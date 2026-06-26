<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceCompose;

#[Assurance(compose: AssuranceCompose::Intersect, composeRange: [1, 1], exact: true)]
final class NamedHandler
{
    public function __construct(
        public readonly string $name,
        public readonly Validator $validator,
    ) {
    }
}
