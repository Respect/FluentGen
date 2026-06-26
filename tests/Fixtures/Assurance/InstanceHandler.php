<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

use Respect\Fluent\Attributes\Assurance;
use Respect\Fluent\Attributes\AssuranceFrom;
use Respect\Fluent\Attributes\AssuranceParameter;

#[Assurance(from: AssuranceFrom::TypeString, exact: true)]
final class InstanceHandler
{
    /** @param class-string $class */
    public function __construct(
        #[AssuranceParameter]
        public readonly string $class,
    ) {
    }
}
