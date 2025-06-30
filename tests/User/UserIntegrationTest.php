<?php

declare(strict_types=1);

putenv('DATABASE_URL=sqlite:///' . __DIR__ . '/../../var/test.db');

use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Command\CreateUserHandler;
use App\User\Application\Query\GetUserByIdHandler;
use App\User\Application\Query\GetUserByIdQuery;
use App\Shared\ValueObject\Email;

uses(Symfony\Bundle\FrameworkBundle\Test\KernelTestCase::class);

it('can create and load user via handlers', function () {
    /** @var CreateUserHandler $createHandler */
    $createHandler = self::getContainer()->get(CreateUserHandler::class);
    /** @var GetUserByIdHandler $getHandler */
    $getHandler = self::getContainer()->get(GetUserByIdHandler::class);

    $email = 'testuser@example.com';
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