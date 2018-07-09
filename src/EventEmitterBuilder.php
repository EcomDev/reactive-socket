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
    public function withStreamObserver(StreamObserver $streamHandler): self;

    /**
     * Invoked when system after all I/O events processed in a single loop run
     *
     * A good example of usage is doing some slow blocking action based on queued list of tasks
     */
    public function withWorker(callable $worker): self;

    /**
     * Invoked when system does not perform any socket I/O operations
     *
     * A good example of task added here is cleaning up cache based on access time
     */
    public function withIdleWorker(callable $worker): self;

    /**
     * Builds an event emitter
     */
    public function build(): EventEmitter;
}
