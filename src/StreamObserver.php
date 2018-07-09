<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Stream observer
 */
interface StreamObserver
{
    /**
     * Invoked when stream has been connected to an event emitter
     */
    public function handleConnected(Stream $stream): void;

    /**
     * Invoked when stream is available for writing
     */
    public function handleWritable(Stream $stream, StreamClient $client): void;

    /**
     * Invoked when stream is available for reading
     */
    public function handleReadable(Stream $stream, StreamClient $client): void;

    /**
     * Invoked when stream has been closed manually or in the result of error
     *
     * Any data that have not been sent by a stream will be returned as $unsentData parameter
     */
    public function handleDisconnected(Stream $stream, string ...$unsentData): void;
}
