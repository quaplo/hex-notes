<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Project\Application\Command\Register\RegisterProjectCommand;
use App\Project\Application\Command\Register\RegisterProjectHandler;
use App\Project\Application\Query\Get\GetProjectHandler;
use App\Project\Application\Query\Get\GetProjectQuery;
use App\User\Application\Command\Create\CreateUserCommand;
use App\User\Application\Command\Create\CreateUserHandler;
use App\User\Application\Command\Delete\DeleteUserCommand;
use App\User\Application\Command\Delete\DeleteUserHandler;
use App\User\Application\Query\Get\GetUserByIdHandler;
use App\User\Application\Query\Get\GetUserByIdQuery;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserProjectCleanupIntegrationTest extends KernelTestCase
{
    private CreateUserHandler $createUserHandler;
    private DeleteUserHandler $deleteUserHandler;
    private GetUserByIdHandler $getUserByIdHandler;
    private RegisterProjectHandler $registerProjectHandler;
    private GetProjectHandler $getProjectHandler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->createUserHandler = $container->get(CreateUserHandler::class);
        $this->deleteUserHandler = $container->get(DeleteUserHandler::class);
        $this->getUserByIdHandler = $container->get(GetUserByIdHandler::class);
        $this->registerProjectHandler = $container->get(RegisterProjectHandler::class);
        $this->getProjectHandler = $container->get(GetProjectHandler::class);
    }

    public function testDeletingUserTriggersOrphanedProjectsCleanup(): void
    {
        // Given: Create a user
        $userEmail = 'test-owner-'.uniqid().'@example.com';

        $user = ($this->createUserHandler)(CreateUserCommand::fromPrimitives($userEmail));
        $uuid = $user->getId();

        // Verify user was created
        $userDto = ($this->getUserByIdHandler)(GetUserByIdQuery::fromPrimitives($uuid->toString()));
        expect($userDto)->not()->toBeNull();
        expect($userDto->id)->toEqual($uuid->toString());

        // Given: Create projects owned by this user
        $project1 = ($this->registerProjectHandler)(RegisterProjectCommand::fromPrimitives(
            'User Project 1',
            $uuid->toString()
        ));

        $project2 = ($this->registerProjectHandler)(RegisterProjectCommand::fromPrimitives(
            'User Project 2',
            $uuid->toString()
        ));

        // Verify projects were created and are accessible
        $project1Query = ($this->getProjectHandler)(GetProjectQuery::fromPrimitives($project1->getId()->toString()));
        $project2Query = ($this->getProjectHandler)(GetProjectQuery::fromPrimitives($project2->getId()->toString()));

        expect($project1Query)->not()->toBeNull();
        expect($project2Query)->not()->toBeNull();
        expect($project1Query->getOwnerId())->toEqual($uuid);
        expect($project2Query->getOwnerId())->toEqual($uuid);

        // When: Delete the user (triggers event-driven cleanup)
        ($this->deleteUserHandler)(DeleteUserCommand::fromPrimitives($uuid->toString()));

        // Then: User should be soft deleted
        $deletedUser = ($this->getUserByIdHandler)(GetUserByIdQuery::fromPrimitives($uuid->toString()));
        expect($deletedUser)->toBeNull(); // Soft deleted users are not returned by queries

        // Then: Projects should be cleaned up automatically via event handlers
        // Note: In a real system with asynchronous event handling, you might need to wait
        // or trigger the event handlers manually. For this test, assuming synchronous handling.

        $cleanedProject1 = ($this->getProjectHandler)(GetProjectQuery::fromPrimitives($project1->getId()->toString()));
        $cleanedProject2 = ($this->getProjectHandler)(GetProjectQuery::fromPrimitives($project2->getId()->toString()));

        // Projects owned by deleted users should be deleted (business rule)
        expect($cleanedProject1)->toBeNull();
        expect($cleanedProject2)->toBeNull();
    }

    public function testDeletingUserWithNoProjectsWorksWithoutErrors(): void
    {
        // Given: Create a user with no projects
        $userEmail = 'no-projects-'.uniqid().'@example.com';

        $user = ($this->createUserHandler)(CreateUserCommand::fromPrimitives($userEmail));
        $uuid = $user->getId();

        // Verify user was created
        $userDto = ($this->getUserByIdHandler)(GetUserByIdQuery::fromPrimitives($uuid->toString()));
        expect($userDto)->not()->toBeNull();

        // When: Delete the user (no projects to clean up)
        ($this->deleteUserHandler)(DeleteUserCommand::fromPrimitives($uuid->toString()));

        // Then: User should be soft deleted without any errors
        $deletedUser = ($this->getUserByIdHandler)(GetUserByIdQuery::fromPrimitives($uuid->toString()));
        expect($deletedUser)->toBeNull();
    }

    public function testMultipleUsersWithProjectsCleanupIndependently(): void
    {
        // Given: Create two users
        $user1 = ($this->createUserHandler)(CreateUserCommand::fromPrimitives('user1-'.uniqid().'@example.com'));
        $user2 = ($this->createUserHandler)(CreateUserCommand::fromPrimitives('user2-'.uniqid().'@example.com'));

        $uuid = $user1->getId();
        $user2Id = $user2->getId();

        // Given: Create projects for each user
        $user1Project = ($this->registerProjectHandler)(RegisterProjectCommand::fromPrimitives(
            'User 1 Project',
            $uuid->toString()
        ));

        $user2Project = ($this->registerProjectHandler)(RegisterProjectCommand::fromPrimitives(
            'User 2 Project',
            $user2Id->toString()
        ));

        // When: Delete only user1
        ($this->deleteUserHandler)(DeleteUserCommand::fromPrimitives($uuid->toString()));

        // Then: Only user1's projects should be cleaned up
        $user1ProjectAfterDeletion = ($this->getProjectHandler)(GetProjectQuery::fromPrimitives($user1Project->getId()->toString()));
        $user2ProjectAfterDeletion = ($this->getProjectHandler)(GetProjectQuery::fromPrimitives($user2Project->getId()->toString()));

        expect($user1ProjectAfterDeletion)->toBeNull(); // User1's project cleaned up
        expect($user2ProjectAfterDeletion)->not()->toBeNull(); // User2's project remains
        expect($user2ProjectAfterDeletion->getOwnerId())->toEqual($user2Id);

        // And user2 should still exist
        $user2AfterUser1Deletion = ($this->getUserByIdHandler)(GetUserByIdQuery::fromPrimitives($user2Id->toString()));
        expect($user2AfterUser1Deletion)->not()->toBeNull();
    }
}
