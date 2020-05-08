<?php

use ProxyBank\Services\Banks\CreditMutuelService;
use function DI\autowire;

return [
    CreditMutuelService::getBank()->id => autowire(CreditMutuelService::class),
];
