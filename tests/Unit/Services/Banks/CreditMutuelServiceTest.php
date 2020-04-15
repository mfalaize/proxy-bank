<?php


namespace Tests\Unit\Services\Banks;


use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ProxyBank\Models\Input;
use ProxyBank\Models\TokenResult;
use ProxyBank\Services\Banks\CreditMutuelService;
use ProxyBank\Services\CryptoService;
use Psr\Container\ContainerInterface;

class CreditMutuelServiceTest extends TestCase
{
    private $service;

    private $mockResponses;
    private $transactionsHistory;
    private $cryptoService;

    protected function setUp(): void
    {
        $this->cryptoService = $this->createMock(CryptoService::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->atLeastOnce())
            ->method("get")
            ->with(CryptoService::class)
            ->willReturn($this->cryptoService);

        $this->service = new CreditMutuelService($container);

        $this->mockResponses = new MockHandler();
        $this->service->handlerStack = HandlerStack::create($this->mockResponses);
        $this->transactionsHistory = [];
        $this->service->handlerStack->push(Middleware::history($this->transactionsHistory));
    }

    private function scenario_login_failed(): TokenResult
    {
        $this->mockResponses->append(
            new Response(200, [], "
<html>
<head><meta charset=\"UTF-8\"/></head>
<body>
<div id=\"ident\">
<div class=\"blocmsg err\"><p>Votre identifiant est inconnu ou votre mot de passe est faux. Veuillez réessayer en corrigeant votre saisie</p></div>
</body>
</html>
            ") // login failed
        );

        return $this->service->getAuthToken([
            "Login" => "myLogin",
            "Password" => "myPassword"
        ]);
    }

    private function scenario_login_success(): TokenResult
    {
        $this->mockResponses->append(
            new Response(302, ["Set-Cookie" => "IdSes=token; Path=/; Secure"]), // login success
            new Response(200, [], "
<html>
<head><meta charset=\"UTF-8\"/></head>
<body>
<script type=\"text/javascript\">
var otpInMobileAppParameters = {
	transactionId: 'aTransactionId'
};
</script>
<div id=\"inMobileAppMessage\"> <h2 class=\"c otpSecuringNeeded\"> <img src=\"#\"> Votre connexion nécessite une sécurisation. </h2> <br/> <h2 class=\"c otpFontSizeIncreased\">Démarrez votre application mobile Crédit Mutuel depuis votre appareil \"<strong>MOTO G (5S) DE M MAXIME FALAIZE</strong>\" pour vérifier et confirmer votre identité.</h2><br> <br> <img src=\"#\"></div>
</body>
</html>
            ") // get validation page
        );

        $this->cryptoService->expects($this->atLeastOnce())
            ->method("encrypt")
            ->with(json_encode([
                "bankId" => "credit-mutuel",
                "Login" => "myLogin",
                "Password" => "myPassword",
                "transactionId" => "aTransactionId"
            ]))
            ->willReturn("encryptedToken");

        return $this->service->getAuthToken([
            "Login" => "myLogin",
            "Password" => "myPassword"
        ]);
    }

    /**
     * @test
     */
    public function getBank_should_return_config_for_the_bank()
    {
        $bank = $this->service->getBank();
        $this->assertEquals("credit-mutuel", $bank->id); // Should NEVER change
        $this->assertEquals("Crédit Mutuel", $bank->name);
        $this->assertEquals("Login", $bank->authInputs[0]->name);
        $this->assertEquals(Input::TYPE_TEXT, $bank->authInputs[0]->type);
        $this->assertEquals("Password", $bank->authInputs[1]->name);
        $this->assertEquals(Input::TYPE_PASSWORD, $bank->authInputs[1]->type);
        $this->assertEquals(2, sizeof($bank->authInputs));
    }

    /**
     * @test
     */
    public function getAuthToken_should_return_error_message_when_no_login()
    {
        $token = $this->service->getAuthToken([]);
        $this->assertNull($token->token);
        $this->assertNull($token->completedToken);
        $this->assertEquals("Login value is required", $token->message);
    }

    /**
     * @test
     */
    public function getAuthToken_should_return_error_message_when_no_password()
    {
        $token = $this->service->getAuthToken([
            "Login" => "myLogin"
        ]);
        $this->assertNull($token->token);
        $this->assertNull($token->completedToken);
        $this->assertEquals("Password value is required", $token->message);
    }

    /**
     * @test
     */
    public function getAuthToken_should_post_login_password_to_auth_url()
    {
        $this->scenario_login_success();

        $request1 = $this->transactionsHistory[0]["request"];
        $this->assertEquals("POST", $request1->getMethod());
        $this->assertEquals("https://www.creditmutuel.fr/fr/authentification.html", (string)$request1->getUri());
        $this->assertEquals("application/x-www-form-urlencoded", $request1->getHeaderLine("Content-Type"));
        $this->assertEquals("_cm_user=myLogin&_cm_pwd=myPassword&flag=password", (string)$request1->getBody());
    }

    /**
     * @test
     */
    public function getAuthToken_should_request_validation_page_after_login_success_and_no_dsp2_cookie()
    {
        $this->scenario_login_success();

        $request2 = $this->transactionsHistory[1]["request"];
        $this->assertEquals("GET", $request2->getMethod());
        $this->assertEquals("https://www.creditmutuel.fr/fr/banque/validation.aspx", (string)$request2->getUri());
        $this->assertEquals("IdSes=token", $request2->getHeaderLine("Cookie"));
    }

    /**
     * @test
     */
    public function getAuthToken_should_return_encrypted_token_with_transactionId_from_validation_page_when_no_dsp2_cookie()
    {
        $token = $this->scenario_login_success();

        $this->assertEquals("encryptedToken", $token->token);
        $this->assertFalse($token->completedToken);
        $this->assertEquals("Votre connexion nécessite une sécurisation. Démarrez votre application mobile Crédit Mutuel depuis votre appareil \"MOTO G (5S) DE M MAXIME FALAIZE\" pour vérifier et confirmer votre identité.", $token->message);
    }

    /**
     * @test
     */
    public function getAuthToken_should_return_error_message_if_login_failed()
    {
        $token = $this->scenario_login_failed();

        $this->assertNull($token->token);
        $this->assertNull($token->completedToken);
        $this->assertEquals("Votre identifiant est inconnu ou votre mot de passe est faux. Veuillez réessayer en corrigeant votre saisie", $token->message);
    }
}
