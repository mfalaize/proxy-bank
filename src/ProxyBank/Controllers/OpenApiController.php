<?php


namespace ProxyBank\Controllers;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use function OpenApi\scan;

class OpenApiController
{
    /**
     * @Inject
     * @var PhpRenderer
     */
    private $view;

    public function swaggerJson(Request $request, Response $response)
    {
        $openapi = scan(__DIR__ . "/../..");
        $response->getBody()->write($openapi->toJson());
        return $response->withHeader("Content-Type", "application/json");
    }

    public function swaggerUi(Request $request, Response $response)
    {
        $serverUrl = $request->getUri()->getScheme() .
            "://" .
            $request->getUri()->getHost();

        if (!empty($request->getUri()->getPort())) {
            $serverUrl .= ":" .
                $request->getUri()->getPort();
        }

        return $this->view->render($response, "swagger.php", [
            "swaggerUrl" => $serverUrl . "/swagger"
        ]);
    }
}
