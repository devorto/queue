<?php

namespace Devorto\Queue;

use DateTime;
use DateTimeZone;
use Devorto\KeyValueStorage\KeyValueStorage;
use Exception;
use InvalidArgumentException;
use RuntimeException;

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
     * @var KeyValueStorage
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
     * @param KeyValueStorage|null $parameters Extra parameters you want to pass along when the command is run.
     * @param DateTime|null $runAfter If left empty current date en time is used.
     * (Note: uses UTC internally, will be converted if provided.).
     */
    public function __construct(
        string $command,
        KeyValueStorage $parameters = null,
        DateTime $runAfter = null
    ) {
        $this->command = $command;
        $this->parameters = $parameters ?? new KeyValueStorage();
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
            if (empty($data[$property])) {
                throw new InvalidArgumentException('Undefined index: ' . $property);
            }

            switch ($property) {
                case 'parameters':
                    if (!($data['parameters'] instanceof KeyValueStorage)) {
                        throw new InvalidArgumentException(
                            'Index "parameters" should be an instanceof: ' . KeyValueStorage::class
                        );
                    }
                    break;
                case 'created':
                    if (!($data['created'] instanceof DateTime)) {
                        throw new InvalidArgumentException(
                            'Index "created" should be an instanceof: ' . DateTime::class
                        );
                    }
                    break;
                case 'runAfter':
                    if (!($data['runAfter'] instanceof DateTime)) {
                        throw new InvalidArgumentException(
                            'Index "runAfter" should be an instanceof: ' . DateTime::class
                        );
                    }
                    break;
            }
        });

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
     * @return KeyValueStorage
     */
    public function getParameters(): KeyValueStorage
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
