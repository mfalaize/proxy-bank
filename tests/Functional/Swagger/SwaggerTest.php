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
        $request = $this->request = $this->requestFactory->createServerRequest("GET", "/swagger");
        $response = $this->app->handle($request);

        $this->assertJson((string)$response->getBody());

        $json = json_decode($response->getBody(), true);
        $this->assertEquals("3.0.0", $json["openapi"]);
    }

    /**
     * @test
     */
    public function base_url_should_return_swaggerui_page()
    {
        $request = $this->request = $this->requestFactory->createServerRequest("GET", "/");
        $response = $this->app->handle($request);
        $this->assertStringContainsString("<title>Swagger UI</title>", $response->getBody());
    }
}
