<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

class SimpleLoopRunner implements LoopRunner
{
    /**
     * Registry of tick handlers
     *
     * @var callable
     */
    private $tickHandlers = [];

    /**
     * Running flag
     *
     * @var bool
     */
    private $running = true;

    /** {@inheritdoc} */
    public function attachTickHandler(callable $handler): void
    {
        $this->tickHandlers[] = $handler;
    }

    /**
     * Executes all tick handlers once
     *
     * Useful for using in tests
     */
    public function runOnce(): void
    {
        foreach ($this->tickHandlers as $tickHandler) {
            $tickHandler();
        }
    }

    /**
     * Executes loop continuously
     *
     * It is highly recommended to set pause for a reasonable value
     * Otherwise CPU usage will get 100% in your application
     */
    public function run(int $pauseInMicroseconds): void
    {
        $this->running = true;

        while ($this->running) {
            $this->runOnce();
            usleep($pauseInMicroseconds);
        }
    }

    /**
     * Stops continuous loop run
     */
    public function stop(): void
    {
        $this->running = false;
    }
}
