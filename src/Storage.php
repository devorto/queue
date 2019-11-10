<?php

namespace Devorto\Queue;

/**
 * Interface Storage
 *
 * @package Devorto\Queue
 */
interface Storage
{
    /**
     * Get the commands in queue.
     *
     * @return StorageCommand[] Should only return commands that are older then "runAfter" property.
     * Unless your own logic requires otherwise.
     */
    public function getStorageCommands(): array;

    /**
     * Adds a new StorageCommand to queue.
     *
     * @param StorageCommand $command
     */
    public function add(StorageCommand $command): void;

    /**
     * Deletes a StorageCommand from queue.
     *
     * @param StorageCommand $command
     */
    public function delete(StorageCommand $command): void;
}
