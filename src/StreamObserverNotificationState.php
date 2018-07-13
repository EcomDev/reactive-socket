<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Makes it possible to compare two states of observations
 *
 */
class StreamObserverNotificationState
{
    /** @var array */
    private $state = [];

    /**
     * Creates empty notification state
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * Adds notification of connected stream
     */
    public function withConnectedNotification(Stream $stream): self
    {
        return $this->duplicateWithState('connected', $stream);
    }

    /**
     * Adds notification of disconnected stream
     */
    public function withDisconnectedNotification(Stream $stream, array $unsentData): self
    {
        return $this->duplicateWithState('disconnected', $stream, $unsentData);
    }

    /**
     * Adds notification of writable stream
     */
    public function withWritableNotification(Stream $stream): self
    {
        return $this->duplicateWithState('writable', $stream);
    }

    /**
     * Adds notification of readable stream
     */
    public function withReadableNotification(Stream $stream, string $data): self
    {
        return $this->duplicateWithState('readable', $stream, $data);
    }

    /**
     * Exports state as JSON string
     */
    public function __toString()
    {
        $info = [];
        foreach ($this->state as list($type, $stream, $data)) {
            $row = [$type, spl_object_hash($stream)];
            if ($data !== null) {
                $row[] = $data;
            }
            $info[] = $row;
        }

        return json_encode($info, JSON_PRETTY_PRINT);
    }

    private function duplicateWithState(string $type, Stream $stream, $data = null): StreamObserverNotificationState
    {
        $duplicate = clone $this;
        $duplicate->state[] = [$type, $stream, $data];
        return $duplicate;
    }
}
