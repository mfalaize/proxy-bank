<?php


namespace ProxyBank\Services;


use Psr\Container\ContainerInterface;
use ReflectionClass;

class BankService
{

    /**
     * @var array
     */
    public $bankImplementations;

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $bankImplementationsFiles = glob(__DIR__ . "/Banks/*.php");
        foreach ($bankImplementationsFiles as $bankImplementationFile) {
            require_once $bankImplementationFile;
        }

        $bankImplementationsClasses = array_filter(
            get_declared_classes(),
            function ($className) {
                return in_array(BankServiceInterface::class, class_implements($className));
            }
        );

        $this->bankImplementations = array_map(function ($bankImplementationClass) {
            $reflectionClass = new ReflectionClass($bankImplementationClass);
            return $reflectionClass->newInstance($this->container);
        }, $bankImplementationsClasses);
    }

    public function listAvailableBankNames(): array
    {
        $bankNames = array_map(function($bankImplementation) {
            return $bankImplementation->getBankName();
        }, $this->bankImplementations);
        sort($bankNames);
        return $bankNames;
    }
}
