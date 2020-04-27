<?php


namespace ProxyBank\Models;


use JsonSerializable;

/**
 * @OA\Schema()
 */
class Bank implements JsonSerializable
{
    /**
     * @OA\Property()
     * @var string a unique id for the bank which is used as URL path variable to access bank specific services
     */
    public $id;

    /**
     * @OA\Property()
     * @var string
     */
    public $name;

    /**
     * @OA\Property(
     *     @OA\Items(ref="#/components/schemas/Input")
     * )
     * @var array Inputs required for authentication
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
