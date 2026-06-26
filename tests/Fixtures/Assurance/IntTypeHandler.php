<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

use Respect\Fluent\Attributes\Assurance;

#[Assurance(type: 'int', exact: true)]
final class IntTypeHandler
{
}
