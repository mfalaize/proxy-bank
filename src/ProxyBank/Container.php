<?php


namespace ProxyBank;


use ProxyBank\Services\BankServiceInterface;

class Container extends \DI\Container
{

    public function getBankImplementations(): array
    {
        $entryNames = array_filter($this->getKnownEntryNames(), function ($entryName) {
            return in_array(BankServiceInterface::class, class_implements($this->get($entryName)));
        });

        $implementations = array_map(function ($entryName) {
            return $this->get($entryName);
        }, $entryNames);

        return array_values($implementations);
    }
}
