<?php


namespace ProxyBank\Models;


use DateTimeInterface;
use JsonSerializable;

/**
 * @OA\Schema()
 */
class Transaction implements JsonSerializable
{
    /**
     * @OA\Property()
     * @var string
     */
    public $description;

    /**
     * @OA\Property(type="string", format="date")
     * @var DateTimeInterface
     */
    public $date;

    /**
     * @OA\Property(pattern="^-?\d+\.\d{2}$")
     * @var string
     */
    public $amount;

    /**
     * @OA\Property(pattern="^-?\d+\.\d{2}$")
     * @var string account balance at the time of the transaction
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
