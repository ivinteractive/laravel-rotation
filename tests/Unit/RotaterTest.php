<?php

namespace IvInteractive\Rotation\Tests\Unit;

use IvInteractive\Rotation\Rotater;

class RotaterTest extends \IvInteractive\Rotation\Tests\TestCase
{
	const COLUMN_ID = 'users.id.dob';

	private $rotater;

	public function setUp() : void
	{
		parent::setUp();
		$this->rotater = $this->makeRotater();
		$this->rotater->setColumnIdentifier(static::COLUMN_ID);
	}

	public function testRotaterTable()
	{
		$this->assertSame('users', $this->rotater->getTable());
	}

	public function testRotaterPrimaryKey()
	{
		$this->assertSame('id', $this->rotater->getPrimaryKey());
	}

	public function testRotaterColumn()
	{
		$this->assertSame('dob', $this->rotater->getColumn());
	}
}
