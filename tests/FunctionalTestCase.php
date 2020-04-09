<?php


namespace Tests;


use DI\Container;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\RequestFactory;

class FunctionalTestCase extends TestCase
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var Container
     */
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();

        require __DIR__ . "/../src/dependencies.php";

        $this->app = $app = AppFactory::create();

        require __DIR__ . "/../src/routes.php";

        $this->requestFactory = new RequestFactory();
        $this->container = $this->app->getContainer();
    }

    protected function tearDown(): void
    {
        unset($this->requestFactory, $this->app);
        parent::tearDown();
    }
}
