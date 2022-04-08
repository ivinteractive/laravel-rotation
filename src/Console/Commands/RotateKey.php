<?php

namespace IvInteractive\Rotation\Console\Commands;

use Illuminate\Bus\Batch;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use IvInteractive\Rotation\Contracts\RotatesApplicationKey;
use Symfony\Component\Console\Command\Command;

class RotateKey extends KeyGenerateCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rotation:run
                            {--horizon : Terminate Laravel Horizon instead of restarting the queue}
                            {--force : Skip the confirmation question before batching re-encryption jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate the application encryption key and update all encrypted data.';

    protected $rotater;
    protected $batch;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $newKey = $this->generateRandomKey();

        $this->rotater = app(RotatesApplicationKey::class, ['oldKey'=>config('app.key'), 'newKey'=>$newKey]);

        $this->info('A new application key has been generated. Laravel Rotation will re-encrypt the following data:');
        $this->newLine();

        $columns = config('rotation.columns');

        foreach ($columns as $col) {
            $this->printColumnInfo($col);
        }

        if ($this->option('force') || $this->confirm('Do you wish to continue?')) {
            if (! $this->setKeyInEnvironmentFile($newKey)) {
                return 1;
            }

            $this->info('Application key set successfully.');

            $this->refreshConfig($newKey);

            $this->batch = $this->rotater->makeBatch($this->option('horizon'));

            foreach ($columns as $col) {
                $this->queueToBatch($col);
            }

            $this->batch->dispatch();

            if (config('rotation.maintenance')) {
                $secret = (string) \Illuminate\Support\Str::uuid();
                $this->info('Go to '.url($secret).' to view the site while it is in maintenance mode.');
                $this->call('down', ['--secret'=>$secret]);
            }
        } else {
            return 1;
        }

        return 0;
    }

    /**
     * Push re-encryption jobs to the queue.
     * @param  string $column The column identifier
     */
    protected function queueToBatch(string $column): void
    {
        $message = config('queue.default') === 'sync' ? 'Re-encrypting data' : 'Batching data re-encryption jobs';
        $this->info($message.' for '.$column.'...');
        $this->rotater->setColumnIdentifier($column);

        $bar = $this->output->createProgressBar($this->rotater->getCount());

        $this->rotater->rotate($this->batch, $bar);

        $this->newLine();
    }

    /**
     * Print out information about the columns to be re-encrypted.
     * @param  string $column The column identifier
     */
    protected function printColumnInfo(string $column): void
    {
        $this->rotater->setColumnIdentifier($column);

        $this->info('Table name: '.$this->rotater->getTable());
        $this->info('Column name: '.$this->rotater->getColumn());
        $this->info('Number of records: '.$this->rotater->getCount());
        $this->newLine();
    }

    /**
     * Refresh the configuration to include the new encryption key.
     * @param  string $newKey The base64-encoded encryption key
     */
    protected function refreshConfig(string $newKey): void
    {
        // Recache the config
        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            // @codeCoverageIgnoreStart
            $this->call('config:cache');
            // @codeCoverageIgnoreEnd
        }

        // Set the encryption key and encrypter in the current config and container
        config(['app.key' => $newKey]);
        app()->singleton('encrypter', function () {
            return $this->rotater->getNewEncrypter();
        });

        // Set the encryption key as the new key for serialization when dispatching the batch
        if ($this->laravelVersion() < 9) {
            \Opis\Closure\SerializableClosure::removeSecurityProvider();
            \Opis\Closure\SerializableClosure::setSecretKey(($this->rotater->getNewEncrypter())->getKey());
        } else {
            \Laravel\SerializableClosure\SerializableClosure::setSecretKey(($this->rotater->getNewEncrypter())->getKey());
        }

        // Restart Horizon or the queue
        if ($this->option('horizon')) {
            $this->call('horizon:terminate');
        } else {
            $this->call('queue:restart');
        }
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key): bool
    {
        $currentKey = $this->laravel['config']['app.key'];

        try {
            if (!parent::setKeyInEnvironmentFile($key)) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }

        $this->writeNewEnvironmentFileWithOld($currentKey);

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     */
    protected function writeNewEnvironmentFileWithOld($key)
    {
        $environmentFilePath = $this->laravel->environmentFilePath();
        $contents = file_get_contents($environmentFilePath);

        if (!str_contains($contents, 'OLD_KEY=')) {
            $contents.= PHP_EOL . 'OLD_KEY=';
        }

        file_put_contents($environmentFilePath, preg_replace(
            $this->keyReplacementPatternOld(),
            'OLD_KEY='.$key,
            $contents,
        ));
    }

    /**
     * Get a regex pattern that will match env OLD_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPatternOld()
    {
        return "/^OLD_KEY=(.*)/m";
    }

    private function laravelVersion(): int
    {
        return (int) explode('.', app()->version())[0];
    }
}
