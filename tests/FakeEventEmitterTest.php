<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

use PHPUnit\Framework\TestCase;

class FakeEventEmitterTest extends TestCase
{
    /** @var FakeEventEmitter */
    private $eventEmitter;

    /** @var NullStreamStub */
    private $stream;

    protected function setUp()
    {
        $this->eventEmitter = new FakeEventEmitter();
        $this->stream = new NullStreamStub();
    }

    /** @test */
    public function registersAddingOfStream()
    {
        $this->eventEmitter->attachStream($this->stream, '');

        $this->assertTrue($this->eventEmitter->hasStream($this->stream));
    }

    /** @test */
    public function reportsNotRegisteredStream()
    {
        $this->assertFalse($this->eventEmitter->hasStream($this->stream));
    }

    /** @test */
    public function reportsRegisteredStreamWithResource()
    {
        $this->eventEmitter->attachStream($this->stream, 'Resource #1');

        $this->assertTrue($this->eventEmitter->hasStreamWithResource($this->stream, 'Resource #1'));
    }

    /** @test */
    public function reportsStreamWithResource()
    {
        $this->eventEmitter->attachStream($this->stream, 'Resource #1');

        $this->assertTrue($this->eventEmitter->hasStreamWithResource($this->stream, 'Resource #1'));
    }

    /** @test */
    public function reportsNotRegisteredResourceWithTheStream()
    {
        $this->eventEmitter->attachStream($this->stream, 'Resource #1');

        $this->assertFalse($this->eventEmitter->hasStreamWithResource($this->stream, 'Resource #2'));
    }

    /** @test */
    public function reportsNonEmptyStream()
    {
        $this->eventEmitter->attachStream($this->stream, 'Resource #1');

        $this->assertFalse($this->eventEmitter->isEmpty());
    }

    /** @test */
    public function reportsEmptyStream()
    {
        $this->assertTrue($this->eventEmitter->isEmpty());
    }

    /** @test */
    public function detachesAddedStream()
    {
        $this->eventEmitter->attachStream($this->stream, 'Resource #1');

        $this->eventEmitter->detachStream($this->stream, 'Resource #1');

        $this->assertFalse($this->eventEmitter->hasStream($this->stream));
    }

    /** @test */
    public function ignoresStreamForDetachWhenResourceDoesNotMatch()
    {
        $this->eventEmitter->attachStream($this->stream, 'Resource #1');

        $this->eventEmitter->detachStream($this->stream, 'Resource #2');

        $this->assertTrue($this->eventEmitter->hasStream($this->stream));
    }

    /** @test */
    public function afterDetachEventEmitterIsEmpty()
    {
        $this->eventEmitter->attachStream($this->stream, 'Resource #2');
        $this->eventEmitter->detachStream($this->stream, 'Resource #2');

        $this->assertTrue($this->eventEmitter->isEmpty());
    }
}
