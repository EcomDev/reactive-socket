<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

class NullStreamStub implements Stream
{
    /**
     * Unique stream id for test reports
     *
     * @var string
     */
    private $streamId;

    public function __construct()
    {
        $this->streamId = uniqid();
    }

    /** {@inheritdoc} */
    public function attach(EventEmitter $emitter): void
    {
    }

    /** {@inheritdoc} */
    public function notifyReadable(StreamObserver $observer): void
    {
    }

    /** {@inheritdoc} */
    public function notifyWritable(StreamObserver $observer): void
    {
    }

    /** {@inheritdoc} */
    public function notifyDisconnected(StreamObserver $observer): void
    {
    }
}
