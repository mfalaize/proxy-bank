<?php


namespace ProxyBank\Models;


use JsonSerializable;

/**
 * @OA\Schema()
 */
class TokenResult implements JsonSerializable
{
    /**
     * @OA\Property()
     * @var string server-side encrypted token which contains authentication information
     */
    public $token;

    /**
     * @OA\Property()
     * @var boolean True if the encrypted token contains enough data to authenticate against bank server. False if it is incomplete (i.e. need a second factor authentication)
     */
    public $completedToken;

    /**
     * @OA\Property()
     * @var string indicates what you need to do if the token is not complete
     */
    public $message;

    public function jsonSerialize()
    {
        $json = [];

        if (isset($this->token)) {
            $json["token"] = $this->token;
            $json["completedToken"] = $this->completedToken;
        }

        if (isset($this->message)) {
            $json["message"] = $this->message;
        }

        return $json;
    }
}
