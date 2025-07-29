<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Response;

it('can create simple order via HTTP API', function (): void {
    $client = static::createClient();

    $client->request('POST', '/api/orders', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'currency' => 'CZK'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_CREATED);
});
