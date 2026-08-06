<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
 */

declare(strict_types=1);

namespace Respect\FluentGen\Fluent;

/**
 * PHPDoc lines describing how a generated method narrows its subject, plus whether
 * those lines replace the constructor-derived doc comment (needed when the narrowing
 * introduces its own @param override, e.g. argument- or element-derived rules).
 */
final readonly class NarrowingDoc
{
    /** @param list<string> $comments */
    public function __construct(
        public array $comments,
        public bool $suppressConstructorDoc = false,
    ) {
    }
}
