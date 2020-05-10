<?php


namespace ProxyBank\Controllers;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;

class ResourcesController
{
    public function getResource(Request $request, Response $response, array $args)
    {
        $acceptedContentTypes = [
            "html" => "text/html",
            "js" => "text/javascript",
            "css" => "text/css",
            "png" => "image/png",
            "gif" => "image/gif",
            "ico" => "image/vnd.microsoft.icon",
            "svg" => "image/svg+xml",
            "jpg" => "image/jpeg",
            "jpeg" => "image/jpeg"
        ];

        $assetPath = $args["root"] . "/" . $args["path"] . "." . $args["type"];

        if (!array_key_exists($args["type"], $acceptedContentTypes)) {
            throw new HttpForbiddenException($request, "Illegal attempt to access file " . $assetPath);
        }

        $file = __DIR__ . "/../../../" . $assetPath;

        if (file_exists($file)) {
            $handle = fopen($file, "r");
            $response->getBody()->write(fread($handle, filesize($file)));
            fclose($handle);

            return $response->withHeader("Content-Type", $acceptedContentTypes[$args["type"]]);
        }

        throw new HttpNotFoundException($request);
    }
}
