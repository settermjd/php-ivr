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
            new TwiMLService(new VoiceResponse()),
            $session,
        );
        $this->assertInstanceOf(Application::class, $app);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testAppCanInitialiseRoutingTable(): void
    {
        $slimApp = $this->createMock(App::class);
        $slimApp
            ->expects($this->exactly(3))
            ->method("post");
        $session = $this->createStub(SessionInterface::class);
        $app     = new Application(
            $slimApp,
            new TwiMLService(new VoiceResponse()),
            $this->createMock(SessionInterface::class),
        );
        $app->setupRoutes();
    }

    /**
     * @param array<string,string>|null $postData The post data sent by Twilio to the endpoint
     */
    #[TestWith(['choose-department', ['Digits' => '1', 'From' => '+16175551212']])]
    #[TestWith(['choose-insurance-category', ['Digits' => '1', 'From' => '+16175551212']])]
    #[TestWith(['choose-insurance-type', ['Digits' => '1', 'From' => '+16175551212']])]
    #[TestWith(['choose-language', ['Digits' => '1', 'From' => '+16175551212']])]
    #[TestWith(['choose-new-or-existing-policy', ['Digits' => '1', 'From' => '+16175551212']])]
    #[TestWith(['get-text-copy-of-conversation', ['Digits' => '1', 'From' => '+16175551212']])]
    #[TestWith(['unknown', ['Digits' => '1', 'From' => '+16175551212']])]
    public function testAppHandlesCallerTouchToneInputCorrectly(string $step, ?array $postData = null): void
    {
        $container = $this->createStub(Container::class);

        AppFactory::setContainer($container);
        $slimApp = AppFactory::createFromContainer($container);

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('has')
            ->with($postData['From'])
            ->willReturn(true);
        $session
            ->expects($this->once())
            ->method('get')
            ->with($postData['From'])
            ->willReturn([]);

        $digits      = $postData['Digits'];
        $sessionData = match ($step) {
            'choose-department' => ["department" => $digits === "1" ? 'insurance' : 'banking'],
            "choose-insurance-category" => ["insurance-category" => $digits === "1" ? 'personal' : 'commercial'],
            "choose-insurance-type" => ["insurance-type" => $digits === "1" ? 'home-and-contents' : 'car'],
            "choose-language" => ["language" => $digits === "1" ? 'English' : 'Español'],
            "choose-new-or-existing-policy" => ["policy-type" => $digits === "1" ? 'new' : 'existing'],
            "get-text-copy-of-conversation" => ["text-copy-of-conversation" => $digits === "1" ? true : false],
            "unknown" => [],
        };

        $session
            ->expects($this->once())
            ->method('set')
            ->with($postData['From'], $sessionData);

        $app = new Application(
            $slimApp,
            new TwiMLService(new VoiceResponse()),
            $session,
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $menu = $app->processCallerInput(
            $request,
            $this->createStub(ResponseInterface::class),
            ['step' => $step],
        );
    }

    /**
     * @param array<string,string> $postData
     */
    #[TestWith([
        'provide-personal-details',
        [
            'From'                => '+16175551212',
            'TranscriptionStatus' => 'completed',
            'TranscriptionText'   => 'Dave Grohl',
        ],
    ])]
    #[TestWith([
        'provide-policy-number',
        [
            'From'                => '+16175551212',
            'TranscriptionStatus' => 'completed',
            'TranscriptionText'   => 'MPW1234567890',
        ],
    ])]
    public function testAppHandlesCallerVoiceResponseInputCorrectly(string $step, array $postData): void
    {
        $response = $this->createStub(ResponseInterface::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('has')
            ->with($postData['From'])
            ->willReturn(true);
        $session
            ->expects($this->once())
            ->method('get')
            ->with($postData['From'])
            ->willReturn([]);

        $sessionData = match ($step) {
            'provide-personal-details' => ['personal-details' => $postData['TranscriptionText']],
            'provide-policy-number'    => ['policy-number' => $postData['TranscriptionText']],
        };
        $session
            ->expects($this->once())
            ->method('set')
            ->with($postData['From'], $sessionData);

        $app = new Application(
            AppFactory::createFromContainer($this->createStub(Container::class)),
            new TwiMLService(new VoiceResponse()),
            $session,
        );

        $menu = $app->processCallerVoiceResponse($request, $response, ['step' => $step]);
    }
}
