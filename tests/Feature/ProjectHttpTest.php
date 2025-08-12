<?php

declare(strict_types=1);

use App\Project\Application\Command\Register\RegisterProjectCommand;
use App\Project\Application\Command\Register\RegisterProjectHandler;
use App\User\Application\Command\Create\CreateUserCommand;
use App\User\Application\Command\Create\CreateUserHandler;
use Symfony\Component\HttpFoundation\Response;

it('can create project via HTTP API', function (): void {
    $client = static::createClient();

    $projectName = 'HTTP Test Project '.uniqid();

    $client->request('POST', '/api/projects', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'name' => $projectName,
        'description' => 'Test project description',
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_CREATED);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['name'])->toBe($projectName);
    expect($responseData['id'])->not()->toBeEmpty();
    expect($responseData['ownerId'])->not()->toBeEmpty();
});

it('can get project with user details via HTTP API (cross-domain)', function (): void {
    $client = static::createClient();

    // First create a user (owner)
    /** @var CreateUserHandler $createUserHandler */
    $createUserHandler = self::getContainer()->get(CreateUserHandler::class);
    $ownerEmail = 'project_owner_'.uniqid().'@example.com';
    $createUserCommand = CreateUserCommand::fromPrimitives($ownerEmail);
    $user = $createUserHandler($createUserCommand);

    // Create a worker user
    $workerEmail = 'project_worker_'.uniqid().'@example.com';
    $workerCommand = CreateUserCommand::fromPrimitives($workerEmail);
    $worker = $createUserHandler($workerCommand);

    // Create a project with the owner
    /** @var RegisterProjectHandler $projectHandler */
    $projectHandler = self::getContainer()->get(RegisterProjectHandler::class);
    $projectName = 'Cross Domain Test Project '.uniqid();
    $registerProjectCommand = RegisterProjectCommand::fromPrimitives($projectName, $user->getId()->toString());
    $project = $projectHandler($registerProjectCommand);

    // Add worker to project
    $client->request('POST', '/api/projects/'.$project->getId()->toString().'/workers', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => $worker->getId()->toString(),
        'role' => 'participant',
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Now get the project via cross-domain query
    $client->request('GET', '/api/projects/'.$project->getId()->toString());

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);

    // Validate project data
    expect($responseData['project']['name'])->toBe($projectName);
    expect($responseData['project']['id'])->toBe($project->getId()->toString());

    // Validate cross-domain owner data
    expect($responseData['project']['owner']['email'])->toBe($ownerEmail);
    expect($responseData['project']['owner']['id'])->toBe($user->getId()->toString());

    // Validate cross-domain workers data
    expect($responseData['project']['workers'])->toHaveCount(1);
    expect($responseData['project']['workers'][0]['email'])->toBe($workerEmail);
    expect($responseData['project']['workers'][0]['id'])->toBe($worker->getId()->toString());
});

it('can rename project via HTTP API', function (): void {
    $client = static::createClient();

    // First create a project
    /** @var RegisterProjectHandler $projectHandler */
    $projectHandler = self::getContainer()->get(RegisterProjectHandler::class);
    $originalName = 'Original Project Name '.uniqid();
    $registerProjectCommand = RegisterProjectCommand::fromPrimitives($originalName, '550e8400-e29b-41d4-a716-446655440001');
    $project = $projectHandler($registerProjectCommand);

    $newName = 'Renamed Project '.uniqid();

    // Rename the project
    $client->request('PUT', '/api/projects/'.$project->getId()->toString(), [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'name' => $newName,
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['name'])->toBe($newName);
    expect($responseData['id'])->toBe($project->getId()->toString());
});

it('can delete project via HTTP API', function (): void {
    $client = static::createClient();

    // First create a user (owner)
    /** @var CreateUserHandler $createUserHandler */
    $createUserHandler = self::getContainer()->get(CreateUserHandler::class);
    $ownerEmail = 'project_owner_'.uniqid().'@example.com';
    $createUserCommand = CreateUserCommand::fromPrimitives($ownerEmail);
    $user = $createUserHandler($createUserCommand);

    // First create a project
    /** @var RegisterProjectHandler $projectHandler */
    $projectHandler = self::getContainer()->get(RegisterProjectHandler::class);
    $projectName = 'Project to Delete '.uniqid();
    $registerProjectCommand = RegisterProjectCommand::fromPrimitives($projectName, $user->getId()->toString());

    $project = $projectHandler($registerProjectCommand);

    $projectId = $project->getId()->toString();

    // Verify project exists before deletion
    $client->request('GET', '/api/projects/'.$projectId);

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    // Delete the project
    $client->request('DELETE', '/api/projects/'.$projectId, [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => $user->getId()->toString(),
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Verify project is no longer accessible
    $client->request('GET', '/api/projects/'.$projectId);
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);
});

it('can add worker to project via HTTP API', function (): void {
    $client = static::createClient();

    // Create owner and worker users
    /** @var CreateUserHandler $createUserHandler */
    $createUserHandler = self::getContainer()->get(CreateUserHandler::class);

    $ownerEmail = 'owner_'.uniqid().'@example.com';
    $createUserCommand = CreateUserCommand::fromPrimitives($ownerEmail);
    $user = $createUserHandler($createUserCommand);

    $workerEmail = 'worker_'.uniqid().'@example.com';
    $workerCommand = CreateUserCommand::fromPrimitives($workerEmail);
    $worker = $createUserHandler($workerCommand);

    // Create a project
    /** @var RegisterProjectHandler $projectHandler */
    $projectHandler = self::getContainer()->get(RegisterProjectHandler::class);
    $projectName = 'Worker Test Project '.uniqid();
    $registerProjectCommand = RegisterProjectCommand::fromPrimitives($projectName, $user->getId()->toString());
    $project = $projectHandler($registerProjectCommand);

    // Add worker to project
    $client->request('POST', '/api/projects/'.$project->getId()->toString().'/workers', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => $worker->getId()->toString(),
        'role' => 'participant',
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Verify worker was added by getting project details
    $client->request('GET', '/api/projects/'.$project->getId()->toString());
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['project']['workers'])->toHaveCount(1);
    expect($responseData['project']['workers'][0]['id'])->toBe($worker->getId()->toString());
});

it('can remove worker from project via HTTP API', function (): void {
    $client = static::createClient();

    // Create owner and worker users
    /** @var CreateUserHandler $createUserHandler */
    $createUserHandler = self::getContainer()->get(CreateUserHandler::class);

    $ownerEmail = 'owner_remove_'.uniqid().'@example.com';
    $createUserCommand = CreateUserCommand::fromPrimitives($ownerEmail);
    $user = $createUserHandler($createUserCommand);

    $workerEmail = 'worker_remove_'.uniqid().'@example.com';
    $workerCommand = CreateUserCommand::fromPrimitives($workerEmail);
    $worker = $createUserHandler($workerCommand);

    // Create a project
    /** @var RegisterProjectHandler $projectHandler */
    $projectHandler = self::getContainer()->get(RegisterProjectHandler::class);
    $projectName = 'Remove Worker Project '.uniqid();
    $registerProjectCommand = RegisterProjectCommand::fromPrimitives($projectName, $user->getId()->toString());
    $project = $projectHandler($registerProjectCommand);

    // Add worker first
    $client->request('POST', '/api/projects/'.$project->getId()->toString().'/workers', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => $worker->getId()->toString(),
        'role' => 'participant',
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Remove worker from project
    $client->request('DELETE', '/api/projects/'.$project->getId()->toString().'/workers', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => $worker->getId()->toString(),
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Verify worker was removed
    $client->request('GET', '/api/projects/'.$project->getId()->toString());
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_OK);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['project']['workers'])->toHaveCount(0);
});

it('returns 404 when getting non-existent project', function (): void {
    $client = static::createClient();

    $nonExistentId = '550e8400-e29b-41d4-a716-446655440000';

    $client->request('GET', '/api/projects/'.$nonExistentId);

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['error'])->toBe('Project not found');
});

it('validates project name when creating project', function (): void {
    $client = static::createClient();

    // Test empty name
    $client->request('POST', '/api/projects', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'name' => '',
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['violations'])->toHaveKey('name');
});

it('validates project name when renaming project', function (): void {
    $client = static::createClient();

    // First create a project
    /** @var RegisterProjectHandler $projectHandler */
    $projectHandler = self::getContainer()->get(RegisterProjectHandler::class);
    $projectName = 'Project to Rename '.uniqid();
    $registerProjectCommand = RegisterProjectCommand::fromPrimitives($projectName, '550e8400-e29b-41d4-a716-446655440001');
    $project = $projectHandler($registerProjectCommand);

    // Try to rename with invalid name
    $client->request('PUT', '/api/projects/'.$project->getId()->toString(), [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'name' => 'ab', // Too short
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['violations'])->toHaveKey('name');
});

it('validates worker role when adding worker', function (): void {
    $client = static::createClient();

    // Create a project
    /** @var RegisterProjectHandler $projectHandler */
    $projectHandler = self::getContainer()->get(RegisterProjectHandler::class);
    $projectName = 'Role Validation Project '.uniqid();
    $registerProjectCommand = RegisterProjectCommand::fromPrimitives($projectName, '550e8400-e29b-41d4-a716-446655440001');
    $project = $projectHandler($registerProjectCommand);

    // Try to add worker with invalid role
    $client->request('POST', '/api/projects/'.$project->getId()->toString().'/workers', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => '550e8400-e29b-41d4-a716-446655440002',
        'role' => 'invalid_role',
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);

    $responseData = json_decode((string) $client->getResponse()->getContent(), true);
    expect($responseData['violations'])->toHaveKey('role');
});

it('prevents double deletion of project via HTTP API', function (): void {
    $client = static::createClient();

    // Create a project
    /** @var RegisterProjectHandler $projectHandler */
    $projectHandler = self::getContainer()->get(RegisterProjectHandler::class);
    $projectName = 'Double Delete Project '.uniqid();
    $registerProjectCommand = RegisterProjectCommand::fromPrimitives($projectName, '550e8400-e29b-41d4-a716-446655440001');
    $project = $projectHandler($registerProjectCommand);

    $projectId = $project->getId()->toString();

    // Delete the project first time
    $client->request('DELETE', '/api/projects/'.$projectId, [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => '550e8400-e29b-41d4-a716-446655440001',
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Try to delete the project second time - should return error
    $client->request('DELETE', '/api/projects/'.$projectId, [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => '550e8400-e29b-41d4-a716-446655440001',
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR);
});

it('cannot rename deleted project via HTTP API', function (): void {
    $client = static::createClient();

    // Create a project
    /** @var RegisterProjectHandler $projectHandler */
    $projectHandler = self::getContainer()->get(RegisterProjectHandler::class);
    $projectName = 'Project to Delete Then Rename '.uniqid();
    $registerProjectCommand = RegisterProjectCommand::fromPrimitives($projectName, '550e8400-e29b-41d4-a716-446655440001');
    $project = $projectHandler($registerProjectCommand);

    $projectId = $project->getId()->toString();

    // Delete the project
    $client->request('DELETE', '/api/projects/'.$projectId, [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => '550e8400-e29b-41d4-a716-446655440001',
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Try to rename the deleted project
    $client->request('PUT', '/api/projects/'.$projectId, [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'name' => 'New Name for Deleted Project',
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR);
});

it('cannot add worker to deleted project via HTTP API', function (): void {
    $client = static::createClient();

    // Create a project
    /** @var RegisterProjectHandler $projectHandler */
    $projectHandler = self::getContainer()->get(RegisterProjectHandler::class);
    $projectName = 'Project to Delete Then Add Worker '.uniqid();
    $registerProjectCommand = RegisterProjectCommand::fromPrimitives($projectName, '550e8400-e29b-41d4-a716-446655440001');
    $project = $projectHandler($registerProjectCommand);

    $projectId = $project->getId()->toString();

    // Delete the project
    $client->request('DELETE', '/api/projects/'.$projectId, [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => '550e8400-e29b-41d4-a716-446655440001',
    ]));
    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_NO_CONTENT);

    // Try to add worker to deleted project
    $client->request('POST', '/api/projects/'.$projectId.'/workers', [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'userId' => '550e8400-e29b-41d4-a716-446655440002',
        'role' => 'participant',
    ]));

    expect($client->getResponse()->getStatusCode())->toBe(Response::HTTP_INTERNAL_SERVER_ERROR);
});
