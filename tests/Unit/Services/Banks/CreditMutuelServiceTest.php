<?php


namespace Tests\Unit\Services\Banks;


use DateTime;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ProxyBank\Exceptions\AuthenticationException;
use ProxyBank\Exceptions\DSP2TokenExpiredOrInvalidException;
use ProxyBank\Exceptions\RequiredValueException;
use ProxyBank\Exceptions\UnknownAccountIdException;
use ProxyBank\Models\Account;
use ProxyBank\Models\Input;
use ProxyBank\Models\TokenResult;
use ProxyBank\Models\Transaction;
use ProxyBank\Services\Banks\CreditMutuelService;
use ProxyBank\Services\CryptoService;

class CreditMutuelServiceTest extends TestCase
{
    /**
     * @var Response
     */
    private $RESPONSE_LOGIN_SUCCESS_WITHOUT_DSP2_TOKEN;

    /**
     * @var Response
     */
    private $RESPONSE_LOGIN_SUCCESS_WITH_DSP2_TOKEN;
    /**
     * @var Response
     */
    private $RESPONSE_LOGIN_FAILED;
    /**
     * @var Response
     */
    private $RESPONSE_OTP_TRANSACTION_STATE_PENDING;
    /**
     * @var Response
     */
    private $RESPONSE_OTP_TRANSACTION_STATE_CANCELLED;
    /**
     * @var Response
     */
    private $RESPONSE_OTP_TRANSACTION_STATE_VALIDATED;
    /**
     * @var Response
     */
    private $RESPONSE_VALIDATION_TRANSACTION_STATE_PENDING;
    /**
     * @var Response
     */
    private $RESPONSE_VALIDATION_TRANSACTION_STATE_VALIDATED;

    /**
     * @var Response
     */
    private $RESPONSE_DOWNLOAD_PAGE;

    /**
     * @var Response
     */
    private $RESPONSE_DOWNLOAD_CSV;

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

        $this->RESPONSE_LOGIN_SUCCESS_WITHOUT_DSP2_TOKEN = new Response(302, [
            "Set-Cookie" => "IdSes=token; Path=/; Secure",
            "Location" => "https://www.creditmutuel.fr/fr/banque/validation.aspx"
        ]);
        $this->RESPONSE_LOGIN_SUCCESS_WITH_DSP2_TOKEN = new Response(302, [
            "Set-Cookie" => "IdSes=token; Path=/; Secure",
            "Location" => "https://www.creditmutuel.fr/fr/banque/pageaccueil.html"
        ]);
        $this->RESPONSE_LOGIN_FAILED = new Response(200, [], "
<html>
<head><meta charset=\"UTF-8\"/></head>
<body>
<div id=\"ident\">
<div class=\"blocmsg err\"><p>Votre identifiant est inconnu ou votre mot de passe est faux. Veuillez réessayer en corrigeant votre saisie</p></div>
</body>
</html>
            ");
        $this->RESPONSE_OTP_TRANSACTION_STATE_PENDING = new Response(200, [],
            "<root><code_retour>0000</code_retour><transactionState>PENDING</transactionState></root>");
        $this->RESPONSE_OTP_TRANSACTION_STATE_CANCELLED = new Response(200, [],
            "<root><code_retour>0000</code_retour><transactionState>CANCELLED</transactionState></root>");
        $this->RESPONSE_OTP_TRANSACTION_STATE_VALIDATED = new Response(200, [],
            "<root><code_retour>0000</code_retour><transactionState>VALIDATED</transactionState></root>");
        $this->RESPONSE_VALIDATION_TRANSACTION_STATE_PENDING = new Response(200, [], "
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
            ");
        $this->RESPONSE_VALIDATION_TRANSACTION_STATE_VALIDATED = new Response(302, ["Set-Cookie" => "auth_client_state=anAuthClientStateToken"]);
        $this->RESPONSE_DOWNLOAD_PAGE = new Response(200, [], "
<html>
<head><meta charset=\"UTF-8\"/></head>
<body>
<form id=\"P:F\" action=\"/fr/banque/compte/telechargement.cgi?withParameters=true\">
<table id=\"account-table\">
<tbody>
<tr><td><input id=\"F_0.accountCheckbox:DataEntry\" name=\"CB:data_accounts_account_ischecked\" type=\"checkbox\"/></td><td><label for=\"F_0.accountCheckbox:DataEntry\">36025 000123456 01 COMPTE CHEQUE EUROCOMPTE M T TEST</label></td></tr>
<tr><td><input id=\"F_1.accountCheckbox:DataEntry\" name=\"CB:data_accounts_account_2__ischecked\" type=\"checkbox\"/></td><td><label for=\"F_1.accountCheckbox:DataEntry\">36025 000123456 02 COMPTE CHEQUE EUROCOMPTE MME M TEST</label></td></tr>
<tr><td><input id=\"F_2.accountCheckbox:DataEntry\" name=\"CB:data_accounts_account_3__ischecked\" type=\"checkbox\"/></td><td><label for=\"F_2.accountCheckbox:DataEntry\">36025 000123456 03 LIVRET BLEU EUROCOMPTE M T TEST</label></td></tr>
</tbody>
</table>
</form>
</body>
</html>
            ");
        $this->RESPONSE_DOWNLOAD_CSV = new Response(200, [], "Date;Date de valeur;Montant;Libellé;Solde
02/04/2020;02/04/2020;1102.00;VIR DE M T TEST;2125.03
08/04/2020;01/04/2020;-8.46;F COTIS EUROCOMPTE;2116.57
09/04/2020;09/04/2020;-176.47;PAIEMENT CB 0704 CENTRE LECLERC CARTE 123456;1940.10");
    }

    private function scenario_get_auth_token_with_otp_transaction_validated(): TokenResult
    {
        $this->mockResponses->append(
            $this->RESPONSE_OTP_TRANSACTION_STATE_VALIDATED,
            $this->RESPONSE_VALIDATION_TRANSACTION_STATE_VALIDATED
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

    private function scenario_get_auth_token_with_otp_transaction_pending(): TokenResult
    {
        $this->mockResponses->append(
            $this->RESPONSE_OTP_TRANSACTION_STATE_PENDING
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

    private function scenario_get_auth_token_with_otp_transaction_cancelled(): TokenResult
    {
        $this->mockResponses->append(
            $this->RESPONSE_OTP_TRANSACTION_STATE_CANCELLED
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

    private function scenario_get_auth_token_with_login_failed(): TokenResult
    {
        $this->mockResponses->append(
            $this->RESPONSE_LOGIN_FAILED
        );

        return $this->service->getAuthToken([
            "Login" => "myLogin",
            "Password" => "myPassword"
        ]);
    }

    private function scenario_get_auth_token_with_login_success(): TokenResult
    {
        $this->mockResponses->append(
            $this->RESPONSE_LOGIN_SUCCESS_WITHOUT_DSP2_TOKEN,
            $this->RESPONSE_VALIDATION_TRANSACTION_STATE_PENDING
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

    private function scenario_list_accounts_with_login_failed(): array
    {
        $this->mockResponses->append(
            $this->RESPONSE_LOGIN_FAILED
        );

        return $this->service->listAccounts([
            "Login" => "myLogin",
            "Password" => "myPassword"
        ]);
    }

    private function scenario_list_accounts_with_login_success(): array
    {
        $this->mockResponses->append(
            $this->RESPONSE_LOGIN_SUCCESS_WITH_DSP2_TOKEN,
            $this->RESPONSE_DOWNLOAD_PAGE
        );

        return $this->service->listAccounts([
            "Login" => "myLogin",
            "Password" => "myPassword",
            "auth_client_state" => "anAuthClientStateToken"
        ]);
    }

    private function scenario_list_accounts_with_login_success_and_dsp2_token_expired_or_invalid(): array
    {
        $this->mockResponses->append(
            $this->RESPONSE_LOGIN_SUCCESS_WITHOUT_DSP2_TOKEN,
            $this->RESPONSE_DOWNLOAD_PAGE
        );

        return $this->service->listAccounts([
            "Login" => "myLogin",
            "Password" => "myPassword",
            "auth_client_state" => "anAuthClientStateToken"
        ]);
    }

    private function scenario_fetch_transactions_with_login_failed(): array
    {
        $this->mockResponses->append(
            $this->RESPONSE_LOGIN_FAILED
        );

        return $this->service->fetchTransactions("3602500012345602", [
            "Login" => "myLogin",
            "Password" => "myPassword",
            "auth_client_state" => "anAuthClientStateToken"
        ]);
    }

    private function scenario_fetch_transactions_with_login_success_and_dsp2_token_expired_or_invalid(string $accountId = "3602500012345602"): array
    {
        $this->mockResponses->append(
            $this->RESPONSE_LOGIN_SUCCESS_WITHOUT_DSP2_TOKEN,
            $this->RESPONSE_DOWNLOAD_PAGE,
            $this->RESPONSE_DOWNLOAD_CSV
        );

        return $this->service->fetchTransactions($accountId, [
            "Login" => "myLogin",
            "Password" => "myPassword",
            "auth_client_state" => "anAuthClientStateToken"
        ]);
    }

    private function scenario_fetch_transactions_with_login_success(string $accountId = "3602500012345602"): array
    {
        $this->mockResponses->append(
            $this->RESPONSE_LOGIN_SUCCESS_WITH_DSP2_TOKEN,
            $this->RESPONSE_DOWNLOAD_PAGE,
            $this->RESPONSE_DOWNLOAD_CSV
        );

        return $this->service->fetchTransactions($accountId, [
            "Login" => "myLogin",
            "Password" => "myPassword",
            "auth_client_state" => "anAuthClientStateToken"
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
    public function getAuthToken_should_throw_RequiredValueException_when_no_login()
    {
        try {
            $this->service->getAuthToken([]);
            $this->fail("RequiredValueException is expected");
        } catch (RequiredValueException $e) {
            $this->assertEquals(["Login"], $e->messageFormatterArgs);
        }
    }

    /**
     * @test
     */
    public function getAuthToken_should_throw_RequiredValueException_message_when_no_password()
    {
        try {
            $this->service->getAuthToken([
                "Login" => "myLogin"
            ]);
            $this->fail("RequiredValueException is required");
        } catch (RequiredValueException $e) {
            $this->assertEquals(["Password"], $e->messageFormatterArgs);
        }
    }

    /**
     * @test
     */
    public function getAuthToken_should_post_login_password_to_auth_url()
    {
        $this->scenario_get_auth_token_with_login_success();

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
        $this->scenario_get_auth_token_with_login_success();

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
        $token = $this->scenario_get_auth_token_with_login_success();

        $this->assertEquals("encryptedToken", $token->token);
        $this->assertFalse($token->completedToken);
        $this->assertEquals("Votre connexion nécessite une sécurisation. Démarrez votre application mobile Crédit Mutuel depuis votre appareil \"MOTO G (5S) DE M MAXIME FALAIZE\" pour vérifier et confirmer votre identité.", $token->message);
    }

    /**
     * @test
     */
    public function getAuthToken_should_throw_AuthenticationException_if_login_failed()
    {
        try {
            $this->scenario_get_auth_token_with_login_failed();
            $this->fail("AuthenticationException is expected");
        } catch (AuthenticationException $e) {
            $this->assertEquals(["Votre identifiant est inconnu ou votre mot de passe est faux. Veuillez réessayer en corrigeant votre saisie"], $e->messageFormatterArgs);
        }
    }

    /**
     * @test
     */
    public function getAuthToken_should_request_otp_transaction_url_to_know_transaction_status()
    {
        $this->scenario_get_auth_token_with_otp_transaction_pending();

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
        $token = $this->scenario_get_auth_token_with_otp_transaction_pending();

        $this->assertNull($token->token);
        $this->assertNull($token->completedToken);
        $this->assertEquals("En attente de votre validation...", $token->message);
    }

    /**
     * @test
     */
    public function getAuthToken_should_return_message_if_transaction_is_cancelled()
    {
        $token = $this->scenario_get_auth_token_with_otp_transaction_cancelled();

        $this->assertNull($token->token);
        $this->assertNull($token->completedToken);
        $this->assertEquals("La transaction a été annulée", $token->message);
    }

    /**
     * @test
     */
    public function getAuthToken_should_request_validation_url_with_otp_hidden_body_if_transaction_is_validated()
    {
        $this->scenario_get_auth_token_with_otp_transaction_validated();

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
        $token = $this->scenario_get_auth_token_with_otp_transaction_validated();

        $this->assertEquals("aCompleteEncryptedToken", $token->token);
        $this->assertTrue($token->completedToken);
        $this->assertNull($token->message);
    }

    /**
     * @test
     */
    public function listAccounts_should_throw_AuthenticationException_if_login_failed()
    {
        try {
            $this->scenario_list_accounts_with_login_failed();
            $this->fail("AuthenticationException is expected");
        } catch (AuthenticationException $e) {
            $this->assertEquals(["Votre identifiant est inconnu ou votre mot de passe est faux. Veuillez réessayer en corrigeant votre saisie"], $e->messageFormatterArgs);
        }
    }

    /**
     * @test
     */
    public function listAccounts_should_throw_DSP2TokenExpiredOrInvalidException_if_dsp2_token_is_invalid_or_expired()
    {
        try {
            $this->scenario_list_accounts_with_login_success_and_dsp2_token_expired_or_invalid();
            $this->fail("DSP2TokenExpiredOrInvalidException is expected");
        } catch (DSP2TokenExpiredOrInvalidException $e) {
            $this->assertEquals(["Crédit Mutuel"], $e->messageFormatterArgs);
        }
    }

    /**
     * @test
     */
    public function listAccounts_should_return_accounts_list_from_download_page()
    {
        $accounts = $this->scenario_list_accounts_with_login_success();

        $request1 = $this->transactionsHistory[0]["request"];
        $request2 = $this->transactionsHistory[1]["request"];

        // Authenticate first with dsp2 token
        $this->assertEquals("POST", $request1->getMethod());
        $this->assertEquals("https://www.creditmutuel.fr/fr/authentification.html", (string)$request1->getUri());
        $this->assertEquals("application/x-www-form-urlencoded", $request1->getHeaderLine("Content-Type"));
        $this->assertEquals("_cm_user=myLogin&_cm_pwd=myPassword&flag=password", (string)$request1->getBody());
        $this->assertEquals("auth_client_state=anAuthClientStateToken", $request1->getHeaderLine("Cookie"));

        // Then get download page
        $this->assertEquals("GET", $request2->getMethod());
        $this->assertEquals("https://www.creditmutuel.fr/fr/banque/compte/telechargement.cgi", (string)$request2->getUri());

        $this->assertInstanceOf(Account::class, $accounts[0]);
        $this->assertEquals(3, sizeof($accounts));
        $this->assertEquals("3602500012345601", $accounts[0]->id);
        $this->assertEquals("COMPTE CHEQUE EUROCOMPTE M T TEST", $accounts[0]->name);
    }

    /**
     * @test
     */
    public function fetchTransactions_should_throw_AuthenticationException_if_login_failed()
    {
        try {
            $this->scenario_fetch_transactions_with_login_failed();
            $this->fail("AuthenticationException is expected");
        } catch (AuthenticationException $e) {
            $this->assertEquals(["Votre identifiant est inconnu ou votre mot de passe est faux. Veuillez réessayer en corrigeant votre saisie"], $e->messageFormatterArgs);
        }
    }

    /**
     * @test
     */
    public function fetchTransactions_should_throw_DSP2TokenExpiredOrInvalidException_if_dsp2_token_is_invalid_or_expired()
    {
        try {
            $this->scenario_fetch_transactions_with_login_success_and_dsp2_token_expired_or_invalid();
            $this->fail("DSP2TokenExpiredOrInvalidException is expected");
        } catch (DSP2TokenExpiredOrInvalidException $e) {
            $this->assertEquals(["Crédit Mutuel"], $e->messageFormatterArgs);
        }
    }

    /**
     * @test
     */
    public function fetchTransactions_should_throw_UnknownAccountIdException_if_accountId_is_unknown()
    {
        try {
            $this->scenario_fetch_transactions_with_login_success("123456");
            $this->fail("UnknownAccountIdException is expected");
        } catch (UnknownAccountIdException $e) {
            $this->assertEquals(["123456"], $e->messageFormatterArgs);
        }
    }

    /**
     * @test
     */
    public function fetchTransactions_should_return_last_transactions_list_parsed_from_csv_downloaded_file()
    {
        $transactions = $this->scenario_fetch_transactions_with_login_success();

        $request1 = $this->transactionsHistory[0]["request"];
        $request2 = $this->transactionsHistory[1]["request"];
        $request3 = $this->transactionsHistory[2]["request"];

        // Authenticate first with dsp2 token
        $this->assertEquals("POST", $request1->getMethod());
        $this->assertEquals("https://www.creditmutuel.fr/fr/authentification.html", (string)$request1->getUri());
        $this->assertEquals("application/x-www-form-urlencoded", $request1->getHeaderLine("Content-Type"));
        $this->assertEquals("_cm_user=myLogin&_cm_pwd=myPassword&flag=password", (string)$request1->getBody());
        $this->assertEquals("auth_client_state=anAuthClientStateToken", $request1->getHeaderLine("Cookie"));

        // Then get download page
        $this->assertEquals("GET", $request2->getMethod());
        $this->assertEquals("https://www.creditmutuel.fr/fr/banque/compte/telechargement.cgi", (string)$request2->getUri());

        // Then download csv transactions file
        $this->assertEquals("POST", $request3->getMethod());
        $this->assertEquals("https://www.creditmutuel.fr/fr/banque/compte/telechargement.cgi?withParameters=true", (string)$request3->getUri());
        $this->assertEquals("data_formats_selected=csv&data_formats_options_csv_fileformat=2&data_formats_options_csv_dateformat=0&data_formats_options_csv_fieldseparator=0&data_formats_options_csv_amountcolnumber=0&data_formats_options_csv_decimalseparator=1&CB%3Adata_accounts_account_2__ischecked=on&_FID_DoDownload.x=0&_FID_DoValidate.y=0", (string)$request3->getBody());

        $this->assertInstanceOf(Transaction::class, $transactions[0]);
        $this->assertEquals(3, sizeof($transactions));

        $this->assertEquals("VIR DE M T TEST", $transactions[0]->description);
        $this->assertEquals(new DateTime("2020-04-02"), $transactions[0]->date);
        $this->assertEquals("1102.00", $transactions[0]->amount);
        $this->assertEquals("2125.03", $transactions[0]->accountBalance);

        $this->assertEquals("F COTIS EUROCOMPTE", $transactions[1]->description);
        $this->assertEquals(new DateTime("2020-04-08"), $transactions[1]->date);
        $this->assertEquals("-8.46", $transactions[1]->amount);
        $this->assertEquals("2116.57", $transactions[1]->accountBalance);

        $this->assertEquals("PAIEMENT CB 0704 CENTRE LECLERC CARTE 123456", $transactions[2]->description);
        $this->assertEquals(new DateTime("2020-04-09"), $transactions[2]->date);
        $this->assertEquals("-176.47", $transactions[2]->amount);
        $this->assertEquals("1940.10", $transactions[2]->accountBalance);
    }
}
