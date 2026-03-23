<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen;

use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;

use function file_get_contents;
use function is_file;
use function is_readable;

final readonly class FileRenderer
{
    public function __construct(
        private OutputFormatter $formatter = new OutputFormatter(),
    ) {
    }

    public function render(PhpNamespace $namespace, string $outputFile): string
    {
        $printer = new Printer();
        $printer->wrapLength = 300;

        $existingContent = '';
        if (is_file($outputFile) && is_readable($outputFile)) {
            $existingContent = file_get_contents($outputFile) ?: '';
        }

        return $this->formatter->format(
            $printer->printNamespace($namespace),
            $existingContent,
        );
    }
}
