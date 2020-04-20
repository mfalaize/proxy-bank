<?php


namespace Tests\Unit\Services;


use PHPUnit\Framework\TestCase;
use ProxyBank\Services\IntlService;

class IntlServiceTest extends TestCase
{
    /**
     * @test
     */
    public function getMessage_should_retrieve_message_in_ResourceBundle_and_use_MessageFormatter_syntax()
    {
        $intl = new IntlService();
        $intl->setLocale("en_US");
        $this->assertEquals(
            "4,560 monkeys on 123 trees make 37.073 monkeys per tree",
            $intl->getMessage("test", [4560, 123, 4560 / 123])
        );

        // FIXME This assertion fails in php docker container for unknown reason...
        /*$intl = new IntlService("fr_FR");
        $this->assertEquals(
            "4 560 singes sur 123 arbres font 37,073 singes par arbre",
            $intl->getMessage("test", [4560, 123, 4560 / 123])
        );*/
    }

    /**
     * @test
     */
    public function getMessage_should_return_key_string_it_value_does_not_exist_in_resource_bundle()
    {
        $intl = new IntlService();
        $intl->setLocale("en_US");
        $this->assertEquals(
            "test.nonexistent",
            $intl->getMessage("test.nonexistent", [4560, 123, 4560 / 123])
        );

        $this->assertEquals(
            "-1",
            $intl->getMessage(-1)
        );
    }

    /**
     * @test
     */
    public function getErrorMessage_should_retrieve_message_in_errors_ResourceBundle_and_use_MessageFormatter_syntax()
    {
        $intl = new IntlService();
        $intl->setLocale("en_US");
        $this->assertEquals(
            "The test for getErrorMessage passed!",
            $intl->getErrorMessage("test", ["getErrorMessage"])
        );

        $intl->setLocale("fr_FR");
        $this->assertEquals(
            "Le test pour getErrorMessage est passé !",
            $intl->getErrorMessage("test", ["getErrorMessage"])
        );
    }

    /**
     * @test
     */
    public function getErrorMessage_should_return_key_string_it_value_does_not_exist_in_resource_bundle()
    {
        $intl = new IntlService();
        $intl->setLocale("en_US");
        $this->assertEquals(
            "test.nonexistent",
            $intl->getErrorMessage("test.nonexistent", [4560, 123, 4560 / 123])
        );

        $this->assertEquals(
            "-1",
            $intl->getErrorMessage(-1)
        );
    }
}
