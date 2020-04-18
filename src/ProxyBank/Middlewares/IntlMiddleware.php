<?php


namespace ProxyBank\Middlewares;


use DI\Container;
use Locale;
use ProxyBank\Services\IntlService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IntlMiddleware implements MiddlewareInterface
{
    const DEFAULT_LOCALE = "en_US";

    /**
     * @var Container
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $locale = Locale::acceptFromHttp($request->getHeaderLine("Accept-Language")) ?: self::DEFAULT_LOCALE;
        $this->container->set(IntlService::class, new IntlService($locale));
        return $handler->handle($request);
    }
}
