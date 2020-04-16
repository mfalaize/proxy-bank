<?php


namespace Tests\Functional\Intl;


use ProxyBank\Controllers\BankController;
use ProxyBank\Helpers\IntlHelper;
use ProxyBank\Middlewares\IntlMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tests\FunctionalTestCase;

class IntlTest extends FunctionalTestCase
{

    private $request;

    /**
     * @var IntlHelper
     */
    private $intl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->requestFactory->createServerRequest("GET", "/bank/list");
        $controller = $this->createMock(BankController::class);
        $this->container->set(BankController::class, $controller);

        $controller->expects($this->atLeastOnce())
            ->method("listBanks")
            ->willReturnCallback(function (ServerRequestInterface $request, ResponseInterface $response) {
                $this->intl = $request->getAttribute(IntlMiddleware::REQUEST_INTL);
                return $response;
            });
    }


    /**
     * @test
     */
    public function intl_middleware_should_add_in_the_request_the_correct_resource_bundle_from_accept_language_header()
    {
        $this->app->handle($this->request->withHeader("Accept-Language", "fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5"));
        $this->assertEquals("fr_CH", (string)$this->intl);
    }

    /**
     * @test
     */
    public function intl_middleware_should_add_in_the_request_the_english_resource_bundle_if_no_accept_language_header()
    {
        $this->app->handle($this->request);
        $this->assertEquals("en_US", (string)$this->intl);
    }

    /**
     * @test
     */
    public function intl_middleware_should_add_in_the_request_the_english_resource_bundle_if_accept_language_header_has_unknown_locales()
    {
        $this->app->handle($this->request->withHeader("Accept-Language", "unknown"));
        $this->assertEquals("en_US", (string)$this->intl);
    }
}
