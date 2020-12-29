<?php

namespace Devorto\Queue\Commands;

use DateTime;
use DateTimeZone;
use Devorto\Queue\Storage;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
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
     * @var bool
     */
    protected $continueQueueAfterCommandException;

    /**
     * Run constructor.
     *
     * @param Storage $storage
     * @param LoggerInterface $logger
     * @param bool $continueQueueAfterCommandException
     */
    public function __construct(
        Storage $storage,
        LoggerInterface $logger,
        bool $continueQueueAfterCommandException = false
    ) {
        parent::__construct();

        $this->storage = $storage;
        $this->logger = $logger;
        $this->continueQueueAfterCommandException = $continueQueueAfterCommandException;
    }

    /**
     * Configures commands.
     */
    protected function configure()
    {
        $this
            ->setName('queue:run')
            ->setDescription('Run symfony commands in queue.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws Throwable
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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
                $command = $this->getApplication()->find($storageCommand->getCommand());
                $parameters = $storageCommand->getParameters();
                if (empty($parameters)) {
                    $parameters = new ArrayInput(['command' => $storageCommand->getCommand()]);
                }
                $command->run($parameters, new NullOutput());

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
                if ($this->continueQueueAfterCommandException) {
                    $output->writeln('<error>' . $message . '</error>');
                } else {
                    throw $throwable;
                }
            }

            $this->storage->delete($storageCommand);
        }
        
        return 0;
    }
}
