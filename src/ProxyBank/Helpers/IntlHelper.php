<?php


namespace ProxyBank\Helpers;


use MessageFormatter;
use ResourceBundle;

class IntlHelper
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var ResourceBundle
     */
    private $resourceBundle;

    public function __construct(string $locale)
    {
        $this->locale = $locale;
        $this->resourceBundle = ResourceBundle::create($locale, __DIR__ . "/../../../locale");
    }

    /**
     * @param int|string $index
     * @param array $args
     * @return string
     */
    public function getMessage($index, array $args = []): string
    {
        return MessageFormatter::formatMessage(
            $this->locale,
            $this->resourceBundle->get($index),
            $args
        );
    }

    public function __toString()
    {
        return $this->locale;
    }


}
