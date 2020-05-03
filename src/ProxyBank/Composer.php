<?php


namespace ProxyBank;


class Composer
{

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
            copy($vendorDir . "/" . $file, $assetsDir . "/" . $file);
        }
    }
}
