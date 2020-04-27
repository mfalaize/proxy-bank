<?php


namespace ProxyBank\Models;


use JsonSerializable;

/**
 * @OA\Schema()
 */
class Account implements JsonSerializable
{
    /**
     * @OA\Property()
     * @var string
     */
    public $id;

    /**
     * @OA\Property()
     * @var string
     */
    public $name;

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "name" => $this->name
        ];
    }
}
