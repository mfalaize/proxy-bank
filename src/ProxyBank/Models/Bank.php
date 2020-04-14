<?php


namespace ProxyBank\Models;


use JsonSerializable;

class Bank implements JsonSerializable
{
    /**
     * @var string a unique id for the bank which will not be modified
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var array Inputs for authentication
     */
    public $authInputs;

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "authInputs" => $this->authInputs
        ];
    }
}
