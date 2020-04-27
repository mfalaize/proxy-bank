<?php


namespace ProxyBank\Models;


use JsonSerializable;

/**
 * @OA\Schema()
 */
class Input implements JsonSerializable
{
    const TYPE_TEXT = "text";
    const TYPE_PASSWORD = "password";

    /**
     * @OA\Property()
     * @var string The input name which is both the input ID AND the label (can be displayed as is)
     */
    public $name;

    /**
     * @OA\Property(enum={"text", "password"})
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
