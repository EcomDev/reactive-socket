<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

class BufferedStreamFactory
{
    public function createFromBuffer(StreamBuffer $streamBuffer): Stream
    {
        return new BufferedStream($streamBuffer);
    }
}
