<?php

namespace Devorto\Queue;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class StorageCommand
 *
 * @package Devorto\Queue
 */
class StorageCommand
{
    /**
     * @var string|null Index/key or whatever you want to use to identify this specific command in the system.
     * Will only be set for existing commands create from dataset, see FromArray method.
     */
    protected $id;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var ArrayInput|null
     */
    protected $parameters;

    /**
     * @var DateTime
     */
    protected $created;

    /**
     * @var DateTime
     */
    protected $runAfter;

    /**
     * StorageCommand constructor.
     *
     * @param string $command The actual command (implementing Command interface) you want to run.
     * (Recommended way of using this is passing the class as string like Command::class).
     * @param ArrayInput|null $parameters Extra parameters you want to pass along when the command is run.
     * @param DateTime|null $runAfter If left empty current date en time is used.
     * (Note: uses UTC internally, will be converted if provided.).
     */
    public function __construct(
        string $command,
        ArrayInput $parameters = null,
        DateTime $runAfter = null
    ) {
        $this->command = $command;
        $this->parameters = $parameters;
        try {
            $this->created = new DateTime('now', new DateTimeZone('UTC'));
            if (empty($runAfter)) {
                $this->runAfter = new DateTime('now', new DateTimeZone('UTC'));
            } else {
                $runAfter->setTimezone(new DateTimeZone('UTC'));
            }
        } catch (Exception $exception) {
            throw new RuntimeException('Could not create new DateTime object.', 0, $exception);
        }
    }

    /**
     * Creates a new StorageCommand from data set.
     *
     * @param array $data
     *
     * @return StorageCommand
     */
    public static function createFromArray(array $data): StorageCommand
    {
        $properties = ['id', 'command', 'parameters', 'created', 'runAfter'];
        array_walk($properties, function (string $property) use ($data) {
            switch ($property) {
                case 'id':
                    if (empty($data['id'])) {
                        throw new InvalidArgumentException('Data "id" can\'t be empty.');
                    }
                    break;
                case 'command':
                    if (empty($data['command'])) {
                        throw new InvalidArgumentException('Data "command" can\'t ben empty.');
                    }
                    break;
                case 'parameters':
                    if (!empty($data['parameters']) && !($data['parameters'] instanceof ArrayInput)) {
                        throw new InvalidArgumentException(
                            'Data "parameters" should be an instanceof: ' . ArrayInput::class
                        );
                    }
                    break;
                case 'created':
                    if (!($data['created'] instanceof DateTime)) {
                        throw new InvalidArgumentException(
                            'Data "created" should be an instanceof: ' . DateTime::class
                        );
                    }
                    break;
                case 'runAfter':
                    if (!empty($data['runAfter']) && !($data['runAfter'] instanceof DateTime)) {
                        throw new InvalidArgumentException(
                            'Data "runAfter" should be an instanceof: ' . DateTime::class
                        );
                    }
                    break;
            }
        });

        if (empty($data['parameters'])) {
            $data['parameters'] = null;
        }

        if (empty($data['runAfter'])) {
            $data['runAfter'] = null;
        }

        $storageCommand = new static($data['command'], $data['parameters'], $data['runAfter']);
        $storageCommand->id = $data['id'];
        $storageCommand->created = $data['created'];

        return $storageCommand;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return ArrayInput|null
     */
    public function getParameters(): ?ArrayInput
    {
        return $this->parameters;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return DateTime
     */
    public function getRunAfter(): DateTime
    {
        return $this->runAfter;
    }
}
