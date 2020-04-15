<?php


namespace ProxyBank\Services\Banks;


use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use ProxyBank\Models\Bank;
use ProxyBank\Models\Input;
use ProxyBank\Models\TokenResult;
use ProxyBank\Services\BankServiceInterface;
use ProxyBank\Services\CryptoService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class CreditMutuelService implements BankServiceInterface
{

    const LOGIN_INPUT = "Login";
    const PASSWORD_INPUT = "Password";
    const TRANSACTION_ID_INPUT = "transactionId";

    const DOMAIN = "www.creditmutuel.fr";
    const BASE_URL = 'https://' . self::DOMAIN;
    const AUTH_URL = '/fr/authentification.html';
    const VALIDATION_URL = '/fr/banque/validation.aspx';
    const OTP_TRANSACTION_STATE_URL = "/fr/banque/async/otp/SOSD_OTP_GetTransactionState.htm";

    public $handlerStack;

    private $cryptoService;

    public function __construct(ContainerInterface $container)
    {
        $this->handlerStack = HandlerStack::create();
        $this->cryptoService = $container->get(CryptoService::class);
    }

    public function getBank(): Bank
    {
        $bank = new Bank();
        $bank->id = "credit-mutuel";
        $bank->name = "Crédit Mutuel";
        $bank->authInputs = [
            new Input(self::LOGIN_INPUT, Input::TYPE_TEXT),
            new Input(self::PASSWORD_INPUT, Input::TYPE_PASSWORD),
        ];
        return $bank;
    }

    public function getAuthToken(array $inputs): TokenResult
    {
        if (!isset($inputs[self::LOGIN_INPUT])) {
            $token = new TokenResult();
            $token->message = self::LOGIN_INPUT . " value is required";
            return $token;
        }

        if (!isset($inputs[self::PASSWORD_INPUT])) {
            $token = new TokenResult();
            $token->message = self::PASSWORD_INPUT . " value is required";
            return $token;
        }

        if (isset($inputs[self::TRANSACTION_ID_INPUT])) {
            return $this->processTransactionState($inputs[self::TRANSACTION_ID_INPUT]);
        }

        return $this->processAuthentication($inputs[self::LOGIN_INPUT], $inputs[self::PASSWORD_INPUT]);
    }

    private function processAuthentication(string $login, string $password): TokenResult
    {
        $client = $this->buildClientHttp();

        $response = $client->post(self::AUTH_URL, [
            "form_params" => ([
                "_cm_user" => $login,
                "_cm_pwd" => $password,
                "flag" => "password"
            ])
        ]);

        if ($response->getStatusCode() == 302) {
            return $this->processLoginSuccess($client, $login, $password);
        } else {
            return $this->processLoginFailed($response);
        }
    }

    private function processTransactionState(string $transactionId): TokenResult
    {
        $client = $this->buildClientHttp();

        $response = $client->post(self::OTP_TRANSACTION_STATE_URL, [
            "form_params" => ([
                "transactionId" => $transactionId
            ])
        ]);

        $xml = new DOMDocument();
        $xml->loadXML($response->getBody());
        $status = $xml->getElementsByTagName("transactionState")->item(0)->textContent;

        $token = new TokenResult();
        if ($status == "PENDING") {
            $token->message = "En attente de votre validation...";
        }
        return $token;
    }

    private function processLoginSuccess(Client $client, string $login, string $password): TokenResult
    {
        $response = $client->get(self::VALIDATION_URL);
        $htmlString = (string)$response->getBody();

        $matches = [];
        preg_match("/.*transactionId: '(.+)'/", $htmlString, $matches);
        $transactionId = $matches[1];

        $html = new DOMDocument();
        $html->loadHTML($htmlString, LIBXML_NOWARNING | LIBXML_NOERROR);
        $message = $html->getElementById("inMobileAppMessage")->textContent;
        $message = trim($message);
        $message = str_replace("  ", "", $message);

        $tokenJson = json_encode([
            "bankId" => $this->getBank()->id,
            self::LOGIN_INPUT => $login,
            self::PASSWORD_INPUT => $password,
            self::TRANSACTION_ID_INPUT => $transactionId
        ]);

        $token = new TokenResult();
        $token->token = $this->cryptoService->encrypt($tokenJson);
        $token->completedToken = false;
        $token->message = $message;
        return $token;
    }

    private function processLoginFailed(ResponseInterface $response): TokenResult
    {
        $html = new DOMDocument();
        $html->loadHTML($response->getBody(), LIBXML_NOWARNING | LIBXML_NOERROR);
        $identDivs = $html->getElementById("ident")->getElementsByTagName("div");
        $errorMessage = null;
        foreach ($identDivs as $identDiv) {
            $cssClasses = preg_split("/ /", $identDiv->getAttribute("class"));
            if (in_array("err", $cssClasses)) {
                $errorMessage = $identDiv->textContent;
                break;
            }
        }

        $token = new TokenResult();
        $token->message = $errorMessage;
        return $token;
    }

    private function buildClientHttp()
    {
        return new Client([
            "base_uri" => self::BASE_URL,
            "allow_redirects" => false,
            "cookies" => true,
            "handler" => $this->handlerStack
        ]);
    }

    public function fetchTransactions(string $accountId): array
    {
        return [];
    }
}
