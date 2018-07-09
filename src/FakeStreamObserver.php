<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Stream observer for testing purposes
 */
class FakeStreamObserver implements StreamObserver
{
    /**
     * List of data to write on handle writable call
     *
     * @var string[]
     */
    private $write = [];

    /**
     * State of processed notifications
     *
     * @var StreamObserverNotificationState
     */
    private $notificationState;

    public function __construct(StreamObserverNotificationState $notificationState = null)
    {
        $this->notificationState = $notificationState ?? StreamObserverNotificationState::createEmpty();
    }

    /** {@inheritdoc} */
    public function handleConnected(Stream $stream): void
    {
        $this->notificationState = $this->notificationState->withConnectedNotification($stream);
    }

    /** {@inheritdoc} */
    public function handleWritable(Stream $stream, StreamClient $client): void
    {
        foreach ($this->write as $data) {
            $client->write($data);
        }

        $this->notificationState = $this->notificationState->withWritableNotification($stream);
    }

    /** {@inheritdoc} */
    public function handleReadable(Stream $stream, StreamClient $client): void
    {
        $this->notificationState = $this->notificationState->withReadableNotification($stream, $client->read());
    }

    /** {@inheritdoc} */
    public function handleDisconnected(Stream $stream, string ...$unsentData): void
    {
        $this->notificationState = $this->notificationState->withDisconnectedNotification($stream, $unsentData);
    }

    /**
     * Creates instance with write on handle writable
     */
    public function withWrite(string $data): self
    {
        $observer = clone $this;
        $observer->write[] = $data;
        return $observer;
    }

    /**
     * Fetches notification state for validation
     */
    public function fetchNotifications(): StreamObserverNotificationState
    {
        return $this->notificationState;
    }
}
