<?php

namespace IvInteractive\Rotation\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use IvInteractive\Rotation\Tests\Resources\User;

class RecordCountTest extends \IvInteractive\Rotation\Tests\TestCase
{
    use DatabaseMigrations;

    private $rotater;

    public const COUNT = 10;

    public function setUp(): void
    {
        parent::setUp();
        $this->rotater = $this->makeRotater();

        User::factory()->count(static::COUNT)->create();
    }

    public function testRecordCount()
    {
        $this->assertSame(static::COUNT, $this->rotater->getCount());
    }

    public function testRecordCountAfterChange()
    {
        $this->rotater->setColumnIdentifier('users.id.name');
        $this->rotater->setColumnIdentifier('users.id.dob');

        $this->assertSame(static::COUNT, $this->rotater->getCount());
    }

    public function testRecordCountNonNull()
    {
        $toEncrypt = 5;

        app('db')->table('users')
                 ->take($toEncrypt)
                 ->update([
                    'dob' => null,
                 ]);

        $this->assertSame((static::COUNT - $toEncrypt), $this->rotater->getCount());
    }

    public function testRecordCountNotSet()
    {
        $rotater = $this->makeRotater(false);
        $this->assertNull($rotater->getCount());
    }
}
