<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Fake implementation of stream client
 */
class FakeStreamClient implements StreamClient
{
    /** @var int */
    private $dataLimit;

    /** @var string[] */
    private $data = [];

    /** @var int */
    private $currentSize = 0;

    /** @var bool */
    private $isClosed = false;

    public function __construct(int $dataLimit)
    {
        $this->dataLimit = $dataLimit;
    }

    /**
     * Creates stream client with defined limited data write
     */
    public static function create(int $dataLimit)
    {
        return new self($dataLimit);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $data): void
    {
        $dataLength = strlen($data);

        $isBufferFull = $this->currentSize + $dataLength > $this->dataLimit;

        if ($this->isClosed || $isBufferFull) {
            throw new WriteLimitReachedException();
        }

        $this->data[] = $data;
        $this->currentSize += $dataLength;
    }

    /**
     * {@inheritdoc}
     */
    public function read(): string
    {
        if (!$this->data) {
            return '';
        }

        $bufferItem = array_shift($this->data);
        $this->currentSize -= strlen($bufferItem);

        return $bufferItem;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->isClosed = true;
    }
}
