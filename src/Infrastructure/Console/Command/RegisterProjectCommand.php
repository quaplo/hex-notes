<?php

namespace App\Infrastructure\Console\Command;

use App\Application\Project\RegisterProjectHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:project:register',
    description: 'Creates a new project.',
)]
final class RegisterProjectCommand extends Command
{
    public function __construct(
        private RegisterProjectHandler $handler
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the project');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $ownerEmail = $input->getArgument('email');

        try {
            $project = $this->handler->handle($name, $ownerEmail);
            $output->writeln(sprintf('<info>"%s"created (ID: %s).</info>', $project->getName(), $project->getId()));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Failed to create project: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
