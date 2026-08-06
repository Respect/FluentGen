<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Fixtures\Assurance;

/**
 * Marker standing in for Respect\Validation\Validator so the mapper's Validator-typed
 * parameter detection (str_contains the type with "Validator") fires in FluentGen's tests
 * without depending on the Validation package.
 */
interface Validator
{
}
