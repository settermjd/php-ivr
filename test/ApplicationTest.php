<?php

declare(strict_types=1);

namespace AppTest;

use App\Application;
use App\Services\TwiMLService;
use ArrayObject;
use DI\Container;
use Monolog\Logger;
use PhpDb\ResultSet\ResultSet;
use PhpDb\TableGateway\Exception\RuntimeException;
use PhpDb\TableGateway\TableGatewayInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Twilio\TwiML\VoiceResponse;

use function array_merge;
use function sprintf;

/**
 * This class encapsulates the central Slim application,
 * making it easier to create and test.
 */
final class ApplicationTest extends TestCase
{
    #[AllowMockObjectsWithoutExpectations]
    public function testCanInstantiateApplicationObject(): void
    {
        $table = $this->createStub(TableGatewayInterface::class);
        $app   = new Application(
            $this->createMock(App::class),
            new TwiMLService(new VoiceResponse()),
            $table,
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

        $app = new Application(
            $slimApp,
            new TwiMLService(new VoiceResponse()),
            $this->createStub(TableGatewayInterface::class),
        );
        $app->setupRoutes();
    }

    /**
     * @param array<string,string>|null $postData The post data sent by Twilio to the endpoint
     */
    #[TestWith(
        [
            'choose-department',
            [
                'CallSid' => 'CAa0000000000000000000000000000000',
                'Digits'  => '1',
                'From'    => '+16175551212',
            ],
        ]
    )]
    #[TestWith(
        [
            'choose-department',
            [
                'CallSid' => 'CAa0000000000000000000000000000000',
                'Digits'  => '1',
                'From'    => '+16175551212',
            ],
            true,
        ]
    )]
    #[TestWith(
        [
            'choose-insurance-category',
            [
                'CallSid' => 'CAa0000000000000000000000000000000',
                'Digits'  => '1',
                'From'    => '+16175551212',
            ],
        ]
    )]
    #[TestWith(
        [
            'choose-insurance-type',
            [
                'CallSid' => 'CAa0000000000000000000000000000000',
                'Digits'  => '1',
                'From'    => '+16175551212',
            ],
        ]
    )]
    #[TestWith(
        [
            'choose-language',
            [
                'CallSid' => 'CAa0000000000000000000000000000000',
                'Digits'  => '1',
                'From'    => '+16175551212',
            ],
        ]
    )]
    #[TestWith(
        [
            'choose-new-or-existing-policy',
            [
                'CallSid' => 'CAa0000000000000000000000000000000',
                'Digits'  => '1',
                'From'    => '+16175551212',
            ],
        ],
    )]
    #[TestWith(
        [
            'get-text-copy-of-conversation',
            [
                'CallSid' => 'CAa0000000000000000000000000000000',
                'Digits'  => '1',
                'From'    => '+16175551212',
            ],
        ],
    )]
    public function testAppHandlesCallerTouchToneInputCorrectly(
        string $step,
        ?array $postData = null,
        bool $recordExists = false,
    ): void {
        $container = $this->createStub(Container::class);

        AppFactory::setContainer($container);
        $slimApp = AppFactory::createFromContainer($container);

        $digits     = $postData['Digits'];
        $callerData = match ($step) {
            'choose-department' => ['department' => $digits === '1' ? 'insurance' : 'banking'],
            'choose-insurance-category' => ['insurance_category' => $digits === '1' ? 'personal' : 'commercial'],
            'choose-insurance-type' => ['insurance_type' => $digits === '1' ? 'home-and-contents' : 'car'],
            'choose-language' => ['language' => $digits === '1' ? 'english' : 'spanish'],
            'choose-new-or-existing-policy' => ['new_or_existing_policy' => $digits === '1' ? 'new' : 'existing'],
            'get-text-copy-of-conversation' => ['text_copy_of_conversation' => $digits === '1' ? true : false],
        };

        $callerDetails = [
            'call_sid'            => $postData['CallSid'],
            'caller_phone_number' => $postData['From'],
        ];
        $table         = $this->createMock(TableGatewayInterface::class);
        if ($recordExists) {
            $table
                ->expects($this->once())
                ->method('insert')
                ->with(array_merge($callerData, $callerDetails))
                ->willThrowException(new RuntimeException());
            $table
                ->expects($this->once())
                ->method('update')
                ->with($callerData, $callerDetails)
                ->willReturn(1);
        } else {
            $table
                ->expects($this->once())
                ->method('insert')
                ->with(array_merge($callerData, $callerDetails))
                ->willReturn(1);
        }

        $app = new Application(
            $slimApp,
            new TwiMLService(new VoiceResponse()),
            $table,
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
            'CallSid'             => 'CAa0000000000000000000000000000000',
            'From'                => '+16175551212',
            'TranscriptionStatus' => 'completed',
            'TranscriptionText'   => 'Dave Grohl',
        ],
    ])]
    #[TestWith([
        'provide-policy-number',
        [
            'CallSid'             => 'CAa0000000000000000000000000000000',
            'From'                => '+16175551212',
            'TranscriptionStatus' => 'completed',
            'TranscriptionText'   => 'MPW1234567890',
        ],
    ])]
    public function testAppHandlesCallerVoiceResponseInputCorrectly(
        string $step,
        array $postData,
        bool $recordExists = false,
    ): void {
        $response = $this->createStub(ResponseInterface::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($postData);

        $callerData = match ($step) {
            'provide-personal-details' => [
                'call_sid'         => $postData['CallSid'],
                'personal_details' => $postData['TranscriptionText'],
            ],
            'provide-policy-number'    => [
                'call_sid'      => $postData['CallSid'],
                'policy_number' => $postData['TranscriptionText'],
            ],
        };
        $callerDetails = ['caller_phone_number' => $postData['From']];
        $table         = $this->createMock(TableGatewayInterface::class);
        if ($recordExists) {
            $table
                ->expects($this->once())
                ->method('insert')
                ->with(array_merge($callerData, $callerDetails))
                ->willThrowException(new RuntimeException());
            $table
                ->expects($this->once())
                ->method('update')
                ->with($callerData, $callerDetails)
                ->willReturn(1);
        } else {
            $table
                ->expects($this->once())
                ->method('insert')
                ->with(array_merge($callerData, $callerDetails))
                ->willReturn(1);
        }

        $slimApp = AppFactory::createFromContainer($this->createStub(Container::class));
        $app     = new Application($slimApp, new TwiMLService(new VoiceResponse()), $table);

        $menu = $app->processCallerVoiceResponse($request, $response, ['step' => $step]);
    }

    public function testThatAgentDashboardRendersAsExpected(): void
    {
        $callSid = 'CAa0000000000000000000000000000000';

        $result = $this->createMock(ResultSet::class);
        $result
            ->expects($this->once())
            ->method('current')
            ->willReturn(new ArrayObject());
        $table = $this->createMock(TableGatewayInterface::class);
        $table
            ->expects($this->once())
            ->method('select')
            ->with(['call_sid' => $callSid])
            ->willReturn($result);

        $slimApp  = AppFactory::createFromContainer($this->createStub(Container::class));
        $app      = new Application($slimApp, new TwiMLService(new VoiceResponse()), $table);
        $response = $this->createStub(ResponseInterface::class);

        $twig = $this->createMock(Twig::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                $response,
                'dashboard.html.twig',
                []
            );

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getAttribute')
            ->with('view')
            ->willReturn($twig);

        $app->getAgentDashboard($request, $response, ['callSid' => $callSid]);
    }

    public function testThatTheApplicationLogsTheCallSIDWhenTheChooseLanguageMenuIsRequested(): void
    {
        $callSid = 'CAa0000000000000000000000000000000';

        $slimApp = AppFactory::createFromContainer($this->createStub(Container::class));
        $logger  = $this->createMock(Logger::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with(sprintf("Call's SID is %s", $callSid));

        $app = new Application(
            $slimApp,
            new TwiMLService(new VoiceResponse()),
            $this->createStub(TableGatewayInterface::class),
            $logger,
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(
                [
                    'CallSid' => $callSid,
                    'From'    => '+16175551212',
                ]
            );
        $response = $this->createStub(ResponseInterface::class);

        $app->getMenu($request, $response, ['step' => 'choose-language']);
    }
}
