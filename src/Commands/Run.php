<?php

namespace Devorto\Queue\Commands;

use DateTime;
use DateTimeZone;
use Devorto\DependencyInjection\DependencyInjection;
use Devorto\Queue\Command as QueueCommand;
use Devorto\Queue\Storage;
use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class Run
 *
 * @package Devorto\Queue\Commands
 */
class Run extends Command
{
    /**
     * @var Storage
     */
    protected $storage;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Run constructor.
     *
     * @param Storage $storage
     * @param LoggerInterface $logger
     */
    public function __construct(Storage $storage, LoggerInterface $logger)
    {
        parent::__construct();

        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * Configures commands.
     */
    protected function configure()
    {
        $this
            ->setName('queue:run')
            ->setDescription('Run commands in queue.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $storageCommands = $this->storage->getStorageCommands();

        foreach ($storageCommands as $storageCommand) {
            $created = $storageCommand->getCreated()->setTimezone(new DateTimeZone('UTC'));
            $runAfter = $storageCommand->getRunAfter()->setTimezone(new DateTimeZone('UTC'));

            try {
                $start = (new DateTime('now', new DateTimeZone('UTC')));
            } catch (Exception $exception) {
                throw new RuntimeException('Could not create new DateTime object.', 0, $exception);
            }

            $message = 'ID: ' . $storageCommand->getId() . PHP_EOL;
            $message .= 'Command: ' . $storageCommand->getCommand() . PHP_EOL;
            $message .= 'Created at: ' . $created->format(DateTime::ISO8601) . PHP_EOL;
            $message .= 'Run after: ' . $runAfter->format(DateTime::ISO8601) . PHP_EOL;
            $message .= 'Started at: ' . $start->format(DateTime::ISO8601) . PHP_EOL;

            try {
                if (!is_subclass_of($storageCommand->getCommand(), QueueCommand::class, true)) {
                    throw new LogicException(
                        sprintf(
                            'Class "%s" does not implement "%s".',
                            $storageCommand,
                            QueueCommand::class
                        )
                    );
                }

                $command = DependencyInjection::instantiate($storageCommand->getCommand());
                $command->run($storageCommand->getParameters());

                try {
                    $end = (new DateTime('now', new DateTimeZone('UTC')));
                } catch (Exception $exception) {
                    throw new RuntimeException('Could not create new DateTime object.', 0, $exception);
                }

                $message .= 'Stopped at: ' . $end->format(DateTime::ISO8601) . PHP_EOL;
                $message .= 'Time elapsed: ' . $start->diff($end)->format('%H:%I:%S') . PHP_EOL;
                $message .= 'Status: success';

                $this->logger->info($message);
                $output->writeln('<info>' . $message . '</info>' . PHP_EOL . PHP_EOL);
            } catch (Throwable $throwable) {
                try {
                    $end = (new DateTime('now', new DateTimeZone('UTC')));
                } catch (Exception $exception) {
                    throw new RuntimeException('Could not create new DateTime object.', 0, $exception);
                }

                $message .= 'Stopped at: ' . $end->format(DateTime::ISO8601) . PHP_EOL;
                $message .= 'Time elapsed: ' . $start->diff($end)->format('%H:%I:%S') . PHP_EOL;
                $message .= 'Status: error' . PHP_EOL . PHP_EOL;
                $message .= $throwable;

                $this->logger->critical($message);
                $output->writeln('<error>' . $message . '</error>');
            }

            $this->storage->delete($storageCommand);
        }
    }
}
