<?php

namespace IvInteractive\LaravelRotation\Console\Commands;

use Illuminate\Foundation\Console\KeyGenerateCommand;
use IvInteractive\LaravelRotation\Rotater;

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

        $rotater = new Rotater($oldKey, $newKey);

        $this->info('A new application key has been generated. Laravel Rotation will re-encrypt the following data:');
        $this->newLine();

        $columns = config('laravel-rotation.columns');

        foreach($columns as $col) {
            $rotater->setColumnIdentifier($col);

            $this->info('Table name: '.$rotater->getTable());
            $this->info('Column name: '.$rotater->getColumn());
            $this->info('Number of records: '.$rotater->getCount());
            $this->newLine();
        }

        if ($this->confirm('Do you wish to continue?')) {

            $this->info($newKey);

            if (! $this->setKeyInEnvironmentFile($newKey)) {
                return;
            }

            $this->info('Application key set successfully.');

            if (file_exists(base_path('bootstrap/cache/config.php')))
                $this->call('config:cache');

            if ($this->option('horizon'))
                $this->call('horizon:terminate');
            else
                $this->call('queue:restart');

            foreach($columns as $col) {
                $rotater->setColumnIdentifier($col);
                $bar = $this->output->createProgressBar($rotater->getCount());

                $message = config('queue.default') === 'sync' ? 'Re-encrypting data' : 'Dispatching data re-encryption jobs';
                $this->info($message.' for '.$col.'...');

                $bar->start();
                $rotater->rotate($bar);
                $bar->finish();
                $this->newLine();
            }
        }

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