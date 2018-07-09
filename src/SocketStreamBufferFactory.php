<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Factory for wrapping
 *
 */
class SocketStreamBufferFactory
{
    /**
     * Default buffer size
     */
    private const BUFFER_512KB = 524288;

    /** @var int */
    private $readBufferSize = self::BUFFER_512KB;

    /** @var int */
    private $writeBufferSize = self::BUFFER_512KB;

    /**
     * Modifies buffer size for reading data
     */
    public function withReadBuffer(int $size): self
    {
        $modifiedFactory = clone $this;
        $modifiedFactory->readBufferSize = $size;
        return $modifiedFactory;
    }

    /**
     * Modifies buffer size for writing data
     */
    public function withWriteBuffer(int $size): self
    {
        $modifiedFactory = clone $this;
        $modifiedFactory->writeBufferSize = $size;
        return $modifiedFactory;
    }

    /**
     * Creates stream buffer from socket
     *
     * @param resource $socket
     */
    public function createFromSocket($socket): SocketStreamBuffer
    {
        stream_set_blocking($socket, false);
        stream_set_write_buffer($socket, 0);
        stream_set_read_buffer($socket, 0);

        return new SocketStreamBuffer($socket, $this->readBufferSize, new WriteBuffer($this->writeBufferSize));
    }
}
