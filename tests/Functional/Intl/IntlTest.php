<?php


namespace Tests\Functional\Intl;


use ProxyBank\Services\IntlService;
use Tests\FunctionalTestCase;

class IntlTest extends FunctionalTestCase
{

    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->requestFactory->createServerRequest("GET", "/bank/list");
    }

    /**
     * @test
     */
    public function intl_middleware_should_add_in_the_container_the_correct_intlService_from_accept_language_header()
    {
        $this->app->handle($this->request->withHeader("Accept-Language", "fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5"));
        $intlService = $this->container->get(IntlService::class);
        $this->assertEquals("fr_CH", $intlService->locale);
    }

    /**
     * @test
     */
    public function intl_middleware_should_add_in_the_container_the_english_intlService_if_no_accept_language_header()
    {
        $this->app->handle($this->request);
        $intlService = $this->container->get(IntlService::class);
        $this->assertEquals("en_US", $intlService->locale);
    }

    /**
     * @test
     */
    public function intl_middleware_should_add_in_the_container_the_english_intlService_if_accept_language_header_has_unknown_locales()
    {
        $this->app->handle($this->request->withHeader("Accept-Language", "unknown"));
        $intlService = $this->container->get(IntlService::class);
        $this->assertEquals("en_US", $intlService->locale);
    }
}
