<?php

return [
	'columns' => [
		// tablename.primary_key.column,
	],
	'chunk_size' => 1000,
	'old_key' => env('OLD_KEY', null),
	'rotater_class' => \IvInteractive\LaravelRotation\Rotater::class,
	'connection' => 'default',
	'queue' => 'default',
];