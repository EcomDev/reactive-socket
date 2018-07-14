<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Event emitter builder
 */
interface EventEmitterBuilder
{
    /**
     * Starts a new configuration chain for emitter builder
     */
    public function anEmitter(): self;

    /**
     * Adds an observer that handles I/O events on sockets
     */
    public function addStreamObserver(StreamObserver $streamObserver): self;

    /**
     * Invoked when after all I/O events processed, can be next loop run
     *
     * A good example of usage is doing some slow blocking action based on queued list of tasks
     */
    public function addWorker(callable $worker): self;

    /**
     * Invoked when system does not have incoming I/O events
     *
     * A good example of task added here is cleaning up hot in memory cache, syncing data to disc, etc
     */
    public function addIdleWorker(callable $worker): self;

    /**
     * Builds an event emitter
     */
    public function build(): EventEmitter;
}
