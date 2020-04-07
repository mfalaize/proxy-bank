<?php


use PHPUnit\Framework\TestCase;
use ProxyBank\Crypto;

/**
 * @backupGlobals enabled
 */
class EncryptTest extends TestCase
{

    private $crypto;

    public static function setUpBeforeClass(): void
    {
        $_SERVER["REQUEST_METHOD"] = "GET";
        include_once "../encrypt.php";
    }

    protected function setUp(): void
    {
        $_SERVER["REQUEST_METHOD"] = "POST";
        $_POST["login"] = "myLogin";
        $_POST["password"] = "myPassword";

        global $crypto;
        $crypto = $this->createMock(Crypto::class);
        $this->crypto = $crypto;
    }

    public function testProcessRequestShouldReturnError405ForGetMethod(): void
    {
        $_SERVER["REQUEST_METHOD"] = "GET";
        processRequest();
        $this->assertEquals(405, http_response_code());
    }

    public function testProcessRequestShouldReturnError400IfNoLogin(): void
    {
        unset($_POST["login"]);
        processRequest();
        $this->assertEquals(400, http_response_code());
    }

    public function testProcessRequestShouldReturnError400IfNoPassword(): void
    {
        unset($_POST["password"]);
        processRequest();
        $this->assertEquals(400, http_response_code());
    }

    /**
     * @runInSeparateProcess
     */
    public function testProcessRequestShouldReturnSuccess(): void
    {
        processRequest();
        $this->assertEquals(200, http_response_code());
    }

    /**
     * @runInSeparateProcess
     */
    public function testProcessRequestShouldReturnEncryptedLoginAndPasswordInBase64(): void
    {
        $this->crypto->expects($this->atLeastOnce())->method("encrypt")->willReturn("encryptedData");
        $response = processRequest();
        $this->assertEquals("ZW5jcnlwdGVkRGF0YQ==", $response);
    }
}
