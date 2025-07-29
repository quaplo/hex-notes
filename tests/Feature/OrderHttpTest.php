<?php

declare(strict_types=1);

use App\Order\Application\Command\CreateOrderCommand;
use App\Order\Application\Command\CreateOrderHandler;
use App\Order\Application\Command\AddItemCommand;
use App\Order\Application\Command\AddItemHandler;
use Symfony\Component\HttpFoundation\Response;

it('can create order via HTTP API', function (): void {
    $client = static::createClient();

    $client->request('POST', '/api/orders', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'currency' => 'CZK'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_CREATED);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['orderId'])->not()->toBeEmpty();
    expect($responseData['orderId'])->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('can create order with different currencies via HTTP API', function (): void {
    $client = static::createClient();
    $currencies = ['CZK', 'EUR', 'USD', 'GBP'];

    foreach ($currencies as $currency) {
        $client->request('POST', '/api/orders', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currency' => $currency
        ]));

        expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_CREATED);

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        expect($responseData['orderId'])->not()->toBeEmpty();
    }
});

it('can get order via HTTP API', function (): void {
    $client = static::createClient();

    // First create an order
    /** @var CreateOrderHandler $createOrderHandler */
    $createOrderHandler = self::getContainer()->get(CreateOrderHandler::class);
    $createOrderCommand = CreateOrderCommand::fromPrimitives('EUR');
    $uuid = $createOrderHandler($createOrderCommand);

    // Then get the order via API
    $client->request('GET', '/api/orders/' . $uuid->toString());

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['orderId'])->toBe($uuid->toString());
    expect($responseData['currency'])->toBe('EUR');
    expect($responseData['status'])->toBe('CREATED');
    expect($responseData['items'])->toBeArray();
    expect($responseData['totalPrice'])->toBe(0);
});

it('can add item to order via HTTP API', function (): void {
    $client = static::createClient();

    // First create an order
    /** @var CreateOrderHandler $createOrderHandler */
    $createOrderHandler = self::getContainer()->get(CreateOrderHandler::class);
    $createOrderCommand = CreateOrderCommand::fromPrimitives('CZK');
    $uuid = $createOrderHandler($createOrderCommand);

    // Add item to order
    $client->request('POST', '/api/orders/' . $uuid->toString() . '/items', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'productId' => '550e8400-e29b-41d4-a716-446655440001',
        'productName' => 'Test Product',
        'quantity' => 2,
        'unitPrice' => 150.0,
        'currency' => 'CZK'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Verify item was added by getting order details
    $client->request('GET', '/api/orders/' . $uuid->toString());
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['items'])->toHaveCount(1);
    expect($responseData['items'][0]['productName'])->toBe('Test Product');
    expect($responseData['items'][0]['quantity'])->toBe(2);
    expect($responseData['totalPrice'])->toBe(300);
});

it('can remove item from order via HTTP API', function (): void {
    $client = static::createClient();

    // Create order and add item programmatically
    /** @var CreateOrderHandler $createOrderHandler */
    $createOrderHandler = self::getContainer()->get(CreateOrderHandler::class);
    $createOrderCommand = CreateOrderCommand::fromPrimitives('USD');
    $uuid = $createOrderHandler($createOrderCommand);

    /** @var AddItemHandler $addItemHandler */
    $addItemHandler = self::getContainer()->get(AddItemHandler::class);
    $addItemCommand = AddItemCommand::fromPrimitives(
        $uuid->toString(),
        '550e8400-e29b-41d4-a716-446655440002',
        'Product to Remove',
        1,
        50.0,
        'USD'
    );
    $addItemHandler($addItemCommand);

    // Get order to find item ID
    $client->request('GET', '/api/orders/' . $uuid->toString());
    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    $orderItemId = $responseData['items'][0]['orderItemId'];

    // Remove item from order
    $client->request('DELETE', '/api/orders/' . $uuid->toString() . '/items', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'orderItemId' => $orderItemId
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Verify item was removed
    $client->request('GET', '/api/orders/' . $uuid->toString());
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['items'])->toHaveCount(0);
    expect($responseData['totalPrice'])->toBe(0);
});

it('can change order status via HTTP API', function (): void {
    $client = static::createClient();

    // First create an order
    /** @var CreateOrderHandler $createOrderHandler */
    $createOrderHandler = self::getContainer()->get(CreateOrderHandler::class);
    $createOrderCommand = CreateOrderCommand::fromPrimitives('EUR');
    $uuid = $createOrderHandler($createOrderCommand);

    // Change status to CONFIRMED
    $client->request('PUT', '/api/orders/' . $uuid->toString() . '/status', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'status' => 'CONFIRMED'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Verify status was changed
    $client->request('GET', '/api/orders/' . $uuid->toString());
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['status'])->toBe('CONFIRMED');
});

it('can follow complete order workflow via HTTP API', function (): void {
    $client = static::createClient();

    // 1. Create order
    $client->request('POST', '/api/orders', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'currency' => 'CZK'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_CREATED);
    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    $orderId = $responseData['orderId'];

    // 2. Add multiple items
    $client->request('POST', '/api/orders/' . $orderId . '/items', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'productId' => '550e8400-e29b-41d4-a716-446655440001',
        'productName' => 'Product A',
        'quantity' => 2,
        'unitPrice' => 100.0,
        'currency' => 'CZK'
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    $client->request('POST', '/api/orders/' . $orderId . '/items', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'productId' => '550e8400-e29b-41d4-a716-446655440002',
        'productName' => 'Product B',
        'quantity' => 1,
        'unitPrice' => 200.0,
        'currency' => 'CZK'
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // 3. Confirm order
    $client->request('PUT', '/api/orders/' . $orderId . '/status', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'status' => 'CONFIRMED'
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // 4. Mark as paid
    $client->request('PUT', '/api/orders/' . $orderId . '/status', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'status' => 'PAID'
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // 5. Ship order
    $client->request('PUT', '/api/orders/' . $orderId . '/status', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'status' => 'SHIPPED'
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // 6. Deliver order
    $client->request('PUT', '/api/orders/' . $orderId . '/status', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'status' => 'DELIVERED'
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // 7. Verify final state
    $client->request('GET', '/api/orders/' . $orderId);
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['status'])->toBe('DELIVERED');
    expect($responseData['items'])->toHaveCount(2);
    expect($responseData['totalPrice'])->toBe(400);
});

it('returns 404 when getting non-existent order', function (): void {
    $client = static::createClient();

    $nonExistentId = '550e8400-e29b-41d4-a716-446655440000';

    $client->request('GET', '/api/orders/' . $nonExistentId);

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['error'])->toBe('Order not found');
});

it('validates currency when creating order', function (): void {
    $client = static::createClient();

    // Test invalid currency
    $client->request('POST', '/api/orders', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'currency' => 'INVALID'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['violations'])->toHaveKey('currency');
});

it('validates empty currency when creating order', function (): void {
    $client = static::createClient();

    // Test empty currency
    $client->request('POST', '/api/orders', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'currency' => ''
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['violations'])->toHaveKey('currency');
});

it('validates item data when adding to order', function (): void {
    $client = static::createClient();

    // First create an order
    /** @var CreateOrderHandler $createOrderHandler */
    $createOrderHandler = self::getContainer()->get(CreateOrderHandler::class);
    $createOrderCommand = CreateOrderCommand::fromPrimitives('CZK');
    $uuid = $createOrderHandler($createOrderCommand);

    // Test invalid product ID
    $client->request('POST', '/api/orders/' . $uuid->toString() . '/items', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'productId' => 'invalid-uuid',
        'productName' => 'Test Product',
        'quantity' => 1,
        'unitPrice' => 100.0,
        'currency' => 'CZK'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['violations'])->toHaveKey('productId');
});

it('validates negative quantity when adding item', function (): void {
    $client = static::createClient();

    // First create an order
    /** @var CreateOrderHandler $createOrderHandler */
    $createOrderHandler = self::getContainer()->get(CreateOrderHandler::class);
    $createOrderCommand = CreateOrderCommand::fromPrimitives('CZK');
    $uuid = $createOrderHandler($createOrderCommand);

    // Test negative quantity
    $client->request('POST', '/api/orders/' . $uuid->toString() . '/items', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'productId' => '550e8400-e29b-41d4-a716-446655440001',
        'productName' => 'Test Product',
        'quantity' => -1,
        'unitPrice' => 100.0,
        'currency' => 'CZK'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['violations'])->toHaveKey('quantity');
});

it('validates invalid status when changing order status', function (): void {
    $client = static::createClient();

    // First create an order
    /** @var CreateOrderHandler $createOrderHandler */
    $createOrderHandler = self::getContainer()->get(CreateOrderHandler::class);
    $createOrderCommand = CreateOrderCommand::fromPrimitives('EUR');
    $uuid = $createOrderHandler($createOrderCommand);

    // Try invalid status
    $client->request('PUT', '/api/orders/' . $uuid->toString() . '/status', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'status' => 'INVALID_STATUS'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['violations'])->toHaveKey('status');
});

it('validates currency mismatch when adding item', function (): void {
    $client = static::createClient();

    // First create an order with CZK
    /** @var CreateOrderHandler $createOrderHandler */
    $createOrderHandler = self::getContainer()->get(CreateOrderHandler::class);
    $createOrderCommand = CreateOrderCommand::fromPrimitives('CZK');
    $uuid = $createOrderHandler($createOrderCommand);

    // Try to add item with different currency
    $client->request('POST', '/api/orders/' . $uuid->toString() . '/items', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'productId' => '550e8400-e29b-41d4-a716-446655440001',
        'productName' => 'Test Product',
        'quantity' => 1,
        'unitPrice' => 100.0,
        'currency' => 'EUR'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR);
});

it('prevents invalid status transitions via HTTP API', function (): void {
    $client = static::createClient();

    // First create an order
    /** @var CreateOrderHandler $createOrderHandler */
    $createOrderHandler = self::getContainer()->get(CreateOrderHandler::class);
    $createOrderCommand = CreateOrderCommand::fromPrimitives('EUR');
    $uuid = $createOrderHandler($createOrderCommand);

    // Try to jump directly to DELIVERED from CREATED
    $client->request('PUT', '/api/orders/' . $uuid->toString() . '/status', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'status' => 'DELIVERED'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR);
});

it('prevents adding items to non-modifiable order via HTTP API', function (): void {
    $client = static::createClient();

    // Create order and change to PAID status
    /** @var CreateOrderHandler $createOrderHandler */
    $createOrderHandler = self::getContainer()->get(CreateOrderHandler::class);
    $createOrderCommand = CreateOrderCommand::fromPrimitives('CZK');
    $uuid = $createOrderHandler($createOrderCommand);

    // Change status to CONFIRMED then PAID
    $client->request('PUT', '/api/orders/' . $uuid->toString() . '/status', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode(['status' => 'CONFIRMED']));

    $client->request('PUT', '/api/orders/' . $uuid->toString() . '/status', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode(['status' => 'PAID']));

    // Try to add item to paid order
    $client->request('POST', '/api/orders/' . $uuid->toString() . '/items', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'productId' => '550e8400-e29b-41d4-a716-446655440001',
        'productName' => 'Test Product',
        'quantity' => 1,
        'unitPrice' => 100.0,
        'currency' => 'CZK'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR);
});
