<?php


namespace ProxyBank\Services;


use ProxyBank\Models\TokenResult;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class BankService
{

    /**
     * @var array
     */
    public $bankImplementations;

    private $container;

    /**
     * @var CryptoService
     */
    private $cryptoService;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->cryptoService = $this->container->get(CryptoService::class);

        $bankImplementationsFiles = glob(__DIR__ . "/Banks/*.php");
        foreach ($bankImplementationsFiles as $bankImplementationFile) {
            require_once $bankImplementationFile;
        }

        $bankImplementationsClasses = array_filter(
            get_declared_classes(),
            function ($className) use (&$bankImplementationsFiles) {
                $classIsCorrectImplementation = in_array(BankServiceInterface::class, class_implements($className));
                $reflectionClass = new ReflectionClass($className);
                return $classIsCorrectImplementation && in_array($reflectionClass->getFileName(), $bankImplementationsFiles);
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

    public function getAuthTokenWithBankId(string $bankId, array $inputs): TokenResult
    {
        $bankImplementation = array_filter($this->bankImplementations, function ($bankImplementation) use (&$bankId) {
            return $bankImplementation->getBank()->id == $bankId;
        });

        if (sizeof($bankImplementation) == 0) {
            $token = new TokenResult();
            $token->message = "Unknown $bankId bankId";
            return $token;
        }

        return array_values($bankImplementation)[0]->getAuthToken($inputs);
    }

    public function getAuthTokenWithToken(string $token): TokenResult
    {
        $inputs = json_decode($this->cryptoService->decrypt($token), true);
        return $this->getAuthTokenWithBankId($inputs["bankId"], $inputs);
    }
}
