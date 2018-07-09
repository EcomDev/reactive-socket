<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Event emitter
 */
interface EventEmitter
{
    /**
     * Attaches stream to event emitter
     */
    public function attachStream(Stream $stream, $resource): void;

    /**
     * Detaches stream from event emitter
     */
    public function detachStream(Stream $stream, $resource): void;
}
