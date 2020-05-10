<?php


namespace ProxyBank;


use GuzzleHttp\Client;

class Composer
{
    public static function installPhpDocumentor()
    {
        $phpDocumentorPhar = __DIR__ . "/../../phpDocumentor.phar";
        $phpDocumentorPharUrl = "http://www.phpdoc.org/phpDocumentor.phar";

        if (!file_exists($phpDocumentorPhar)) {
            print "Download phpDocumentor.phar...";
            require __DIR__ . "/../../vendor/autoload.php";
            $client = new Client();
            $response = $client->get($phpDocumentorPharUrl);
            $pharLocalFile = fopen($phpDocumentorPhar, "w");
            fwrite($pharLocalFile, $response->getBody());
            fclose($pharLocalFile);
        }
    }

    public static function copySwaggerUIAssets()
    {
        $vendorDir = __DIR__ . "/../../vendor/swagger-api/swagger-ui/dist";
        $assetsDir = __DIR__ . "/../../assets/swagger-ui";

        if (!file_exists($assetsDir)) {
            mkdir($assetsDir, 0777, true);
        }

        $filesToCopy = [
            "swagger-ui-bundle.js",
            "swagger-ui-standalone-preset.js",
            "swagger-ui.css",
            "favicon-16x16.png",
            "favicon-32x32.png"
        ];

        foreach ($filesToCopy as $file) {
            $sourceFile = $vendorDir . "/" . $file;
            $destFile = $assetsDir . "/" . $file;

            print "Copy " . $sourceFile . " to " . $destFile . "\n";

            copy($sourceFile, $destFile);
        }
    }
}
