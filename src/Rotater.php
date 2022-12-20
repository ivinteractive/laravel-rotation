<?php

namespace IvInteractive\Rotation;

use Illuminate\Bus\PendingBatch;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use IvInteractive\Rotation\Contracts\RotatesApplicationKey;
use IvInteractive\Rotation\Exceptions\AlreadyReencryptedException;
use IvInteractive\Rotation\Jobs\ReencryptionJob;

class Rotater implements RotatesApplicationKey
{
    private $oldEncrypter;
    private $newEncrypter;

    private $columnIdentifier;

    private $recordCounts = [];

    /**
     * @param string $oldKey The old base64-encoded key
     * @param string $newKey The new base64-encoded key
     */
    public function __construct(string $oldKey, string $newKey)
    {
        $this->oldEncrypter = new Encrypter($this->parseKey($oldKey), config('rotation.cipher.old', config('app.cipher')));
        $this->newEncrypter = new Encrypter($this->parseKey($newKey), config('rotation.cipher.new', config('app.cipher')));
    }

    /**
     * Set the identifier for the database column (table.id.column).
     * @param string $columnIdentifier
     */
    public function setColumnIdentifier(string $columnIdentifier): void
    {
        $this->columnIdentifier = $columnIdentifier;
    }

    /**
     * Get chunked database records and push to the queue for re-encryption.
     * @param  \Illuminate\Bus\PendingBatch                  $batch
     * @param  \Symfony\Component\Console\Helper\ProgressBar $bar
     */
    public function rotate(PendingBatch $batch, ?\Symfony\Component\Console\Helper\ProgressBar $bar=null): void
    {
        if ($bar !== null) {
            $bar->start();
        }

        $records = app('db')->table($this->getTable())
                            ->select([$this->getPrimaryKey(), $this->getColumn()])
                            ->whereNotNull($this->getColumn())
                            ->orderBy($this->getPrimaryKey())
                            ->chunk(config('rotation.chunk_size'), function ($records) use ($batch, $bar) {
                                $batch->add([new ReencryptionJob($this->columnIdentifier, $records->pluck($this->getPrimaryKey())->toArray())]);
                                if ($bar !== null) {
                                    $bar->advance($records->count());
                                }
                            });

        if ($bar !== null) {
            $bar->finish();
        }
    }

    /**
     * Re-encrypt an individual database record.
     * @param  stdClass $record
     */
    public function rotateRecord(\stdClass $record): void
    {
        if ($reencrypted = $this->reencrypt($record->{$this->getColumn()})) {
            app('db')->table($this->getTable())
                     ->where($this->getPrimaryKey(), $record->{$this->getPrimaryKey()})
                     ->update([
                        $this->getColumn() => $reencrypted,
                     ]);
        }
    }

    /**
     * Re-encrypt an encrypted value.
     * @param  string $encryptedValue
     * @return string The value after encryption with the new key
     */
    public function reencrypt(string $encryptedValue): string
    {
        try {
            $decrypted = $this->decrypt($encryptedValue);
            return $this->encrypt($decrypted);
        } catch (AlreadyReencryptedException $e) {
            return $encryptedValue;
        }
    }

    /**
     * Decrypt the encrypted value with the old key.
     * @param  string $encryptedValue
     * @return mixed  The decrypted value
     */
    protected function decrypt(string $encryptedValue)
    {
        try {
            return $this->oldEncrypter->decrypt($encryptedValue);
        } catch (DecryptException $e) {
            try {
                $decrypted = $this->newEncrypter->decrypt($encryptedValue);
                throw new AlreadyReencryptedException('The value has already been decrypted.');
            } catch (DecryptException $ex) {
                throw $e;
            }
        }
    }

    /**
     * Encrypt the decrypted value with the new encryption key.
     * @param  mixed   $value
     * @return string  The re-encrypted value
     */
    protected function encrypt($value): string
    {
        return $this->newEncrypter->encrypt($value);
    }

    /**
     * Parse the encryption key.
     *
     * @param  string The encoded key from the config
     * @return string
     */
    private function parseKey(string $key)
    {
        if (Str::startsWith($key, $prefix = 'base64:')) {
            $key = base64_decode(Str::after($key, $prefix));
        }

        return $key;
    }

    /**
     * Get the table for the currently-set column.
     * @return string The table name
     */
    public function getTable(): string
    {
        return explode('.', $this->columnIdentifier)[0];
    }

    /**
     * Get the primary key for the currently-set column.
     * @return string The primary key column name
     */
    public function getPrimaryKey(): string
    {
        return explode('.', $this->columnIdentifier)[1];
    }

    /**
     * Get the name for the currently-set column.
     * @return string The column name
     */
    public function getColumn(): string
    {
        return explode('.', $this->columnIdentifier)[2];
    }

    /**
     * Get the number of records for the currently-set column.
     * @return int|null  The count
     */
    public function getCount(): ?int
    {
        if (!strlen($this->getTable())) {
            return null;
        }

        if (!array_key_exists($this->columnIdentifier, $this->recordCounts)) {
            $this->recordCounts[$this->columnIdentifier] = app('db')->table($this->getTable())->whereNotNull($this->getColumn())->count();
        }

        return $this->recordCounts[$this->columnIdentifier];
    }

    /**
     * Get the old Encrypter.
     * @return \Illuminate\Encryption\Encrypter|null
     */
    public function getOldEncrypter(): ?Encrypter
    {
        return $this->oldEncrypter;
    }

    /**
     * Get the new Encrypter.
     * @return \Illuminate\Encryption\Encrypter|null
     */
    public function getNewEncrypter(): ?Encrypter
    {
        return $this->newEncrypter;
    }

    /**
     * The actions to run when the batch is complete.
     * @param  \Illuminate\Bus\Batch  $batch
     */
    public static function finish(\Illuminate\Bus\Batch $batch): void
    {
        if (config('rotation.maintenance')) {
            Artisan::call('up');
        }

        if (config('rotation.remove_old_key')) {
            static::removeOldKey();
        }

        if (config('rotation.cipher.new')) {
            static::setNewCipher();
        }

        static::refreshConfig($batch);

        event(new \IvInteractive\Rotation\Events\ReencryptionFinished($batch->toArray()));
    }

    /**
     * Make the job batch for the queue.
     * @param  bool $withHorizon
     * @return \Illuminate\Bus\PendingBatch
     */
    public function makeBatch(bool $withHorizon=false): PendingBatch
    {
        $batch = Bus::batch([])
                    ->name('reencryption_' . now()->format('Y-m-d_H:i:s'))
                    ->withOption('horizon', $withHorizon)
                    ->then([static::class, 'finish']);

        if (config('rotation.connection') !== 'default') {
            $batch->onConnection(config('rotation.connection'));
        }

        if (config('rotation.queue') !== 'default') {
            $batch->onQueue(config('rotation.queue'));
        }

        return $batch;
    }

    /**
     * Remove the old application key from the .env.
     * @return void
     */
    protected static function removeOldKey(): void
    {
        $environmentFilePath = app()->environmentFilePath();
        $contents = file_get_contents($environmentFilePath);

        file_put_contents($environmentFilePath, preg_replace(
            '/OLD_KEY=(.*)/',
            '',
            $contents,
        ));
    }

    /**
     * Set the new cipher in the app.php config file.
     * @return void
     */
    protected static function setNewCipher(): void
    {
        if (config('rotation.cipher.new')) {
            $configPath = app()->configPath('app.php');
            $contents = file_get_contents($configPath);

            file_put_contents($configPath, preg_replace(
                '/\'cipher\'(\s*)\=\>(\s*)\'(.*)\'/',
                '\'cipher\' => \'' . config('rotation.cipher.new') . '\'',
                $contents,
            ));
        }
    }

    /**
     * Refresh the config and restart the queue.
     * @param  \Illuminate\Bus\Batch $batch
     * @return void
     */
    protected static function refreshConfig(\Illuminate\Bus\Batch $batch): void
    {
        // Recache the config
        if (file_exists(app()->bootstrapPath('cache/config.php'))) {
            // @codeCoverageIgnoreStart
            Artisan::call('config:cache');
            // @codeCoverageIgnoreEnd
        }

        // Restart the queue (the `horizon:terminate` command is only available in the console)
        if (!$batch->options['horizon']) {
            Artisan::call('queue:restart');
        }
    }
}
