<?php

use Spiral\RoadRunner;
use Nyholm\Psr7;
use Illuminate\Database\Capsule\Manager as ORM;

require_once __DIR__ . '/vendor/autoload.php';

// NB! We could debug app with error_log() not echo() / var_dump()

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// --- Set up routes

$routes = [
    'GET' => [],
    'POST' => [ '/products/list' => 'App\Handlers\Handler::listProducts' ]
];

// Set up Eloquent ORM

$orm = new ORM;
$orm->addConnection([
    'driver'   => getenv('DB_TYPE'),
    'host'     => getenv('DB_HOST'),
    'port'     => getenv('DB_PORT'),
    'database' => getenv('DB_NAME'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASSWORD'),
    'options'  => [ \PDO::ATTR_TIMEOUT => 5 ],
]);
$orm->setAsGlobal();

// --- Set up Road Runner

$worker = RoadRunner\Worker::create();
$psrFactory = new Psr7\Factory\Psr17Factory();

$worker = new RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);

// --- Main Loop

while ($request = $worker->waitRequest()) {
    try {

        // --- Simple routing

        $method = $request->getMethod();
        $path = $request->getURI()->getPath();

        if (isset($routes[$method][$path])) {
            $response = call_user_func($routes[$method][$path], $request, new Psr7\Response());
        } else {
            $response = (new Psr7\Response())->withStatus(404);
        }

        $worker->respond($response);

    } catch (\Throwable $e) {
        $worker->getWorker()->error((string)$e);
    }
}
