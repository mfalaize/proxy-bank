<?php


namespace ProxyBank\Middlewares;


use Locale;
use ProxyBank\Helpers\IntlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IntlMiddleware implements MiddlewareInterface
{
    const REQUEST_INTL = "REQUEST_INTL";
    const DEFAULT_LOCALE = "en_US";

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $locale = Locale::acceptFromHttp($request->getHeaderLine("Accept-Language")) ?: self::DEFAULT_LOCALE;

        return $handler->handle(
            $request->withAttribute(
                self::REQUEST_INTL,
                new IntlHelper($locale)
            )
        );
    }
}
