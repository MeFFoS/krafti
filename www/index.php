<?php

use App\Container;
use App\Controllers\Api;
use App\Controllers\Home;
use App\Controllers\Image;
use Slim\App;

require dirname(__DIR__) . '/core/vendor/autoload.php';

$container = new Container();
$app = new App($container);
$app->add(new RKA\Middleware\IpAddress());

$app->any('/api[/{name:.+}]', function ($request, $response, $args) use ($container) {
    $container->request = $request;
    $container->response = $response;

    return (new Api($container))->process($request, $response, $args);
});

$app->any('/image/{id:\d+}[/{size:\d+x\d+}[/{params}]]', function ($request, $response, $args) use ($container) {
    $container->request = $request;
    $container->response = $response;

    return (new Image($container))->process($request, $response, $args);
});

$app->any('/file/{id:\d+}[/{params}]', function ($request, $response, $args) use ($container) {
    $container->request = $request;
    $container->response = $response;

    return (new \App\Controllers\File($container))->process($request, $response, $args);
});


if (getenv('ENV') === 'dev') {
    $app->any('/upload[/{file:.+}]', function ($request, $response, $args) use ($container) {
        /** @var Slim\Http\Response $response */
        return $response->withStatus(302)->withHeader('Location', 'https://krafti.ru/upload/' . $args['file']);
    });
}

$app->get('/', function ($request, $response, $args) use ($container) {
    return (new Home($container))->process($args);
});

try {
    $app->run();
} catch (Throwable $e) {
    exit($e->getMessage());
}