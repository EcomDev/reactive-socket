<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

class StreamSelectEventEmitter implements EventEmitter
{
    /** @var callable */
    private $worker;

    /** @var callable */
    private $idleWorker;

    /**
     * @var StreamObserver
     */
    private $streamObserver;

    /** @var Stream[] */
    private $streams = [];

    /** @var resource[] */
    private $watchedResources = [];

    public function __construct(
        callable $worker,
        callable $idleWorker,
        StreamObserver $streamObserver
    ) {
        $this->worker = $worker;
        $this->idleWorker = $idleWorker;
        $this->streamObserver = $streamObserver;
    }

    /** {@inheritdoc} */
    public function attachStream(Stream $stream, $resource): void
    {
        $this->streamObserver->handleConnected($stream);
        $this->streams[(int)$resource] = $stream;
        $this->watchedResources[(int)$resource] = $resource;
    }

    /** {@inheritdoc} */
    public function detachStream(Stream $stream, $resource): void
    {
        $stream->notifyDisconnected($this->streamObserver);
        unset($this->streams[(int)$resource], $this->watchedResources[(int)$resource]);
    }

    /**
     * Executed on every tick by event loop
     */
    public function __invoke()
    {
        call_user_func($this->worker);

        $this->executeIdleWhenNoResourceEvents($this->watchedResources);

        if ($this->watchedResources) {
            $this->watchForChangesInStreams();
        }
    }

    private function watchForChangesInStreams(): void
    {
        $readStreams = $this->watchedResources;
        $writeStreams = $this->watchedResources;
        $exceptStreams = null;

        if (stream_select($readStreams, $writeStreams, $exceptStreams, 0, 0)) {
            foreach ($readStreams as $resource) {
                $this->streams[(int)$resource]->notifyReadable($this->streamObserver);
            }

            $this->executeIdleWhenNoResourceEvents($readStreams);

            foreach ($writeStreams as $resource) {
                $this->streams[(int)$resource]->notifyWritable($this->streamObserver);
            }
        }
    }

    private function executeIdleWhenNoResourceEvents($matchedResources): void
    {
        if (!$matchedResources) {
            call_user_func($this->idleWorker);
        }
    }
}
