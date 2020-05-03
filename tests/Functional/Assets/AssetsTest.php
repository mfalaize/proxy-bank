<?php


namespace Tests\Functional\Assets;


use Tests\FunctionalTestCase;

class AssetsTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function assets_url_should_return_css_file_contents()
    {
        $request = $this->requestFactory->createServerRequest("GET", "/assets/swagger-ui/swagger-ui.css");
        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("text/css", $response->getHeaderLine("Content-Type"));
        $this->assertStringContainsString(".swagger-ui{", $response->getBody());
    }

    /**
     * @test
     */
    public function assets_url_should_return_js_file_contents()
    {
        $request = $this->requestFactory->createServerRequest("GET", "/assets/swagger-ui/swagger-ui-bundle.js");
        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("text/javascript", $response->getHeaderLine("Content-Type"));
        $this->assertStringContainsString("!function(e,t)", $response->getBody());
    }

    /**
     * @test
     */
    public function assets_url_should_return_png_file_contents()
    {
        $request = $this->requestFactory->createServerRequest("GET", "/assets/swagger-ui/favicon-32x32.png");
        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("image/png", $response->getHeaderLine("Content-Type"));
    }

    /**
     * @test
     */
    public function assets_url_should_return_403_if_unexpected_file_type()
    {
        $request = $this->requestFactory->createServerRequest("GET", "/assets/swagger-ui/test.json");
        $response = $this->app->handle($request);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function assets_url_should_return_404_if_file_does_not_exist()
    {
        $request = $this->requestFactory->createServerRequest("GET", "/assets/swagger-ui/test.js");
        $response = $this->app->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }
}
