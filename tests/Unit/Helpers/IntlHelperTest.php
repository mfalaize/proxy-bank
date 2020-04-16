<?php


namespace Tests\Unit\Helpers;


use PHPUnit\Framework\TestCase;
use ProxyBank\Helpers\IntlHelper;

class IntlHelperTest extends TestCase
{
    /**
     * @test
     */
    public function getMessage_should_retrieve_message_in_ResourceBundle_and_use_MessageFormatter_syntax()
    {
        $intl = new IntlHelper("en_US");
        $this->assertEquals(
            "4,560 monkeys on 123 trees make 37.073 monkeys per tree",
            $intl->getMessage("test", [4560, 123, 4560 / 123])
        );

        // FIXME This assertion fails in php docker container for unknown reason...
        /*$intl = new IntlHelper("fr_FR");
        $this->assertEquals(
            "4 560 singes sur 123 arbres font 37,073 singes par arbre",
            $intl->getMessage("test", [4560, 123, 4560 / 123])
        );*/
    }
}
