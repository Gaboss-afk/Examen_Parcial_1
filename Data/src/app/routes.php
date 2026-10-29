<?php

namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;


$app->group('/api', function(RouteCollectorProxy $api) {
    $api->post('/auth/credentials', Auth::class . ':credentials');
    $api->group('/ordenes_trabajo', function(RouteCollectorProxy $endpoint) {
        $endpoint->get('', OrdenesTrabajo::class . ':read');
        $endpoint->get('/{id}', OrdenesTrabajo::class . ':read');
        $endpoint->post('', OrdenesTrabajo::class . ':create');
        $endpoint->put('/{id}', OrdenesTrabajo::class . ':update');
        $endpoint->delete('/{id}', OrdenesTrabajo::class . ':delete');
    });
});