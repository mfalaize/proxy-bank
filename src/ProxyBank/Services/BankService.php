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

    public function listAvailableBanks(): array
    {
        $banks = array_map(function ($bankImplementation) {
            return $bankImplementation->getBank();
        }, $this->bankImplementations);

        usort($banks, function ($a, $b) {
            return strcmp($a->name, $b->name);
        });

        return $banks;
    }
}
