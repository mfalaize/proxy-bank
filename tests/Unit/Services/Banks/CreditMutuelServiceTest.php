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

class CreditMutuelServiceTest extends TestCase
{
    private $service;

    private $mockResponses;
    private $transactionsHistory;
    private $cryptoService;

    protected function setUp(): void
    {
        $this->cryptoService = $this->createMock(CryptoService::class);
        $this->service = new CreditMutuelService($this->cryptoService);

        $this->mockResponses = new MockHandler();
        $this->service->handlerStack = HandlerStack::create($this->mockResponses);
        $this->transactionsHistory = [];
        $this->service->handlerStack->push(Middleware::history($this->transactionsHistory));
    }

    private function scenario_token_with_transactionId_and_transaction_validated(): TokenResult
    {
        $this->mockResponses->append(
            new Response(200, [],
                "<root><code_retour>0000</code_retour><transactionState>VALIDATED</transactionState></root>"),
            new Response(302, ["Set-Cookie" => "auth_client_state=anAuthClientStateToken"])
        );

        $this->cryptoService->expects($this->atLeastOnce())
            ->method("encrypt")
            ->with(json_encode([
                "Login" => "myLogin",
                "Password" => "myPassword",
                "auth_client_state" => "anAuthClientStateToken"
            ]))
            ->willReturn("aCompleteEncryptedToken");

        return $this->service->getAuthToken([
            "Login" => "myLogin",
            "Password" => "myPassword",
            "transactionId" => "aTransactionId",
            "validationUrl" => "https://www.creditmutuel.fr/avalidationurl",
            "otp_hidden" => "anOTPHiddenToken",
            "cookies" => [
                [
                    "Name" => "IdSes",
                    "Value" => "sessionId",
                    "Domain" => "www.creditmutuel.fr",
                    "Path" => "/",
                    "Max-Age" => null,
                    "Expires" => null,
                    "Secure" => true,
                    "Discard" => false,
                    "HttpOnly" => false
                ]
            ]
        ]);
    }

    private function scenario_token_with_transactionId_and_transaction_pending(): TokenResult
    {
        $this->mockResponses->append(
            new Response(200, [], "<root><code_retour>0000</code_retour><transactionState>PENDING</transactionState></root>")
        );

        return $this->service->getAuthToken([
            "Login" => "myLogin",
            "Password" => "myPassword",
            "transactionId" => "aTransactionId",
            "validationUrl" => "https://www.creditmutuel.fr/avalidationurl",
            "otp_hidden" => "anOTPHiddenToken",
            "cookies" => [
                [
                    "Name" => "IdSes",
                    "Value" => "sessionId",
                    "Domain" => "www.creditmutuel.fr",
                    "Path" => "/",
                    "Max-Age" => null,
                    "Expires" => null,
                    "Secure" => true,
                    "Discard" => false,
                    "HttpOnly" => false
                ]
            ]
        ]);
    }

    private function scenario_token_with_transactionId_and_transaction_cancelled(): TokenResult
    {
        $this->mockResponses->append(
            new Response(200, [], "<root><code_retour>0000</code_retour><transactionState>CANCELLED</transactionState></root>")
        );

        return $this->service->getAuthToken([
            "Login" => "myLogin",
            "Password" => "myPassword",
            "transactionId" => "aTransactionId",
            "validationUrl" => "https://www.creditmutuel.fr/avalidationurl",
            "otp_hidden" => "anOTPHiddenToken",
            "cookies" => [
                [
                    "Name" => "IdSes",
                    "Value" => "sessionId",
                    "Domain" => "www.creditmutuel.fr",
                    "Path" => "/",
                    "Max-Age" => null,
                    "Expires" => null,
                    "Secure" => true,
                    "Discard" => false,
                    "HttpOnly" => false
                ]
            ]
        ]);
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
<form id=\"C:P:F\" action=\"/fr/banque/validation.aspx?_tabi=C&amp;_pid=OtpValidationPage&amp;k___ValidateAntiForgeryToken=aCsrfToken\">
<input type=\"hidden\" name=\"otp_hidden\" value=\"anOtpHiddenToken\"/><script type=\"text/javascript\">
var otpInMobileAppParameters = {
	transactionId: 'aTransactionId'
};
</script>
<div id=\"inMobileAppMessage\"> <h2 class=\"c otpSecuringNeeded\"> <img src=\"#\"> Votre connexion nécessite une sécurisation. </h2> <br/> <h2 class=\"c otpFontSizeIncreased\">Démarrez votre application mobile Crédit Mutuel depuis votre appareil \"<strong>MOTO G (5S) DE M MAXIME FALAIZE</strong>\" pour vérifier et confirmer votre identité.</h2><br> <br> <img src=\"#\"></div>
</form>
</body>
</html>
            ") // get validation page
        );

        $this->cryptoService->expects($this->atLeastOnce())
            ->method("encrypt")
            ->with(json_encode([
                "Login" => "myLogin",
                "Password" => "myPassword",
                "transactionId" => "aTransactionId",
                "validationUrl" => "/fr/banque/validation.aspx?_tabi=C&_pid=OtpValidationPage&k___ValidateAntiForgeryToken=aCsrfToken",
                "otp_hidden" => "anOtpHiddenToken",
                "cookies" => [
                    [
                        "Name" => "IdSes",
                        "Value" => "token",
                        "Domain" => "www.creditmutuel.fr",
                        "Path" => "/",
                        "Max-Age" => null,
                        "Expires" => null,
                        "Secure" => true,
                        "Discard" => false,
                        "HttpOnly" => false
                    ]
                ]
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

    /**
     * @test
     */
    public function getAuthToken_should_request_otp_transaction_url_to_know_transaction_status()
    {
        $this->scenario_token_with_transactionId_and_transaction_pending();

        $request1 = $this->transactionsHistory[0]["request"];
        $this->assertEquals("POST", $request1->getMethod());
        $this->assertEquals("https://www.creditmutuel.fr/fr/banque/async/otp/SOSD_OTP_GetTransactionState.htm", (string)$request1->getUri());
        $this->assertEquals("application/x-www-form-urlencoded", $request1->getHeaderLine("Content-Type"));
        $this->assertEquals("transactionId=aTransactionId", (string)$request1->getBody());
        $this->assertEquals("IdSes=sessionId", $request1->getHeaderLine("Cookie"));
    }

    /**
     * @test
     */
    public function getAuthToken_should_return_message_if_transaction_is_pending()
    {
        $token = $this->scenario_token_with_transactionId_and_transaction_pending();

        $this->assertNull($token->token);
        $this->assertNull($token->completedToken);
        $this->assertEquals("En attente de votre validation...", $token->message);
    }

    /**
     * @test
     */
    public function getAuthToken_should_return_message_if_transaction_is_cancelled()
    {
        $token = $this->scenario_token_with_transactionId_and_transaction_cancelled();

        $this->assertNull($token->token);
        $this->assertNull($token->completedToken);
        $this->assertEquals("La transaction a été annulée", $token->message);
    }

    /**
     * @test
     */
    public function getAuthToken_should_request_validation_url_with_otp_hidden_body_if_transaction_is_validated()
    {
        $this->scenario_token_with_transactionId_and_transaction_validated();

        $request2 = $this->transactionsHistory[1]["request"];
        $this->assertEquals("POST", $request2->getMethod());
        $this->assertEquals("https://www.creditmutuel.fr/avalidationurl", (string)$request2->getUri());
        $this->assertEquals("application/x-www-form-urlencoded", $request2->getHeaderLine("Content-Type"));
        $this->assertEquals("otp_hidden=anOTPHiddenToken&_FID_DoValidate.x=0&_FID_DoValidate.y=0", (string)$request2->getBody());
        $this->assertEquals("IdSes=sessionId", $request2->getHeaderLine("Cookie"));
    }

    /**
     * @test
     */
    public function getAuthToken_should_return_complete_token_if_transaction_is_validated()
    {
        $token = $this->scenario_token_with_transactionId_and_transaction_validated();

        $this->assertEquals("aCompleteEncryptedToken", $token->token);
        $this->assertTrue($token->completedToken);
        $this->assertNull($token->message);
    }
}
