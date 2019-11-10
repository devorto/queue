<?php

namespace Devorto\Queue;

use Devorto\KeyValueStorage\KeyValueStorage;

/**
 * Interface Command
 *
 * @package Devorto\Queue
 */
interface Command
{
    /**
     * Run/execute the command.
     *
     * @param KeyValueStorage $storage
     */
    public function run(KeyValueStorage $storage): void;
}
