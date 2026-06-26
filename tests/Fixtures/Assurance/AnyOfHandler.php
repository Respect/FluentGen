<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceCompose;

#[Assurance(compose: AssuranceCompose::Union, exact: true)]
final class AnyOfHandler
{
    public function __construct(
        public readonly Validator $validator1,
        public readonly Validator $validator2,
        Validator ...$validators,
    ) {
    }
}
