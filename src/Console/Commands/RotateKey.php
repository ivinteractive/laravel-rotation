<?php

namespace IvInteractive\Rotation\Console\Commands;

use Illuminate\Bus\Batch;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use IvInteractive\Rotation\Contracts\RotatesApplicationKey;
use IvInteractive\Rotation\Exceptions\ConfigurationException;
use IvInteractive\Rotation\Exceptions\CouldNotParseIdentifierException;
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

    protected RotatesApplicationKey $rotater;
    protected \Illuminate\Bus\PendingBatch $batch;

    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        $newKey = $this->generateRandomKey();

        $this->rotater = app(RotatesApplicationKey::class, ['oldKey'=>config('app.key'), 'newKey'=>$newKey]);

        $this->info('A new application key has been generated. Laravel Rotation will re-encrypt the following data:');
        $this->newLine();

        $columns = config('rotation.columns');

        if (!is_array($columns)) {
            throw new ConfigurationException('The list of columns to re-encrypt must be an array. (config path: rotation.columns)');
        }

        $columnFormatExceptionMessage = 'The columns to re-encrypt must be strings following a format of `tablename.primary_key.column`. (config path: rotation.columns)';

        foreach ($columns as $col) {
            if (!is_string($col)) {
                throw new ConfigurationException($columnFormatExceptionMessage);
            }
            $this->printColumnInfo($col);
        }

        if ($this->option('force') || $this->confirm('Do you wish to continue?')) {
            $this->batch = $this->rotater->makeBatch((bool) $this->option('horizon'));

            foreach ($columns as $col) {
                if (!is_string($col)) {
                    throw new ConfigurationException($columnFormatExceptionMessage);
                }
                $this->queueToBatch($col);
            }

            if (! $this->setKeyInEnvironmentFile($newKey)) {
                return Command::FAILURE;
            }

            $this->info('Application key set successfully.');
            $this->refreshConfig($newKey);

            $this->setMaintenanceMode();

            $this->batch->dispatch();
        } else {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        $cipher = config('rotation.cipher.new', config('app.cipher'));

        if (!is_string($cipher)) {
            throw new ConfigurationException('The new cipher must be a string. (config path: rotation.cipher.new)');
        }

        return 'base64:'.base64_encode(
            \Illuminate\Encryption\Encrypter::generateKey($cipher)
        );
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

        try {
            $bar = $this->output->createProgressBar($this->rotater->getCount());
            $this->rotater->rotate($this->batch, $bar);
        } catch (CouldNotParseIdentifierException $e) {
            $this->info($e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Print out information about the columns to be re-encrypted.
     * @param  string $column The column identifier
     */
    protected function printColumnInfo(string $column): void
    {
        $this->rotater->setColumnIdentifier($column);

        try {
            $this->info('Table name: '.$this->rotater->getTable());
            $this->info('Column name: '.$this->rotater->getColumn());
            $this->info('Number of records: '.$this->rotater->getCount());
        } catch (CouldNotParseIdentifierException $e) {
            $this->info($e->getMessage());
        }
        $this->newLine();
    }

    /**
     * Refresh the configuration to include the new encryption key.
     * @param  string $newKey The base64-encoded encryption key
     */
    protected function refreshConfig(string $newKey): void
    {
        // Recache the config
        if (file_exists(app()->bootstrapPath('cache/config.php'))) {
            // @codeCoverageIgnoreStart
            $this->call('config:cache');
            // @codeCoverageIgnoreEnd
        }

        // Set the encryption key and encrypter in the current config and container
        config(['rotation.old_key' => config('app.key')]);
        config(['app.key' => $newKey]);
        app()->singleton('encrypter', function () {
            return $this->rotater->getNewEncrypter();
        });

        // Re-register the service provider to set the secret key
        (new \Illuminate\Encryption\EncryptionServiceProvider(app()))->register();

        // Restart Horizon or the queue
        if ($this->option('horizon')) {
            $this->call('horizon:terminate');
        } else {
            $this->call('queue:restart');
        }
    }

    /**
     * Turn on maintenance mode, if enabled in configuration.
     * @return void
     */
    protected function setMaintenanceMode(): void
    {
        if (config('rotation.maintenance')) {
            if (config('rotation.maintenance-secret')) {
                $secret = (string) \Illuminate\Support\Str::uuid();
                $this->info('Go to '.url($secret).' to view the site while it is in maintenance mode.');
                $this->call('down', ['--secret'=>$secret]);
            } else {
                $this->call('down');
            }
        }
    }

    /**
     * Set the application key in the environment file.
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key): bool
    {
        $config = $this->laravel->get('config');

        if (!$config instanceof \Illuminate\Config\Repository) {
            return false;
        }

        $currentKey = $config['app.key'];

        if (!is_string($currentKey)) {
            return false;
        }

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
     * @param  string  $key
     */
    protected function writeNewEnvironmentFileWithOld($key): void
    {
        $environmentFilePath = app()->environmentFilePath();
        $contents = file_get_contents($environmentFilePath) ?: '';

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
     * @return string
     */
    protected function keyReplacementPatternOld()
    {
        return "/^OLD_KEY\=(.*)/m";
    }
}
