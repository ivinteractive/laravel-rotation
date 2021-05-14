<?php

namespace IvInteractive\Rotation\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use IvInteractive\Rotation\Rotater;
use IvInteractive\Rotation\Tests\Resources\User;

class ReencryptionTest extends \IvInteractive\Rotation\Tests\TestCase
{
    use DatabaseMigrations;

    protected $rotater;
    protected $user;
    protected $userObj;
    protected $dob;

    public function setUp() : void
    {
        parent::setUp();

        $this->dob = '1980-03-04';

        $this->rotater = $this->makeRotater();

        $oldEncrypter = $this->makeEncrypter(($this->rotater->getOldEncrypter())->getKey());

        $this->user = User::factory()->create();
        $this->user->update(['dob'=>$oldEncrypter->encrypt($this->dob)]);

        $this->userObj = app('db')->table('users')->where('id', $this->user->id)->first();
    }

    public function testReencryptionDatabaseRecord()
    {
        $this->rotater->rotateRecord($this->userObj);
        $encrypter = $this->makeEncrypter(($this->rotater->getNewEncrypter())->getKey());

        $user = $this->user->fresh();

        $this->assertSame($this->dob, $encrypter->decrypt($user->dob));
    }
}
