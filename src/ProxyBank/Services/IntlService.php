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
        ) ?: $index;
    }
}
