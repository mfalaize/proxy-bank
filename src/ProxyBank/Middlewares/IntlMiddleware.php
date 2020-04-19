<?php


namespace ProxyBank\Middlewares;


use Locale;
use ProxyBank\Services\IntlService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IntlMiddleware implements MiddlewareInterface
{
    /**
     * @var IntlService
     */
    private $intlService;

    public function __construct(ContainerInterface $container)
    {
        $this->intlService = $container->get(IntlService::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $locale = Locale::acceptFromHttp($request->getHeaderLine("Accept-Language")) ?: IntlService::DEFAULT_LOCALE;
        $this->intlService->setLocale($locale);
        return $handler->handle($request);
    }
}
