<?php

declare(strict_types=1);

use App\Application;
use App\Services\TwiMLService;
use DI\Container;
use Dotenv\Dotenv;
use Laminas\Session;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Load environment variables from .env in the project's parent directory
 */
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

/**
 * The following three environment variables are are required for using the Twilio Client, and
 * sending notifications (SMS, MMS, and WhatsApp messages). So, we now ensure that they're
 * available and not empty.
 */
$dotenv->required([
    'TWILIO_ACCOUNT_SID',
    'TWILIO_AUTH_TOKEN',
    'TWILIO_PHONE_NUMBER',
])->notEmpty();

/**
 * We next set up the application's DI container, which uses PHP-DI.
 */
$container = new Container();

$container->set(
    Session\SessionManager::class,
    function (): Session\SessionManager {
        $config = [
            'session_manager' => [
                'config'     => [
                    'class'   => Session\Config\SessionConfig::class,
                    'options' => [
                        'cookie_domain'       => 'localhost',
                        'cookie_lifetime'     => 3600,
                        'cookie_path'         => '/',
                        'name'                => 'test',
                        'remember_me_seconds' => 3600,
                        'use_cookies'         => true,
                    ],
                ],
                'storage'    => Session\Storage\SessionArrayStorage::class,
                'validators' => [
                    Session\Validator\RemoteAddr::class,
                    Session\Validator\HttpUserAgent::class,
                ],
            ],
        ];

        $sessionManager = new Session\SessionManager(
            new Session\Config\SessionConfig()
                ->setOptions($config['session_manager']['config']['options']),
        );
        $sessionManager->setStorage(new Session\Storage\SessionArrayStorage());
        Session\Container::setDefaultManager($sessionManager);
        return $sessionManager;
    },
);

$config  = [
    'session' => [
        'name'          => 'app',
        'lifetime'      => 7200,
        'save_path'     => null,
        'domain'        => null,
        'secure'        => false,
        'httponly'      => true,
        'cache_limiter' => 'nocache',
    ],
];
$session = new PhpSession($config['session']);

/**
 * To simplify interacting with Twilio's APIs, we next register a Twilio REST Client object
 * as a service with the DI container, available in Twilio's PHP Helper Library.
 * Find out more about it at https://www.twilio.com/docs/libraries/reference/twilio-php/.
 */
$container->set(
    Client::class,
    fn(): Client => new Client($_ENV['TWILIO_ACCOUNT_SID'], $_ENV['TWILIO_AUTH_TOKEN']),
);

/**
 * With the DI container initialised, it's now set as the Slim application's DI container,
 * before initialising a new Slim App object.
 */
AppFactory::setContainer($container);
$app = AppFactory::createFromContainer($container);

/**
 * Finally, initialise a new Application object, initialise the routing table, and boot the
 * application, having it available for handling requests.
 */
$application = new Application($app, new TwiMLService(new VoiceResponse()), $session);
$application->setupRoutes();
$application->run();
