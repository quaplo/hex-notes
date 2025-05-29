<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\Command;

use App\Application\User\RegisterUserHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:register-user')]
class RegisterUserCommand extends Command
{
    public function __construct(private RegisterUserHandler $handler)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Registers a new user')
            ->addArgument('email', InputArgument::REQUIRED, 'The email address of the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

        try {
            $this->handler->handle($email);
            $output->writeln("<info>User '$email' registered successfully.</info>");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to register user: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
