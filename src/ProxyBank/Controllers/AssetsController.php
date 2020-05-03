<?php


namespace ProxyBank\Controllers;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;

class AssetsController
{
    public function getResource(Request $request, Response $response, array $args)
    {
        $acceptedContentTypes = [
            "js" => "text/javascript",
            "css" => "text/css",
            "png" => "image/png"
        ];

        if (!array_key_exists($args["type"], $acceptedContentTypes)) {
            throw new HttpForbiddenException($request);
        }

        $file = __DIR__ . "/../../../assets/" . $args["path"] . "." . $args["type"];

        if (file_exists($file)) {
            $handle = fopen($file, "r");
            $response->getBody()->write(fread($handle, filesize($file)));
            fclose($handle);

            return $response->withHeader("Content-Type", $acceptedContentTypes[$args["type"]]);
        }

        throw new HttpNotFoundException($request);
    }
}
