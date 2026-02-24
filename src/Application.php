<?php

declare(strict_types=1);

namespace App;

use App\Services\TwiMLService;
use Laminas\Filter\Word\DashToCamelCase;
use Odan\Session\Middleware\SessionStartMiddleware;
use Odan\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App as SlimApp;
use Slim\Interfaces\RouteInterface;
use Slim\Middleware\ContentLengthMiddleware;

use function sprintf;

/**
 * This class encapsulates the central Slim application,
 * making it easier to create and test.
 */
final class Application
{
    public function __construct(
        private readonly SlimApp $app,
        private readonly TwiMLService $twimlService,
    ) {
        $app->add(new ContentLengthMiddleware());
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->add(SessionStartMiddleware::class);
        $app->addErrorMiddleware(true, true, true);
    }

    /**
     * setupRoutes sets up the application's routing table
     */
    public function setupRoutes(): void
    {
        $this->app->post('/menu/step/{step}', [$this, 'getMenu']);
        $this->app->post('/menu/step/{step}/respond', [$this, 'handleMenu']);
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
     * This function returns TwiML for the menus in the application
     *
     * @param array<int,string> $args
     */
    public function getMenu(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        $menu = $this->twimlService->getMenu($args['step']);
        $response->getBody()->write($menu->asXML());
        return $response;
    }

    /**
     * This function determines the menu to return
     *
     * It does this by looking at the requested menu and any digit pressed.
     *
     * @param array<string,string> $args
     */
    public function handleMenu(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        $function = sprintf(
            "handle%sMenu",
            new DashToCamelCase()->filter($args['step']),
        );
        $menu     = $this->twimlService->$function($request->getParsedBody()['Digits']);
        $response->getBody()->write($menu->asXML());

        return $response;
    }
}
