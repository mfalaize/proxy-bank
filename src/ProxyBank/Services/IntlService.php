<?php


namespace ProxyBank\Services;


use MessageFormatter;
use ResourceBundle;

class IntlService
{
    const DEFAULT_LOCALE = "en_US";
    const ERROR_RESOURCES = "errors";

    /**
     * @var string
     */
    public $locale;

    /**
     * @var ResourceBundle
     */
    private $resourceBundle;

    public function __construct()
    {
        $this->setLocale(self::DEFAULT_LOCALE);
    }

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

    /**
     * @param int|string $index
     * @param array $args
     * @return string
     */
    public function getErrorMessage($index, array $args = []): string
    {
        $errorResources = $this->resourceBundle->get(self::ERROR_RESOURCES);
        return MessageFormatter::formatMessage(
            $this->locale,
            $errorResources->get($index),
            $args
        ) ?: $index;
    }
}
