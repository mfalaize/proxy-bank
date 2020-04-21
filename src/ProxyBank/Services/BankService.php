<?php


namespace ProxyBank\Services;


use DI\NotFoundException;
use ProxyBank\Container;
use ProxyBank\Exceptions\InvalidTokenException;
use ProxyBank\Exceptions\UnknownBankIdException;
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
            throw new UnknownBankIdException($bankId);
        }

        return $bankImplementation->getAuthToken($inputs);
    }

    public function getAuthTokenWithEncryptedToken(string $bankId, string $token): TokenResult
    {
        $decrypted = $this->cryptoService->decrypt($token);
        if (empty($decrypted)) {
            throw new InvalidTokenException();
        }
        $inputs = json_decode($decrypted, true);
        return $this->getAuthTokenWithUnencryptedInputs($bankId, $inputs);
    }
}
