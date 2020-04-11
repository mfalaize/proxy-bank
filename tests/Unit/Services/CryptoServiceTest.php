<?php


namespace Tests\Unit\Services;


use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ProxyBank\Services\CryptoService;
use Psr\Log\LoggerInterface;

class CryptoServiceTest extends TestCase
{

    private $service;
    private $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup();
        $this->service = new CryptoService(
            $this->root->url(),
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @test
     */
    public function getSecretFilePath_should_prepend_secret_filename_with_src_dir()
    {
        $this->assertEquals("vfs://root/secret.php", $this->service->getSecretFilePath());
    }

    /**
     * @test
     */
    public function getSecretPassword_should_return_secret_content()
    {
        vfsStream::newFile(CryptoService::SECRET_FILE)
            ->withContent("<?php return \"123456abcdef\";")
            ->at($this->root);

        $this->assertEquals("123456abcdef", $this->service->getSecretPassword());
    }

    /**
     * @test
     */
    public function getSecretPassword_should_not_generate_file_if_it_already_exists()
    {
        vfsStream::newFile(CryptoService::SECRET_FILE)
            ->at($this->root);

        $this->service = $this->getMockBuilder(CryptoService::class)
            ->setConstructorArgs([$this->root->url(), $this->createMock(LoggerInterface::class)])
            ->onlyMethods(["generateSecretPasswordFile"])
            ->getMock();
        $this->service->expects($this->never())->method("generateSecretPasswordFile");

        $this->service->getSecretPassword();
    }

    /**
     * @test
     */
    public function getSecretPassword_should_generate_file_if_it_does_not_exist()
    {
        $this->service = $this->getMockBuilder(CryptoService::class)
            ->setConstructorArgs([$this->root->url(), $this->createMock(LoggerInterface::class)])
            ->onlyMethods(["generateSecretPasswordFile"])
            ->getMock();
        $this->service->expects($this->atLeastOnce())->method("generateSecretPasswordFile")->willReturnCallback(function () {
            vfsStream::newFile(CryptoService::SECRET_FILE)
                ->withContent("<?php return \"123456abcdef\";")
                ->at($this->root);
        });
        $this->assertEquals("123456abcdef", $this->service->getSecretPassword());
    }

    /**
     * @test
     */
    public function generateRandomSecret_should_generate_random_128_length_secret()
    {
        $secrets = [];
        for ($i = 0; $i < 20; $i++) {
            $secret = $this->service->generateRandomSecret();

            $this->assertEquals(128, strlen($secret));
            $this->assertFalse(in_array($secret, $secrets));

            $secrets[] = $secret;
        }
    }

    /**
     * @test
     * Quotation marks and dollar char have to be excluded from possibles chars because they are not escaped
     * in the secret.php generated file.
     */
    public function generateRandomSecret_should_not_generate_quotation_marks_and_dollar_char()
    {
        for ($i = 0; $i < 20; $i++) {
            $secret = $this->service->generateRandomSecret();
            $this->assertStringNotContainsString('"', $secret);
            $this->assertStringNotContainsString('$', $secret);
        }
    }

    /**
     * @test
     */
    public function generateSecretPasswordFile_should_generate_a_php_file_with_random_secret_inside()
    {
        $this->service = $this->getMockBuilder(CryptoService::class)
            ->setConstructorArgs([$this->root->url(), $this->createMock(LoggerInterface::class)])
            ->onlyMethods(["generateRandomSecret"])
            ->getMock();
        $this->service->expects($this->atLeastOnce())->method("generateRandomSecret")->willReturn("123456abcdef");

        $this->service->generateSecretPasswordFile();

        $expectedSecretFile = "vfs://root/secret.php";
        $this->assertTrue(file_exists($expectedSecretFile));
        $file = fopen($expectedSecretFile, "r");
        $this->assertEquals("<?php return \"123456abcdef\";", fread($file, 28));
        fclose($file);
    }

    /**
     * @test
     */
    public function encrypt_then_decrypt_should_return_the_same_initial_data()
    {
        vfsStream::newFile(CryptoService::SECRET_FILE)
            ->withContent("<?php return \"123456abcdef\";")
            ->at($this->root);

        $text = "encrypt test sample";
        $encrypted = $this->service->encrypt($text);

        $this->assertNotEquals($text, $encrypted);
        $this->assertEquals($text, $this->service->decrypt($encrypted));
    }

    /**
     * @test
     */
    public function encrypt_then_decrypt_with_different_secret_file_should_return_empty_string()
    {
        vfsStream::newFile(CryptoService::SECRET_FILE)
            ->withContent("<?php return \"123456abcdef\";")
            ->at($this->root);

        $text = "encrypt test sample";
        $encrypted = $this->service->encrypt($text);

        vfsStream::newFile(CryptoService::SECRET_FILE)
            ->withContent("<?php return \"anotherSecret\";")
            ->at($this->root);

        $this->assertEquals("", $this->service->decrypt($encrypted));
    }
}
