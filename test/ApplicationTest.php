<?php

declare(strict_types=1);

namespace AppTest;

use App\Application;
use App\Services\TwiMLService;
use DI\Container;
use Odan\Session\SessionInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Twilio\TwiML\VoiceResponse;

/**
 * This class encapsulates the central Slim application,
 * making it easier to create and test.
 */
final class ApplicationTest extends TestCase
{
    #[AllowMockObjectsWithoutExpectations]
    public function testCanInstantiateApplicationObject(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $app     = new Application(
            $this->createMock(App::class),
            new TwiMLService(new VoiceResponse(), $session),
            $session,
        );
        $this->assertInstanceOf(Application::class, $app);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testAppCanInitialiseRoutingTable(): void
    {
        $slimApp = $this->createMock(App::class);
        $slimApp
            ->expects($this->exactly(2))
            ->method("post");
        $session = $this->createStub(SessionInterface::class);
        $app     = new Application(
            $slimApp,
            new TwiMLService(new VoiceResponse(), $session),
            $this->createMock(SessionInterface::class),
        );
        $app->setupRoutes();
    }

    /**
     * @param array<string,string>|null $postData The post data sent by Twilio to the endpoint
     */
    #[TestWith(['choose-department', ['Digits' => '1']])]
    #[TestWith(['choose-insurance-category', ['Digits' => '1']])]
    #[TestWith(['choose-insurance-type', ['Digits' => '1']])]
    #[TestWith(['choose-language', ['Digits' => '1']])]
    #[TestWith(['choose-new-or-existing-policy', ['Digits' => '1']])]
    #[TestWith(['get-text-copy-of-conversation', ['Digits' => '1']])]
    #[TestWith(['pre-transfer-confirmation', ['Digits' => '1']])]
    #[TestWith(['provide-personal-details', ['Digits' => '1']])]
    #[TestWith(['provide-policy-number', ['Digits' => '1']])]
    #[AllowMockObjectsWithoutExpectations]
    public function testAppHandlesRequestsCorrectly(string $step, ?array $postData = null): void
    {
        $container = $this->createStub(Container::class);

        AppFactory::setContainer($container);
        $slimApp = AppFactory::createFromContainer($container);

        $session = $this->createMock(SessionInterface::class);

        match ($step) {
            'choose-department' => $session->expects($this->once())->method('set'),
            default => '',
        };

        $app = new Application(
            $slimApp,
            new TwiMLService(new VoiceResponse(), $session),
            $session,
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $menu = $app->handleMenu(
            $request,
            $this->createStub(ResponseInterface::class),
            ['step' => $step],
        );
    }
}
