<?php


namespace Tests\Functional\Swagger;


use Tests\FunctionalTestCase;

class SwaggerTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function swagger_url_should_return_openapi_3_in_json_format()
    {
        $request = $this->requestFactory->createServerRequest("GET", "/swagger.json");
        $response = $this->app->handle($request);

        $this->assertJson((string)$response->getBody());

        $json = json_decode($response->getBody(), true);
        $this->assertEquals("3.0.0", $json["openapi"]);
    }

    /**
     * @test
     */
    public function base_url_should_return_swaggerui_page_with_host_uri_with_specified_port()
    {
        $request = $this->requestFactory->createServerRequest("GET", "http://localhost:8080/");
        $response = $this->app->handle($request);
        $this->assertStringContainsString("<title>ProxyBank API</title>", $response->getBody());
        $this->assertStringContainsString("url: \"http://localhost:8080/swagger.json\",", $response->getBody());
    }

    /**
     * @test
     */
    public function base_url_should_return_swaggerui_page_with_host_uri_without_specified_port()
    {
        $request = $this->requestFactory->createServerRequest("GET", "https://api.test.com/");
        $response = $this->app->handle($request);
        $this->assertStringContainsString("<title>ProxyBank API</title>", $response->getBody());
        $this->assertStringContainsString("url: \"https://api.test.com/swagger.json\",", $response->getBody());
    }
}
