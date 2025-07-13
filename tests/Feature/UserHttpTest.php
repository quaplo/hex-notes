<?php

declare(strict_types=1);

use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Command\CreateUserHandler;
use Symfony\Component\HttpFoundation\Response;

it('can create user via HTTP API', function (): void {
    $client = static::createClient();

    $email = 'http_test_' . uniqid() . '@example.com';

    $client->request('POST', '/api/users', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'email' => $email
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_CREATED);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['message'])->toBe('User created successfully');
    expect($responseData['email'])->toBe($email);
    expect($responseData['id'])->not()->toBeEmpty();
});

it('can get user by ID via HTTP API', function (): void {
    $client = static::createClient();

    // First create a user
    /** @var CreateUserHandler $createHandler */
    $createHandler = self::getContainer()->get(CreateUserHandler::class);
    $email = 'get_test_' . uniqid() . '@example.com';
    $command = new CreateUserCommand($email);
    $user = $createHandler($command);

    // Then get the user via API
    $client->request('GET', '/api/users/' . $user->getId()->toString());

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['email'])->toBe($email);
    expect($responseData['id'])->toBe($user->getId()->toString());
});

it('can delete user via HTTP API', function (): void {
    $client = static::createClient();

    // First create a user
    /** @var CreateUserHandler $createHandler */
    $createHandler = self::getContainer()->get(CreateUserHandler::class);
    $email = 'delete_test_' . uniqid() . '@example.com';
    $command = new CreateUserCommand($email);
    $user = $createHandler($command);

    $userId = $user->getId()->toString();

    // Verify user exists before deletion
    $client->request('GET', '/api/users/' . $userId);
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    // Delete the user via API
    $client->request('DELETE', '/api/users/' . $userId);

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['message'])->toBe('User deleted successfully');

    // Verify user is no longer accessible via regular endpoint
    $client->request('GET', '/api/users/' . $userId);
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
});

it('returns 404 when deleting non-existent user', function (): void {
    $client = static::createClient();

    $nonExistentId = '550e8400-e29b-41d4-a716-446655440000';

    $client->request('DELETE', '/api/users/' . $nonExistentId);

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['error'])->toBe('User not found');
});

it('delete operation is idempotent via HTTP API', function (): void {
    $client = static::createClient();

    // First create a user
    /** @var CreateUserHandler $createHandler */
    $createHandler = self::getContainer()->get(CreateUserHandler::class);
    $email = 'idempotent_delete_' . uniqid() . '@example.com';
    $command = new CreateUserCommand($email);
    $user = $createHandler($command);

    $userId = $user->getId()->toString();

    // Delete the user first time
    $client->request('DELETE', '/api/users/' . $userId);
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    // Delete the user second time - should still return success
    $client->request('DELETE', '/api/users/' . $userId);
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['message'])->toBe('User deleted successfully');
});

it('validates email format when creating user', function (): void {
    $client = static::createClient();

    $client->request('POST', '/api/users', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'email' => 'invalid-email'
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['violations'])->toHaveKey('email');
});

it('returns 404 when getting non-existent user', function (): void {
    $client = static::createClient();

    $nonExistentId = '550e8400-e29b-41d4-a716-446655440000';

    $client->request('GET', '/api/users/' . $nonExistentId);

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['error'])->toBe('User not found');
});
