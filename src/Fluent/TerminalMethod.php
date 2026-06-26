<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\FluentGen\Fluent;

/**
 * A method injected verbatim into a generated root interface (carrying
 * the @phpstan-assert narrowing PHPDoc).
 * These are not derived from scanned node classes.
 */
final readonly class TerminalMethod
{
    /**
     * @param array<string, string> $parameters         name => PHP type
     * @param list<string> $comments           PHPDoc lines, e.g. '@phpstan-assert TSure $input'
     * @param array<string, string> $optionalParameters name => PHP type, each defaulting to null
     */
    public function __construct(
        public string $name,
        public string $returnType,
        public array $parameters = [],
        public array $comments = [],
        public array $optionalParameters = [],
    ) {
    }
}
