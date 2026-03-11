<?php

declare(strict_types=1);

namespace App;

use App\Services\TwiMLService;
use Laminas\Filter\Word\DashToCamelCase;
use PhpDb\TableGateway\Exception\RuntimeException;
use PhpDb\TableGateway\TableGatewayInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App as SlimApp;
use Slim\Interfaces\RouteInterface;
use Slim\Middleware\ContentLengthMiddleware;

use function array_merge;
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
        private readonly TableGatewayInterface $table,
    ) {
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
        $this->app->post('/menu/step/{step}', [$this, 'getMenu']);
        $this->app->post('/menu/step/{step}/respond', [$this, 'processCallerInput']);
        $this->app->post('/menu/step/{step}/record', [$this, 'processCallerVoiceResponse']);
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
     * @param array<string,string> $args
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
    public function processCallerInput(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        $step     = $args['step'];
        $postData = $request->getParsedBody();
        $digits   = $postData['Digits'];

        $callerData = match ($step) {
            "choose-department" => ["department" => $digits === "1" ? 'insurance' : 'banking'],
            "choose-insurance-category" => ["insurance-category" => $digits === "1" ? 'personal' : 'commercial'],
            "choose-insurance-type" => ["insurance-type" => $digits === "1" ? 'home-and-contents' : 'car'],
            "choose-language" => ["language" => $digits === "1" ? 'English' : 'Español'],
            "choose-new-or-existing-policy" => ["policy-type" => $digits === "1" ? 'new' : 'existing'],
            "get-text-copy-of-conversation" => ["text-copy-of-conversation" => $digits === "1" ? true : false],
            default => [],
        };

        $function = sprintf("handle%sMenu", new DashToCamelCase()->filter($step));
        $menu     = $this->twimlService->$function($digits ?? null);

        $response->getBody()->write($menu->asXML());

        $this->persistCallerData($postData['From'], $callerData);

        return $response;
    }

    /**
     * @param array<string,bool|string|mixed> $args
     */
    public function processCallerVoiceResponse(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): void {
        $postData = $request->getParsedBody();

        $key = $args['step'] === 'provide-personal-details'
            ? 'personal-details'
            : 'policy-number';
        $this->persistCallerData(
            $postData['From'],
            [
                $key => $postData['TranscriptionText'],
            ],
        );
    }

    /**
     * This function handles persisting the user's data in session
     *
     * It, basically, pulls any existing data from the current session, merges the new data
     * into the retrieved data, then persists the updated data back into the session
     *
     * @param array<int,string> $callerData
     */
    private function persistCallerData(string $callerPhoneNumber, array $callerData): void
    {
        if ($callerData === []) {
            return;
        }

        try {
            $this->table->insert(array_merge($callerData, ['caller_phone_number' => $callerPhoneNumber]));
        } catch (RuntimeException $e) {
            $this->table->update($callerData, ['caller_phone_number' => $callerPhoneNumber]);
        }
    }
}
