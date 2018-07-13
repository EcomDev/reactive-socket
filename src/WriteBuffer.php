<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/** {@internal} */
class WriteBuffer
{
    /** @var array  */
    private $buffer;

    /** @var int */
    private $bufferLimit;

    /** @var int */
    private $bufferSize;

    /** @var string */
    private $currentValue;

    /**
     * Creates buffer with limit
     *
     */
    public function __construct(int $limit)
    {
        $this->bufferLimit = $limit;
        $this->bufferSize = 0;
        $this->currentValue = '';
        $this->buffer = [];
    }

    /**
     * Appends data to buffer
     *
     * @throws WriteLimitReachedException when buffer size is too low
     */
    public function append(string $data)
    {
        $appendSize = strlen($data);

        if ($appendSize + $this->bufferSize > $this->bufferLimit) {
            throw new WriteLimitReachedException();
        }

        $this->bufferSize += $appendSize;
        $this->buffer[] = $data;
    }

    /**
     * Checks if buffer is empty
     */
    public function isEmpty(): bool
    {
        return $this->bufferSize === 0;
    }

    /**
     * Returns current value to write from buffer
     */
    public function currentValue(): string
    {
        if (!$this->currentValue) {
            $this->currentValue = $this->buffer[0] ?? '';
        }

        return $this->currentValue;
    }

    /**
     * Updates buffer with information about written data to remote
     */
    public function handleWrittenBytes(int $bytes)
    {
        $this->currentValue = substr($this->currentValue, $bytes);
        $this->bufferSize -= $bytes;

        if ($this->currentValue === '') {
            array_shift($this->buffer);
        }
    }

    /**
     * Returns data that was not yet written in complete
     */
    public function flushNotWrittenItems(): array
    {
        $notWrittenItems = $this->buffer;
        $this->buffer = [];
        return $notWrittenItems;
    }
}
