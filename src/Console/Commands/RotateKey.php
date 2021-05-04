<?php

namespace IvInteractive\LaravelRotation\Console\Commands;

use Illuminate\Console\Command;

class RotateKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rotation:run {--dry-run}';

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
        

    }
}