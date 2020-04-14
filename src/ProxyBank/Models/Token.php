<?php


namespace ProxyBank\Models;


use JsonSerializable;

class Token implements JsonSerializable
{
    /**
     * The token is an encrypted json
     * @var string
     */
    public $token;

    /**
     * True if the encrypted token contains enough data to authenticate against bank server.
     * False if it is incomplete (i.e. need a second factor authentication)
     * @var boolean
     */
    public $completedToken;

    /**
     * @var string
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
