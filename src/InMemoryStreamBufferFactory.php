<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

class InMemoryStreamBufferFactory
{
    /**
     * Default buffer size
     */
    private const BUFFER_64KB = 65536;

    /** @var int */
    private $writeBufferSize = self::BUFFER_64KB;

    /** @var int */
    private $readBufferSize = self::BUFFER_64KB;

    /** @var int */
    private $chunkSize = self::BUFFER_64KB;

    /** @var mixed */
    private $resource;

    public function withWriteBuffer(int $size): self
    {
        $factory = clone $this;
        $factory->writeBufferSize = $size;
        return $factory;
    }

    public function withReadBuffer(int $size): self
    {
        $factory = clone $this;
        $factory->readBufferSize = $size;
        return $factory;
    }

    public function withChunkSize(int $size): self
    {
        $factory = clone $this;
        $factory->chunkSize = $size;
        return $factory;
    }

    /**
     * Adds custom resource to an object
     * for attaching into event emitter
     *
     * The resource IS NOT handled in any way by the buffer
     */
    public function withCustomResource($resource): self
    {
        $factory = clone $this;
        $factory->resource = $resource;
        return $factory;
    }

    public function create(): InMemoryStreamBuffer
    {
        return new InMemoryStreamBuffer(
            $this->chunkSize,
            $this->readBufferSize,
            new WriteBuffer($this->writeBufferSize),
            $this->resource
        );
    }
}
