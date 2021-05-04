<?php

namespace IvInteractive\LaravelRotation;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;

class Rotater
{
	private $oldEncrypter;
	private $newEncrypter;

	private $tableName;
	private $columnName;
	private $primaryKey;

	public function __construct(string $oldKey, string $newKey, string $columnIdentifier)
	{
		$this->oldEncrypter = new Encrypter($this->parseKey($oldKey), config('app.cipher'));
		$this->newEncrypter = new Encrypter($this->parseKey($newKey), config('app.cipher'));

		$split = explode('.', $columnIdentifier);

		$this->tableName = $split[0];
		$this->columnName = $split[1];
		$this->primaryKey = $split[2];
	}

	public function rotate()
	{
		$records = app('db')->table($this->tableName)
							->select([$this->primaryKey, $this->columnName])
							->get();

		foreach ($records as $record)
			$this->rotateRecord($record);
	}

	protected function rotateRecord(\stdClass $record)
	{
		app('db')->table($this->tableName)
				 ->where($this->primaryKey, $record->{$this->primaryKey})
				 ->update([
				 	$this->columnName => $this->encrypt($this->decrypt($record->{$this->columnName})),
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

}
