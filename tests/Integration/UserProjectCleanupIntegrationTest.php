<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Project\Application\Command\RegisterProjectCommand;
use App\Project\Application\Command\RegisterProjectHandler;
use App\Project\Application\Query\GetProjectQuery;
use App\Project\Application\Query\GetProjectHandler;
use App\User\Application\Command\CreateUserCommand;
use App\User\Application\Command\CreateUserHandler;
use App\User\Application\Command\DeleteUserCommand;
use App\User\Application\Command\DeleteUserHandler;
use App\User\Application\Query\GetUserByIdQuery;
use App\User\Application\Query\GetUserByIdHandler;
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

    public function test_deleting_user_triggers_orphaned_projects_cleanup(): void
    {
        // Given: Create a user
        $userEmail = 'test-owner-' . uniqid() . '@example.com';
        
        $user = ($this->createUserHandler)(new CreateUserCommand($userEmail));
        $uuid = $user->getId();

        // Verify user was created
        $userDto = ($this->getUserByIdHandler)(new GetUserByIdQuery($uuid->toString()));
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
        ($this->deleteUserHandler)(new DeleteUserCommand($uuid->toString()));

        // Then: User should be soft deleted
        $deletedUser = ($this->getUserByIdHandler)(new GetUserByIdQuery($uuid->toString()));
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

    public function test_deleting_user_with_no_projects_works_without_errors(): void
    {
        // Given: Create a user with no projects
        $userEmail = 'no-projects-' . uniqid() . '@example.com';
        
        $user = ($this->createUserHandler)(new CreateUserCommand($userEmail));
        $uuid = $user->getId();

        // Verify user was created
        $userDto = ($this->getUserByIdHandler)(new GetUserByIdQuery($uuid->toString()));
        expect($userDto)->not()->toBeNull();

        // When: Delete the user (no projects to clean up)
        ($this->deleteUserHandler)(new DeleteUserCommand($uuid->toString()));

        // Then: User should be soft deleted without any errors
        $deletedUser = ($this->getUserByIdHandler)(new GetUserByIdQuery($uuid->toString()));
        expect($deletedUser)->toBeNull();
    }

    public function test_multiple_users_with_projects_cleanup_independently(): void
    {
        // Given: Create two users
        $user1 = ($this->createUserHandler)(new CreateUserCommand('user1-' . uniqid() . '@example.com'));
        $user2 = ($this->createUserHandler)(new CreateUserCommand('user2-' . uniqid() . '@example.com'));
        
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
        ($this->deleteUserHandler)(new DeleteUserCommand($uuid->toString()));

        // Then: Only user1's projects should be cleaned up
        $user1ProjectAfterDeletion = ($this->getProjectHandler)(GetProjectQuery::fromPrimitives($user1Project->getId()->toString()));
        $user2ProjectAfterDeletion = ($this->getProjectHandler)(GetProjectQuery::fromPrimitives($user2Project->getId()->toString()));
        
        expect($user1ProjectAfterDeletion)->toBeNull(); // User1's project cleaned up
        expect($user2ProjectAfterDeletion)->not()->toBeNull(); // User2's project remains
        expect($user2ProjectAfterDeletion->getOwnerId())->toEqual($user2Id);

        // And user2 should still exist
        $user2AfterUser1Deletion = ($this->getUserByIdHandler)(new GetUserByIdQuery($user2Id->toString()));
        expect($user2AfterUser1Deletion)->not()->toBeNull();
    }
}