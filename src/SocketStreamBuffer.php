<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

class SocketStreamBuffer implements StreamBuffer
{
    const BROKEN_PIPE_ERROR = 32;

    /** @var resource */
    private $stream;

    /** @var string */
    private $readBuffer = '';

    /** @var int */
    private $readBufferSize;

    /** @var int */
    private $lastSocketErrorNumber;

    /** @var bool */
    private $isClosed = false;

    /** @var bool  */
    private $isRemoteFull = false;

    /** @var WriteBuffer */
    private $writeBuffer;

    public function __construct($stream, int $readBufferSize, WriteBuffer $writeBuffer)
    {
        $this->stream = $stream;
        $this->readBufferSize = $readBufferSize;
        $this->writeBuffer = $writeBuffer;
    }

    /** {@inheritdoc} */
    public function attachResourceToEmitter(Stream $stream, EventEmitter $emitter): void
    {
        $emitter->attachStream($stream, $this->stream);
    }

    /** {@inheritdoc} */
    public function isRemoteFull(): bool
    {
        return $this->isClosed || $this->isRemoteFull;
    }

    /** {@inheritdoc} */
    public function write(string $data): void
    {
        if ($this->isConnectionNotAvailable()) {
            throw new WriteLimitReachedException();
        }

        $this->writeBuffer->append($data);
    }

    /** {@inheritdoc} */
    public function read(): string
    {
        $buffer = $this->readBuffer;
        $this->readBuffer = '';
        return $buffer;
    }

    /** {@inheritdoc} */
    public function detachResourceFromEmitter(Stream $stream, EventEmitter $emitter): void
    {
        $emitter->detachStream($stream, $this->stream);
    }

    /** {@inheritdoc} */
    public function close(): void
    {
        stream_socket_shutdown($this->stream, STREAM_SHUT_RD);
        $this->isClosed = true;
    }

    /** {@inheritdoc} */
    public function readFromRemote(): void
    {
        $this->catchSocketErrors(function () {
            $currentBufferSize = strlen($this->readBuffer);

            if ($this->readBufferSize > $currentBufferSize) {
                $data = stream_get_contents(
                    $this->stream,
                    $this->readBufferSize - $currentBufferSize
                );
                $this->readBuffer .= $data;
            }
        });
    }
    /** {@inheritdoc} */
    public function writeToRemote(): void
    {
        $this->catchSocketErrors(function () {
            while (!$this->writeBuffer->isEmpty()) {
                $data = $this->writeBuffer->currentValue();
                $size = fwrite($this->stream, $data);

                $this->isRemoteFull = $size < strlen($data);

                if ($size === false || $size === 0) {
                    break;
                }

                $this->writeBuffer->handleWrittenBytes($size);
            }
        });
    }


    /** {@inheritdoc} */
    public function notifyConnectionClosedOrBroken(callable $listener): void
    {
        if ($this->isConnectionNotAvailable()) {
            $listener($this->writeBuffer->flushNotWrittenItems());
        }
    }

    private function catchSocketErrors(callable $operation): void
    {
        set_error_handler(function ($errorNumber, $errorText) {
            $socketErrorPosition = strpos($errorText, 'errno=');

            if ($errorNumber === E_NOTICE && $socketErrorPosition !== false) {
                $this->lastSocketErrorNumber = (int)substr(
                    $errorText,
                    strpos($errorText, '=', $socketErrorPosition) + 1,
                    3
                );
            }
        });

        $operation();

        restore_error_handler();
    }

    private function isConnectionNotAvailable(): bool
    {
        return $this->isClosed || $this->lastSocketErrorNumber === self::BROKEN_PIPE_ERROR;
    }
}
