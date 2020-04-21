<?php


namespace ProxyBank\Exceptions;


use ProxyBank\Services\IntlService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Slim\Handlers\ErrorHandler;
use Slim\Interfaces\CallableResolverInterface;
use Throwable;

class ProxyBankExceptionsHandler extends ErrorHandler
{
    /**
     * @var IntlService
     */
    private $intlService;

    public function __construct(
        IntlService $intlService, CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory, ?LoggerInterface $logger = null)
    {
        parent::__construct($callableResolver, $responseFactory, $logger);
        $this->intlService = $intlService;
    }


    public function __invoke(ServerRequestInterface $request, Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails): ResponseInterface
    {
        $message = $this->intlService->getErrorMessage((new ReflectionClass($exception))->getShortName(), $exception->messageFormatterArgs);
        $httpException = new $exception->httpExceptionClass($request);
        $httpException->setTitle($message);
        return parent::__invoke($request, $httpException, $displayErrorDetails, $logErrors, $logErrorDetails);
    }

}
