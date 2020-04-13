<?php


namespace ProxyBank\Models;


use ProxyBank\Models\Security\AuthenticationStrategy;

class Bank implements \JsonSerializable
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
     * @var int (values from {@link AuthenticationStrategy})
     */
    public $authenticationStrategy;

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "name" => $this->name
        ];
    }
}
