<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Command\CreateUserHandler;
use App\User\Application\Command\DeleteUserCommand;
use App\User\Application\Command\DeleteUserHandler;
use App\User\Application\Query\GetUserByIdHandler;
use App\User\Application\Query\GetUserByIdQuery;
use App\User\Domain\Repository\UserRepositoryInterface;

uses(KernelTestCase::class);

it('can create and load user via handlers', function (): void {
    /** @var CreateUserHandler $createHandler */
    $createHandler = self::getContainer()->get(CreateUserHandler::class);
    /** @var GetUserByIdHandler $getHandler */
    $getHandler = self::getContainer()->get(GetUserByIdHandler::class);

    $email = 'test' . uniqid() . '@example.com';
    $command = new CreateUserCommand($email);
    $user = $createHandler($command);

    expect($user->getEmail()->getValue())->toBe($email);
    expect($user->getId())->not()->toBeNull();

    $query = new GetUserByIdQuery($user->getId()->toString());
    $userDto = $getHandler($query);

    expect($userDto)->not()->toBeNull();
    expect($userDto->email)->toBe($email);
    expect($userDto->id)->toBe($user->getId()->toString());
});

it('can soft delete user and user becomes unavailable via regular queries', function (): void {
    /** @var CreateUserHandler $createHandler */
    $createHandler = self::getContainer()->get(CreateUserHandler::class);
    /** @var DeleteUserHandler $deleteHandler */
    $deleteHandler = self::getContainer()->get(DeleteUserHandler::class);
    /** @var GetUserByIdHandler $getHandler */
    $getHandler = self::getContainer()->get(GetUserByIdHandler::class);
    /** @var UserRepositoryInterface $userRepository */
    $userRepository = self::getContainer()->get(UserRepositoryInterface::class);

    // Create user
    $email = 'delete_test' . uniqid() . '@example.com';
    $command = new CreateUserCommand($email);
    $user = $createHandler($command);

    $uuid = $user->getId();

    // Verify user exists
    $query = new GetUserByIdQuery($uuid->toString());
    $userDto = $getHandler($query);
    expect($userDto)->not()->toBeNull();

    // Soft delete user
    $deleteCommand = new DeleteUserCommand($uuid->toString());
    $deleteHandler($deleteCommand);

    // Verify user is not available via regular query
    $userDtoAfterDelete = $getHandler($query);
    expect($userDtoAfterDelete)->toBeNull();

    // Verify user still exists with including deleted method
    $userIncludingDeleted = $userRepository->findByIdIncludingDeleted($uuid);
    expect($userIncludingDeleted)->not()->toBeNull();
    expect($userIncludingDeleted->isDeleted())->toBeTrue();
    expect($userIncludingDeleted->getDeletedAt())->not()->toBeNull();
});

it('soft delete is idempotent - deleting already deleted user does nothing', function (): void {
    /** @var CreateUserHandler $createHandler */
    $createHandler = self::getContainer()->get(CreateUserHandler::class);
    /** @var DeleteUserHandler $deleteHandler */
    $deleteHandler = self::getContainer()->get(DeleteUserHandler::class);
    /** @var UserRepositoryInterface $userRepository */
    $userRepository = self::getContainer()->get(UserRepositoryInterface::class);

    // Create user
    $email = 'idempotent_delete' . uniqid() . '@example.com';
    $command = new CreateUserCommand($email);
    $user = $createHandler($command);

    $uuid = $user->getId();

    // Delete user first time
    $deleteCommand = new DeleteUserCommand($uuid->toString());
    $deleteHandler($deleteCommand);

    $userAfterFirstDelete = $userRepository->findByIdIncludingDeleted($uuid);
    $firstDeleteTime = $userAfterFirstDelete->getDeletedAt();

    // Delete user second time
    $deleteHandler($deleteCommand);

    $userAfterSecondDelete = $userRepository->findByIdIncludingDeleted($uuid);
    $secondDeleteTime = $userAfterSecondDelete->getDeletedAt();

    // Verify delete time didn't change
    expect($secondDeleteTime)->toEqual($firstDeleteTime);
});

it('cannot create user with same email as soft deleted user', function (): void {
    /** @var CreateUserHandler $createHandler */
    $createHandler = self::getContainer()->get(CreateUserHandler::class);
    /** @var DeleteUserHandler $deleteHandler */
    $deleteHandler = self::getContainer()->get(DeleteUserHandler::class);

    // Create user
    $email = 'unique_email_test' . uniqid() . '@example.com';
    $command = new CreateUserCommand($email);
    $user = $createHandler($command);

    // Soft delete user
    $deleteCommand = new DeleteUserCommand($user->getId()->toString());
    $deleteHandler($deleteCommand);

    // Try to create user with same email - should fail
    expect(fn() => $createHandler($command))
        ->toThrow(UserAlreadyExistsException::class);
});
