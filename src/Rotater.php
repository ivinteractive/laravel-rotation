<?php

namespace IvInteractive\LaravelRotation;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

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

	public function rotate(?ProgressBar $bar=null)
	{
		$records = app('db')->table($this->getTable())
							->select([$this->getPrimaryKey(), $this->getColumn()])
							->whereNotNull($this->getColumn())
							->orderBy($this->getPrimaryKey())
							->chunk(250, function ($records) use ($bar) {
								foreach ($records as $record) {
									$this->rotateRecord($record);
									$bar->advance();
								}
							});
	}

	protected function rotateRecord(\stdClass $record)
	{
		app('db')->table($this->getTable())
				 ->where($this->getPrimaryKey(), $record->{$this->getPrimaryKey()})
				 ->update([
				 	$this->getColumn() => $this->encrypt($this->decrypt($record->{$this->getColumn()})),
				 ]);
	}

	private function decrypt($encryptedValue)
	{
		return $this->oldEncrypter->decrypt($encryptedValue);
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

}
