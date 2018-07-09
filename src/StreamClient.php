<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\ReactiveSocket;

/**
 * Stream client
 *
 * This abstraction allows you to manipulate connected stream
 *
 * Implementation MUST stick to the described behaviour in method description
 */
interface StreamClient
{
    /**
     * Writes data to stream
     *
     * Any call to write that exceeds the write limit MUST throw WriteLimitReachedException.
     * Any client who calls write MUST schedule write till the next stream writable notification.
     * Apart from throwing exception implementation MAY pause stream notifications
     * by observer till all queued writes are complete.
     *
     * @throws WriteLimitReachedException
     */
    public function write(string $data): void;

    /**
     * Returns currently available data in stream
     *
     * This method MUST never fail. If connection is dropped it should always return empty string.
     *
     * @return string
     */
    public function read(): string;

    /**
     * Gracefully closes stream
     *
     * Any data write in progress MAY be completed
     * Any write operation that are not yet started MUST be returned back to observer
     * Any unread data MUST NOT emmit notifications to the remote if any out-of-bounds confirmation is used
     */
    public function close(): void;
}
