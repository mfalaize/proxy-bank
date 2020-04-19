<?php


namespace ProxyBank\Services;


use DI\NotFoundException;
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

    /**
     * @var IntlService
     */
    private $intlService;

    public function __construct(ContainerInterface $container, CryptoService $cryptoService, IntlService $intlService)
    {
        $this->container = $container;
        $this->cryptoService = $cryptoService;
        $this->intlService = $intlService;
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

    public function getAuthTokenWithUnencryptedInputs(string $bankId, ?array $inputs): TokenResult
    {
        try {
            $bankImplementation = $this->container->get($bankId);
        } catch (NotFoundException $e) {
            $token = new TokenResult();
            $token->message = $this->intlService->getMessage("BankService.errors.unknown.bankId", [$bankId]);
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
