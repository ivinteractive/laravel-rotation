<?php

namespace IvInteractive\Rotation\Tests\Unit;

use IvInteractive\Rotation\Rotater;

class EncryptionTest extends \IvInteractive\Rotation\Tests\TestCase
{
    private $oldKey;
    private $newKey;
    private $rotater;

    public function setUp(): void
    {
        parent::setUp();

        $this->rotater = $this->makeRotater();

        $this->oldKey = ($this->rotater->getOldEncrypter())->getKey();
        $this->newKey = ($this->rotater->getNewEncrypter())->getKey();
    }

    public function testDecryptionFunctionality()
    {
        $value = 'Lorem ipsum dolor sit amet';
        $enc = $this->makeEncrypter($this->oldKey);

        $method = $this->getMethod('decrypt');

        $decrypted = $method->invokeArgs($this->rotater, [$enc->encrypt($value)]);

        $this->assertSame($value, $decrypted);
    }

    public function testEncryptionFunctionality()
    {
        $value = 'Lorem ipsum dolor sit amet';
        $enc = $this->makeEncrypter($this->newKey);

        $method = $this->getMethod('encrypt');

        $encrypted = $method->invokeArgs($this->rotater, [$value]);

        $this->assertSame($value, $enc->decrypt($encrypted));
    }

    public function testReencryptionFunctionality()
    {
        $value = 'Lorem ipsum dolor sit amet';
        $encOld = $this->makeEncrypter($this->oldKey);
        $encNew = $this->makeEncrypter($this->newKey);

        $method = $this->getMethod('reencrypt');

        $encrypted = $encOld->encrypt($value);
        $reencrypted = $method->invokeArgs($this->rotater, [$encrypted]);

        $this->assertSame($value, $encNew->decrypt($reencrypted));
    }

    private function getMethod(string $methodName)
    {
        $class = new \ReflectionClass(Rotater::class);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}
