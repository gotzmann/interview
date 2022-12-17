<?php
declare(strict_types=1);

use Comet\Comet;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as ORM;

require_once __DIR__ . '/vendor/autoload.php';

const VERSION = '0.9.0';

// Enable .env files for getenv()
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$app = new Comet([
    'host' => getenv('LISTEN_HOST'),
    'port' => getenv('LISTEN_PORT'),
]);

$app->setBasePath("/api/v1");

$app->init(
    function() {
        global $argv;

        $orm = new ORM;
        $orm->addConnection([
            'driver'   => getenv('DB_TYPE'),
            'database' => getenv('DB_NAME'),
            'options'  => [\PDO::ATTR_TIMEOUT => 5],
        ]);
        $orm->setAsGlobal();

    });

// API endpoint for Rules batch uploading and updating
$app->post('/rules/load',
    'App\Controllers\RulesController:load');

// API endpoint to add one or more items to the one of active baskets
$app->post('/basket/add',
    'App\Controllers\BasketController:add');

// API endpoint to compute total order price for all the items in given basket
$app->get('/checkout/total',
    'App\Controllers\CheckoutController:total');

$app->run();
