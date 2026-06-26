<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

use ArrayAccess;
use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceSubject;
use Respect\Fluent\Attributes\AssuranceSubjectMode;

#[Assurance(type: ['array', ArrayAccess::class])]
#[AssuranceSubject(AssuranceSubjectMode::Container)]
final class KeyHandler
{
    public function __construct(
        public readonly int|string $key,
        public readonly Validator $validator,
    ) {
    }
}
