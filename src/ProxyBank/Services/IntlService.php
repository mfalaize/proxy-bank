<?php


namespace ProxyBank\Services;


use MessageFormatter;
use ResourceBundle;

class IntlService
{

    /**
     * @var string
     */
    public $locale;

    /**
     * @var ResourceBundle
     */
    private $resourceBundle;

    public function setLocale(string $locale): IntlService
    {
        $this->locale = $locale;
        $this->resourceBundle = ResourceBundle::create($locale, __DIR__ . "/../../../locale");
        return $this;
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
        ) ?: $index;
    }
}
