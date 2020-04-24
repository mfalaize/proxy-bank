<?php


namespace ProxyBank\Models;


use JsonSerializable;

class Account implements JsonSerializable
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $balance;

    public function jsonSerialize()
    {
        $json = [
            "id" => $this->id,
            "name" => $this->name
        ];

        if (!is_null($this->balance)) {
            $json["balance"] = $this->balance;
        }

        return $json;
    }
}
