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
    protected $signature = 'rotation:run';

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
        $key = $this->generateRandomKey();

        $rotater = new Rotater(config('app.key'), $key);

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

            $this->info($key);

            if (! $this->setKeyInEnvironmentFile($key)) {
                return;
            }

            $this->info('Application key set successfully.');

            foreach($columns as $col) {
                $rotater->setColumnIdentifier($col);
                $bar = $this->output->createProgressBar($rotater->getCount());

                $this->info('Re-encrypting data for '.$col.'...');

                $bar->start();
                $rotater->rotate($bar);
                $bar->finish();
            }
        }

    }
}