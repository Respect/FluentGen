<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures;

final class BarHandler implements Handler
{
    /** @param array<string> $tags */
    public function __construct(
        public readonly string $value,
        public readonly bool $strict = true,
        public readonly array $tags = [],
    ) {
    }
}
