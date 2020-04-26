<?php


namespace ProxyBank\Models;


use DateTimeInterface;
use JsonSerializable;

class Transaction implements JsonSerializable
{
    /**
     * @var string
     */
    public $description;

    /**
     * @var DateTimeInterface
     */
    public $date;

    /**
     * @var string
     */
    public $amount;

    /**
     * @var string
     */
    public $accountBalance;

    public function jsonSerialize()
    {
        return [
            'date' => $this->date->format("Y-m-d"),
            'description' => $this->description,
            'amount' => $this->amount,
            'accountBalance' => $this->accountBalance
        ];
    }
}
