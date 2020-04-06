<?php


namespace ProxyBank;


use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class CryptoTest extends TestCase
{

    private $crypto;
    private $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup();
        $this->crypto = new CryptoPrivateAccess($this->root->url());
    }

    public function testGetSecretFilePathShouldPrependSecretFileNameWithRootDir()
    {
        $this->assertEquals("vfs://root/secret.txt", $this->crypto->getSecretFilePath());
    }

    public function testGetSecretPasswordShouldReturnSecretFileContent(): void
    {
        vfsStream::newFile(Crypto::SECRET_FILE)
            ->withContent("123456abcdef")
            ->at($this->root);

        $this->assertEquals("123456abcdef", $this->crypto->getSecretPassword());
    }

    public function testGetSecretPasswordShouldNotGenerateFileIfItAlreadyExists()
    {
        vfsStream::newFile(Crypto::SECRET_FILE)
            ->withContent("123456abcdef")
            ->at($this->root);

        $this->crypto = $this->getMockBuilder(CryptoPrivateAccess::class)
            ->setConstructorArgs([$this->root->url()])
            ->onlyMethods(["generateSecretPasswordFile"])
            ->getMock();
        $this->crypto->expects($this->never())->method("generateSecretPasswordFile");

        $this->crypto->getSecretPassword();
    }

    public function testGetSecretPasswordShouldGenerateFileIfItDoesNotExist()
    {
        $this->crypto = $this->getMockBuilder(CryptoPrivateAccess::class)
            ->setConstructorArgs([$this->root->url()])
            ->onlyMethods(["generateSecretPasswordFile"])
            ->getMock();
        $this->crypto->expects($this->atLeastOnce())->method("generateSecretPasswordFile")->willReturnCallback(function () {
            vfsStream::newFile(Crypto::SECRET_FILE)
                ->withContent("123456abcdef")
                ->at($this->root);
        });
        $this->assertEquals("123456abcdef", $this->crypto->getSecretPassword());
    }

    public function testGenerateRandomSecretShouldGenerateRandom128LengthSecret()
    {
        $secrets = [];
        for ($i = 0; $i < 20; $i++) {
            $secret = $this->crypto->generateRandomSecret();

            $this->assertEquals(128, strlen($secret));
            $this->assertFalse(in_array($secret, $secrets));

            $secrets[] = $secret;
        }
    }

    public function testGenerateSecretPasswordFileShouldGenerateAFileWithRandomSecretInside()
    {
        $this->crypto = $this->getMockBuilder(CryptoPrivateAccess::class)
            ->setConstructorArgs([$this->root->url()])
            ->onlyMethods(["generateRandomSecret"])
            ->getMock();
        $this->crypto->expects($this->atLeastOnce())->method("generateRandomSecret")->willReturn("123456abcdef");

        $this->crypto->generateSecretPasswordFile();

        $expectedSecretFile = "vfs://root/secret.txt";
        $this->assertTrue(file_exists($expectedSecretFile));
        $file = fopen($expectedSecretFile, "r");
        $this->assertEquals("123456abcdef", fread($file, 12));
        fclose($file);
    }

    public function testEncryptThenDecryptShouldNotAlterData()
    {
        vfsStream::newFile(Crypto::SECRET_FILE)
            ->withContent("123456abcdef")
            ->at($this->root);

        $text = "encrypt test sample";
        $encrypted = $this->crypto->encrypt($text);

        $this->assertNotEquals($text, $encrypted);
        $this->assertEquals($text, $this->crypto->decrypt($encrypted));
    }

    public function testEncryptThenDecryptWithDifferentSecretFileShouldReturnEmptyString()
    {
        vfsStream::newFile(Crypto::SECRET_FILE)
            ->withContent("123456abcdef")
            ->at($this->root);

        $text = "encrypt test sample";
        $encrypted = $this->crypto->encrypt($text);

        vfsStream::newFile(Crypto::SECRET_FILE)
            ->withContent("anotherSecret")
            ->at($this->root);

        $this->assertEquals("", $this->crypto->decrypt($encrypted));
    }
}

class CryptoPrivateAccess extends Crypto
{
    public function getSecretPassword(): string
    {
        return parent::getSecretPassword();
    }

    public function generateRandomSecret(): string
    {
        return parent::generateRandomSecret();
    }

    public function generateSecretPasswordFile(): void
    {
        parent::generateSecretPasswordFile();
    }

    public function getSecretFilePath(): string
    {
        return parent::getSecretFilePath();
    }
}
