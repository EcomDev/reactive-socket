<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Reactive Socket Stream
 *
 */
interface Stream
{
    /**
     * Asks stream to subscribe its system resource when ready
     */
    public function attach(EventEmitter $emitter): void;

    /**
     * Notification is invoked when resource stream is available for read operations
     */
    public function notifyReadable(StreamObserver $observer): void;

    /**
     * Notification is invoked when resource stream is available for write operations
     */
    public function notifyWritable(StreamObserver $observer): void;

    /**
     * Notifies an observer that underlying stream has been disconnected
     */
    public function notifyDisconnected(StreamObserver $observer): void;
}
