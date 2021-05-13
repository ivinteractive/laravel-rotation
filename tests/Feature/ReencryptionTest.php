<?php

namespace IvInteractive\Rotation\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use IvInteractive\Rotation\Rotater;
use IvInteractive\Rotation\Resources\User;

class ReencryptionTest extends \IvInteractive\Rotation\Tests\TestCase
{
    use DatabaseMigrations;

    protected $rotater;
    protected $user;
    protected $dob;

    public function setUp() : void
    {
        parent::setUp();

        $this->dob = '1980-03-04';

        $this->rotater = $this->makeRotater();
        $this->user = User::factory()->create();

        $this->user->update(['dob'=>encrypt($this->dob)]);
    }

    public function testReencryptionDatabaseRecord()
    {
        $this->rotater->rotateRecord((object) $this->user->toArray());
        $encrypter = $this->makeEncrypter(($this->rotater->getNewEncrypter())->getKey());

        $user = $this->user->fresh();

        $this->assertSame($this->dob, $encrypter->decrypt($user->dob));
    }
}
