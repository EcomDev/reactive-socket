<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/** Stream select based event emitter */
class StreamSelectEventEmitterBuilder implements EventEmitterBuilder
{
    /**
     * @var LoopRunner
     */
    private $loopRunner;

    /**
     * @var callable[]
     */
    private $workers = [];

    /**
     * @var callable[]
     */
    private $idleWorkers = [];

    /** @var StreamObserver[] */
    private $streamObservers = [];

    public function __construct(LoopRunner $loopRunner)
    {
        $this->loopRunner = $loopRunner;
    }

    /**
     * Creates builder with loop runner
     *
     * Stream select event emitter requires loop runner to work,
     * As it has to register its emitters for being executed on remote ticks
     */
    public static function createWithLoopRunner(LoopRunner $runner)
    {
        return new self($runner);
    }

    /** {@inheritdoc} */
    public function anEmitter(): EventEmitterBuilder
    {
        return new self($this->loopRunner);
    }

    /** {@inheritdoc} */
    public function addStreamObserver(StreamObserver $streamObserver): EventEmitterBuilder
    {
        $this->streamObservers[] = $streamObserver;
        return $this;
    }

    /** {@inheritdoc} */
    public function addWorker(callable $worker): EventEmitterBuilder
    {
        $this->workers[] = $worker;
        return $this;
    }

    /** {@inheritdoc} */
    public function addIdleWorker(callable $worker): EventEmitterBuilder
    {
        $this->idleWorkers[] = $worker;
        return $this;
    }

    /** {@inheritdoc} */
    public function build(): EventEmitter
    {
        $eventEmitter = new StreamSelectEventEmitter(
            $this->createCompositeWorker($this->workers),
            $this->createCompositeWorker($this->idleWorkers),
            CompositeStreamObserver::createFromObservers(...$this->streamObservers)
        );
        $this->loopRunner->attachTickHandler($eventEmitter);
        return $eventEmitter;
    }

    private function createCompositeWorker(array $workers): callable
    {
        return function () use ($workers) {
            array_walk($workers, 'call_user_func');
        };
    }
}
