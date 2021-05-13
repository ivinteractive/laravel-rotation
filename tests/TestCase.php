<?php

namespace IvInteractive\Rotation\Tests;

use Illuminate\Encryption\Encrypter;
use IvInteractive\Rotation\Rotater;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [\IvInteractive\Rotation\RotationServiceProvider::class];
    }

    protected function makeRotater(): Rotater
    {
    	$oldKey = Encrypter::generateKey(config('app.cipher'));
		$newKey = Encrypter::generateKey(config('app.cipher'));

		return new Rotater('base64:'.base64_encode($oldKey), 'base64:'.base64_encode($newKey));
    }
}
