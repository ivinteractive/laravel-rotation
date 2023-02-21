<?php

namespace IvInteractive\Rotation\Contracts;

use Illuminate\Bus\PendingBatch;
use Illuminate\Encryption\Encrypter;

interface RotatesApplicationKey
{
    /**
     * Set the identifier for the database column (table.id.column).
     * @param string $columnIdentifier
     */
    public function setColumnIdentifier(string $columnIdentifier): void;

    /**
     * Get chunked database records and push to the queue for re-encryption.
     * @param  \Illuminate\Bus\PendingBatch                  $batch
     * @param  \Symfony\Component\Console\Helper\ProgressBar $bar
     */
    public function rotate(PendingBatch $batch, ?\Symfony\Component\Console\Helper\ProgressBar $bar=null): void;

    /**
     * Re-encrypt an individual database record.
     * @param  \stdClass $record
     */
    public function rotateRecord(\stdClass $record): void;

    /**
     * Re-encrypt an encrypted value.
     * @param  string $encryptedValue
     * @return string The value after encryption with the new key
     */
    public function reencrypt(string $encryptedValue): string;

    /**
     * Get the table for the currently-set column.
     * @return string The table name
     */
    public function getTable(): ?string;

    /**
     * Get the primary key for the currently-set column.
     * @return string The primary key column name
     */
    public function getPrimaryKey(): ?string;

    /**
     * Get the name for the currently-set column.
     * @return string The column name
     */
    public function getColumn(): ?string;

    /**
     * Get the number of records for the currently-set column.
     * @return int|null  The count
     */
    public function getCount(): ?int;

    /**
     * Get the old Encrypter.
     * @return Encrypter|null
     */
    public function getOldEncrypter(): ?Encrypter;

    /**
     * Get the new Encrypter.
     * @return Encrypter|null
     */
    public function getNewEncrypter(): ?Encrypter;

    /**
     * The actions to run when the batch is complete.
     * @param  \Illuminate\Bus\Batch  $batch
     */
    public static function finish(\Illuminate\Bus\Batch $batch): void;

    public function makeBatch(bool $withHorizon=false): PendingBatch;
}
