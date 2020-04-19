<?php


namespace ProxyBank\Services;


use ProxyBank\Container;
use ProxyBank\Models\TokenResult;
use Psr\Container\ContainerInterface;

class BankService
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var CryptoService
     */
    private $cryptoService;

    public function __construct(ContainerInterface $container, CryptoService $cryptoService)
    {
        $this->container = $container;
        $this->cryptoService = $cryptoService;
    }

    public function listAvailableBanks(): array
    {
        $banks = array_map(function ($bankImplementation) {
            return $bankImplementation::getBank();
        }, $this->container->getBankImplementations());

        usort($banks, function ($a, $b) {
            return strcmp($a->name, $b->name);
        });

        return $banks;
    }

    public function getAuthTokenWithUnencryptedInputs(string $bankId, array $inputs): TokenResult
    {
        $bankImplementation = $this->container->get($bankId);

        if (is_null($bankImplementation)) {
            $token = new TokenResult();
            $token->message = "Unknown $bankId bankId";
            return $token;
        }

        return $bankImplementation->getAuthToken($inputs);
    }

    public function getAuthTokenWithEncryptedToken(string $bankId, string $token): TokenResult
    {
        $inputs = json_decode($this->cryptoService->decrypt($token), true);
        return $this->getAuthTokenWithUnencryptedInputs($bankId, $inputs);
    }
}
