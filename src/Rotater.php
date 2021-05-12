<?php

namespace IvInteractive\LaravelRotation;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\DecryptException;
use Symfony\Component\Console\Helper\ProgressBar;
use IvInteractive\LaravelRotation\Exceptions\AlreadyReencryptedException;
use IvInteractive\LaravelRotation\Jobs\ReencryptionJob;

class Rotater
{
	private $oldEncrypter;
	private $newEncrypter;

	private $columnIdentifier;
	private $tableName;
	private $columnName;
	private $primaryKey;

	private $recordCounts = [];

	public function __construct(string $oldKey, string $newKey)
	{
		$this->oldEncrypter = new Encrypter($this->parseKey($oldKey), config('app.cipher'));
		$this->newEncrypter = new Encrypter($this->parseKey($newKey), config('app.cipher'));
	}

	public function setColumnIdentifier(string $columnIdentifier)
	{
		$this->columnIdentifier = $columnIdentifier;
	}

	public function rotate(\Illuminate\Bus\PendingBatch $batch, \Symfony\Component\Console\Helper\ProgressBar $bar)
	{
		$bar->start();

		$records = app('db')->table($this->getTable())
							->select([$this->getPrimaryKey(), $this->getColumn()])
							->whereNotNull($this->getColumn())
							->orderBy($this->getPrimaryKey())
							->chunk(config('laravel-rotation.chunk-size'), function ($records) use ($batch, $bar) {
								$batch->add(new ReencryptionJob($this->columnIdentifier, $records->pluck($this->getPrimaryKey())->toArray()));
								$bar->advance($records->count());
							});

		$bar->finish();
	}

	public function rotateRecord(\stdClass $record)
	{
		if ($reencrypted = $this->reencrypt($record->{$this->getColumn()}))
			app('db')->table($this->getTable())
					 ->where($this->getPrimaryKey(), $record->{$this->getPrimaryKey()})
					 ->update([
					 	$this->getColumn() => $reencrypted,
					 ]);
	}

	private function reencrypt(string $value) : ?string
	{
		try {
			$decrypted = $this->decrypt($value);
			return $this->encrypt($decrypted);
		} catch (AlreadyReencryptedException $e) {
			return $value;
		}
	}

	private function decrypt($encryptedValue)
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

	private function encrypt($value) : string
	{
		return $this->newEncrypter->encrypt($value);
	}

	private function parseKey(string $key)
	{
        if (Str::startsWith($key, $prefix = 'base64:')) {
            $key = base64_decode(Str::after($key, $prefix));
        }

        return $key;
	}

	public function getTable() : ?string
	{
		return explode('.', $this->columnIdentifier)[0];
	}

	public function getPrimaryKey() : ?string
	{
		return explode('.', $this->columnIdentifier)[1];
	}

	public function getColumn() : ?string
	{
		return explode('.', $this->columnIdentifier)[2];
	}

	public function getCount() : ?int
	{
		if ($this->getTable()===null)
			return null;

		if (!array_key_exists($this->columnIdentifier, $this->recordCounts))
			$this->recordCounts[$this->columnIdentifier] = app('db')->table($this->getTable())->whereNotNull($this->getColumn())->count();

		return $this->recordCounts[$this->columnIdentifier];
	}

	public function getNewEncrypter()
	{
		return $this->newEncrypter;
	}

	public static function finish()
	{
		\Illuminate\Support\Facades\Artisan::call('up');
        app('log')->info('Reencryption complete!');
        \Illuminate\Support\Facades\Notification::route('mail', 'cs@ivinteractive.com')
            ->notify(new \IvInteractive\LaravelRotation\Notifications\ReencryptionComplete);
	}
}
