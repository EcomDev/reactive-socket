<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Composite observer
 *
 * Simply notifies observers assigned internally
 */
class CompositeStreamObserver implements StreamObserver
{
    /**
     * @var StreamObserver[]
     */
    private $observers;

    public function __construct(array $observers)
    {
        $this->observers = $observers;
    }

    /**
     * Creates composite from passed stream observers
     */
    public static function createFromObservers(StreamObserver ...$observers)
    {
        return new self($observers);
    }

    /** {@inheritdoc} */
    public function handleConnected(Stream $stream): void
    {
        foreach ($this->observers as $observer) {
            $observer->handleConnected($stream);
        }
    }

    /** {@inheritdoc} */
    public function handleWritable(Stream $stream, StreamClient $client): void
    {
        foreach ($this->observers as $observer) {
            $observer->handleWritable($stream, $client);
        }
    }

    /** {@inheritdoc} */
    public function handleReadable(Stream $stream, StreamClient $client): void
    {
        foreach ($this->observers as $observer) {
            $observer->handleReadable($stream, $client);
        }
    }

    /** {@inheritdoc} */
    public function handleDisconnected(Stream $stream, string ...$unsentData): void
    {
        foreach ($this->observers as $observer) {
            $observer->handleDisconnected($stream, ...$unsentData);
        }
    }
}
