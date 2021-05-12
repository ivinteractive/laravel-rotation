<?php

namespace IvInteractive\LaravelRotation\Console\Commands;

use Illuminate\Foundation\Console\KeyGenerateCommand;
use IvInteractive\LaravelRotation\Rotater;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

class RotateKey extends KeyGenerateCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rotation:run {--horizon}';

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
        $oldKey = config('app.key');
        $newKey = $this->generateRandomKey();

        $this->rotater = new Rotater($oldKey, $newKey);

        $this->info('A new application key has been generated. Laravel Rotation will re-encrypt the following data:');
        $this->newLine();

        $columns = config('rotation.columns');

        foreach($columns as $col) {
            $this->printColumnInfo($col);
        }

        if ($this->confirm('Do you wish to continue?')) {

            if (! $this->setKeyInEnvironmentFile($newKey)) {
                return;
            }

            $this->info('Application key set successfully.');
            
            $this->refreshConfig($newKey);

            $this->batch = Bus::batch([])
                              ->name('Reencryption Job');

            foreach($columns as $col) {
                $this->queueToBatch($col);
            }

            $this->batch
                 ->then([Rotater::class, 'finish'])
                 ->dispatch();

            $secret = (string) \Illuminate\Support\Str::uuid();
            $this->info('Go to '.url($secret).' to view the site while it is in maintenance mode.');
            $this->call('down', ['--secret'=>$secret]);
        }
    }

    protected function queueToBatch(string $column) : void
    {        
        $message = config('queue.default') === 'sync' ? 'Re-encrypting data' : 'Batching data re-encryption jobs';
        $this->info($message.' for '.$column.'...');
        $this->rotater->setColumnIdentifier($column);

        $bar = $this->output->createProgressBar($this->rotater->getCount());

        $this->rotater->rotate($this->batch, $bar);

        $this->newLine();
    }

    protected function printColumnInfo(string $column) : void
    {
        $this->rotater->setColumnIdentifier($column);

        $this->info('Table name: '.$this->rotater->getTable());
        $this->info('Column name: '.$this->rotater->getColumn());
        $this->info('Number of records: '.$this->rotater->getCount());
        $this->newLine();
    }

    protected function refreshConfig(string $newKey) : void
    {
        if (file_exists(base_path('bootstrap/cache/config.php')))
            $this->call('config:cache');

        config(['app.key' => $newKey]);
        app()->singleton('encrypter', function () {
            return $this->rotater->getNewEncrypter();
        });
        \Opis\Closure\SerializableClosure::removeSecurityProvider();
        \Opis\Closure\SerializableClosure::setSecretKey(($this->rotater->getNewEncrypter())->getKey());

        if ($this->option('horizon'))
            $this->call('horizon:terminate');
        else
            $this->call('queue:restart');
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $currentKey = $this->laravel['config']['app.key'];

        if(!parent::setKeyInEnvironmentFile($key))
            return false;

        $this->writeNewEnvironmentFileWithOld($currentKey);

        return true;
    }

    /**
     * Write a new environment file with the given key.
     *
     * @param  string  $key
     * @return void
     */
    protected function writeNewEnvironmentFileWithOld($key)
    {
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            $this->keyReplacementPattern(),
            '',
            file_get_contents($this->laravel->environmentFilePath())
        ).PHP_EOL.'OLD_KEY='.$key);
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * @return string
     */
    protected function keyReplacementPatternOld()
    {
        $escaped = preg_quote('='.$this->laravel['config']['old.key'], '/');

        return "/^OLD_KEY{$escaped}/m";
    }
}