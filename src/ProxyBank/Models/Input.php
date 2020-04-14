<?php


namespace ProxyBank\Models;


use JsonSerializable;

class Input implements JsonSerializable
{
    const TYPE_TEXT = "text";
    const TYPE_PASSWORD = "password";

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }


    public function jsonSerialize()
    {
        return [
            "name" => $this->name,
            "type" => $this->type
        ];
    }
}
