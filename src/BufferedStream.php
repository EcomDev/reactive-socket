<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

class BufferedStream implements Stream
{
    /**
     * @var StreamBuffer
     */
    private $streamBuffer;

    /** @var EventEmitter */
    private $attachedEmitters = [];

    /** @var string[] */
    private $unsentData = [];

    public function __construct(StreamBuffer $streamBuffer)
    {
        $this->streamBuffer = $streamBuffer;
    }

    /** {@inheritdoc} */
    public function attach(EventEmitter $emitter): void
    {
        $this->streamBuffer->attachResourceToEmitter($this, $emitter);
        $this->attachedEmitters[] = $emitter;
    }

    /** {@inheritdoc} */
    public function notifyReadable(StreamObserver $observer): void
    {
        $this->streamBuffer->readFromRemote();
        $observer->handleReadable($this, $this->streamBuffer);

        $this->detachFromAllEmittersWhenConnectionIsBroken();
    }

    /** {@inheritdoc} */
    public function notifyWritable(StreamObserver $observer): void
    {
        $this->streamBuffer->writeToRemote();

        if (!$this->streamBuffer->isRemoteFull()) {
            $observer->handleWritable($this, $this->streamBuffer);
            $this->streamBuffer->writeToRemote();
        }

        $this->detachFromAllEmittersWhenConnectionIsBroken();
    }

    /** {@inheritdoc} */
    public function notifyDisconnected(StreamObserver $observer): void
    {
        $observer->handleDisconnected($this, ...$this->unsentData);
    }

    private function detachFromAllEmittersWhenConnectionIsBroken(): void
    {
        $this->streamBuffer->notifyConnectionClosedOrBroken(function ($unsentData) {
            foreach ($this->attachedEmitters as $emitter) {
                $this->streamBuffer->detachResourceFromEmitter($this, $emitter);
            }

            $this->unsentData = $unsentData;
        });
    }
}
