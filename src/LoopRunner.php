<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Loop runner abstraction
 *
 * Allows to combine multiple tick handlers from multiple event emitters.
 * It is not required for event emitter implementation, in case if emitter already has everything required.
 */
interface LoopRunner
{
    /**
     * Attaches tick handler to loop
     */
    public function attachTickHandler(callable $handler): void;
}
