<?php

declare(strict_types=1);

namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App as SlimApp;
use Slim\Interfaces\RouteInterface;
use Slim\Middleware\ContentLengthMiddleware;

/**
 * This class encapsulates the central Slim application,
 * making it easier to create and test.
 */
final class Application
{
    public function __construct(private readonly SlimApp $app)
    {
        $app->add(new ContentLengthMiddleware());
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(true, true, true);
    }

    /**
     * setupRoutes sets up the application's routing table
     */
    public function setupRoutes(): void
    {
        $this->app->get('/', [$this, 'handleDefaultRoute']);
    }

    /**
     * getRoutes returns the application's current routes
     *
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        return $this->app->getRouteCollector()->getRoutes();
    }

    /**
     * run launches the application
     */
    public function run(): void
    {
        $this->app->run();
    }

    /**
     * handleDefaultRoute responds to requests to the default route
     *
     * Currently, it's effectively a stub, as the project doesn't dictate nor assume
     * the kind of application that will be built.
     */
    public function handleDefaultRoute(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        return $response;
    }
}
