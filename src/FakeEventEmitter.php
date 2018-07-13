<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Fake event emitter
 *
 * Can be easily used to test application bootstrap logic related
 * to registering new event streams
 */
class FakeEventEmitter implements EventEmitter
{
    private $attachedStreams = [];

    /** {@inheritdoc} */
    public function attachStream(Stream $stream, $resource): void
    {
        $this->attachedStreams[spl_object_hash($stream)] = $resource;
    }

    /** {@inheritdoc} */
    public function detachStream(Stream $stream, $resource): void
    {
        if ($this->hasStreamWithResource($stream, $resource)) {
            unset($this->attachedStreams[spl_object_hash($stream)]);
        }
    }

    /**
     * Checks if stream has been added to the event emitter
     */
    public function hasStreamWithResource(Stream $stream, $resource): bool
    {
        $streamId = spl_object_hash($stream);

        return isset($this->attachedStreams[$streamId]) && $this->attachedStreams[$streamId] === $resource;
    }

    /**
     * Checks if stream has been added to event emitter
     */
    public function hasStream(Stream $stream): bool
    {
        $streamId = spl_object_hash($stream);

        return isset($this->attachedStreams[$streamId]);
    }

    /**
     * Checks if stream is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->attachedStreams);
    }
}
