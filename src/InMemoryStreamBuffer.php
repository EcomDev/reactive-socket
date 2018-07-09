<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

class InMemoryStreamBuffer implements StreamBuffer
{
    /** @var string */
    private $stream = '';

    /** @var string */
    private $readBuffer = '';

    /** @var WriteBuffer */
    private $writeBuffer;

    /** @var int */
    private $readBufferSize;

    /** @var int */
    private $chunkSize;

    /** @var bool */
    private $isClosed = false;

    /** @var bool */
    private $isRemoteFull = false;

    /** @var mixed */
    private $resource;

    public function __construct(int $chunkSize, int $readBufferSize, WriteBuffer $writeBuffer, $resource)
    {
        $this->writeBuffer = $writeBuffer;
        $this->readBufferSize = $readBufferSize;
        $this->chunkSize = $chunkSize;
        $this->resource = $resource;
    }

    /** {@inheritdoc} */
    public function attachResourceToEmitter(Stream $stream, EventEmitter $emitter): void
    {
        if ($this->resource === null) {
            return;
        }

        $emitter->attachStream($stream, $this->resource);
    }

    /** {@inheritdoc} */
    public function detachResourceFromEmitter(Stream $stream, EventEmitter $emitter): void
    {
        if ($this->resource === null) {
            return;
        }

        $emitter->detachStream($stream, $this->resource);
    }

    /** {@inheritdoc} */
    public function notifyConnectionClosedOrBroken(callable $listener): void
    {
        if ($this->isClosed) {
            $listener($this->writeBuffer->flushNotWrittenItems());
        }
    }

    /** {@inheritdoc} */
    public function readFromRemote(): void
    {
        $remainingBufferSize = $this->readBufferSize - strlen($this->readBuffer);
        $readSize = min(strlen($this->stream), $remainingBufferSize);
        $this->readBuffer .= substr($this->stream, 0, $readSize);
        $this->stream = substr($this->stream, $readSize);
    }

    /** {@inheritdoc} */
    public function writeToRemote(): void
    {
        if ($this->isClosed) {
            return;
        }

        $remainingBytes = $this->chunkSize - strlen($this->stream);
        $this->isRemoteFull = $remainingBytes === 0;

        while (!$this->writeBuffer->isEmpty() && $remainingBytes > 0) {
            $item = $this->writeBuffer->currentValue();
            $dataToWrite = min(strlen($item), $remainingBytes);
            $remainingBytes -= $dataToWrite;
            $this->stream .= substr($item, 0, $dataToWrite);
            $this->writeBuffer->handleWrittenBytes($dataToWrite);
        }
    }

    /** {@inheritdoc} */
    public function isRemoteFull(): bool
    {
        return $this->isClosed || $this->isRemoteFull;
    }

    /** {@inheritdoc} */
    public function write(string $data): void
    {
        if ($this->isClosed) {
            throw new WriteLimitReachedException();
        }

        $this->writeBuffer->append($data);
    }

    /** {@inheritdoc} */
    public function read(): string
    {
        $read = $this->readBuffer;
        $this->readBuffer = '';
        return $read;
    }

    /** {@inheritdoc} */
    public function close(): void
    {
        $this->isClosed = true;
    }
}
