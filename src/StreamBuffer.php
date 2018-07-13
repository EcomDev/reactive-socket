<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

interface StreamBuffer extends StreamClient
{
    /**
     * Attaches stream to an event emitter
     */
    public function attachResourceToEmitter(Stream $stream, EventEmitter $emitter): void;

    /**
     * Detaches stream from an event emitter
     */
    public function detachResourceFromEmitter(Stream $stream, EventEmitter $emitter): void;

    /**
     * Notifies stream observer in case of dropped connection
     */
    public function notifyConnectionClosedOrBroken(callable $listener): void;

    /**
     * Reads data from remote source
     */
    public function readFromRemote(): void;

    /**
     * Writes data to remote source
     */
    public function writeToRemote(): void;

    /**
     * Checks if writes are possible for remote pipe
     */
    public function isRemoteFull(): bool;
}
