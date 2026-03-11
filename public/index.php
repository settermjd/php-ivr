<?php

declare(strict_types=1);

use App\Application;
use App\Services\TwiMLService;
use DI\Container;
use Dotenv\Dotenv;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\Adapter\Sqlite\ConfigProvider;
use PhpDb\Adapter\Sqlite\Container\AdapterFactory;
use PhpDb\TableGateway\TableGateway;
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

/**
 * To simplify interacting with Twilio's APIs, we next register a Twilio REST Client object
 * as a service with the DI container, available in Twilio's PHP Helper Library.
 * Find out more about it at https://www.twilio.com/docs/libraries/reference/twilio-php/.
 */
$container->set(
    Client::class,
    fn(): Client => new Client($_ENV['TWILIO_ACCOUNT_SID'], $_ENV['TWILIO_AUTH_TOKEN']),
);

$aggregator                         = new ConfigAggregator(
    [
        ConfigProvider::class,
        new PhpFileProvider(realpath(__DIR__) . '/../config/{{,*.}global,{,*.}local}.php'),
    ],
);
$config                             = $aggregator->getMergedConfig();
$dependencies                       = $config['dependencies'];
$dependencies['services']['config'] = $config;

$diContainer = new ServiceManager($dependencies);

$adapter = new AdapterFactory()->__invoke($diContainer);

/**
 * With the DI container initialised, it's now set as the Slim application's DI container,
 * before initialising a new Slim App object.
 */
AppFactory::setContainer($diContainer);
$app = AppFactory::createFromContainer($diContainer);

/**
 * Finally, initialise a new Application object, initialise the routing table, and boot the
 * application, having it available for handling requests.
 */
$application = new Application(
    $app,
    new TwiMLService(new VoiceResponse()),
    new TableGateway('ivr_input', $adapter),
);
$application->setupRoutes();
$application->run();
