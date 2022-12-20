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

    public function testCipherChange()
    {
        config([
            'app.cipher' => 'AES-128-CBC',
            'rotation.cipher' => [
                'old' => 'AES-128-CBC',
                'new' => 'AES-256-GCM',
            ],
        ]);

        $this->oldKey = \Illuminate\Encryption\Encrypter::generateKey('AES-128-CBC');
        $this->setEnvironmentKey('base64:'.base64_encode($this->oldKey));

        $this->rotater = new \IvInteractive\Rotation\Rotater('base64:'.base64_encode($this->oldKey), 'base64:'.base64_encode($this->newKey));
        $this->rotater->setColumnIdentifier('users.id.dob');

        $value = 'Lorem ipsum dolor sit amet';
        $encOld = $this->makeEncrypter($this->oldKey, 'rotation.cipher.old');
        $encNew = $this->makeEncrypter($this->newKey, 'rotation.cipher.new');

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
