<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/** Stream client stub */
class NullStreamClientStub implements StreamClient
{
    /** {@inheritdoc} */
    public function write(string $data): void
    {
    }

    /** {@inheritdoc} */
    public function read(): string
    {
        return '';
    }

    /** {@inheritdoc} */
    public function close(): void
    {
    }
}
