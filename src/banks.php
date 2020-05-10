<?php

use ProxyBank\Services\Banks\France\CreditMutuelService;
use function DI\autowire;

return [
    CreditMutuelService::getBank()->id => autowire(CreditMutuelService::class),
];
