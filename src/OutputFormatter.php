<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 */

declare(strict_types=1);

namespace Respect\FluentGen;

use function array_keys;
use function array_values;
use function implode;
use function preg_match;
use function preg_replace;
use function trim;

use const PHP_EOL;

final readonly class OutputFormatter
{
    public function format(string $content, string $existingContent): string
    {
        preg_match('/^<\?php\s*\/\*[\s\S]*?\*\//', $existingContent, $matches);
        $existingHeader = $matches[0] ?? '<?php';

        $replacements = [
            '/\n\n\t(public|private|\/\*\*)/m' => PHP_EOL . '    $1',
            '/\t/m' => '    ',
            '/\?([a-zA-Z]+) \$/' => '$1|null $',
            '/\/\*\*\n +\* (.+)\n +\*\//m' => '/** $1 */',
        ];

        return implode(PHP_EOL, [
            trim($existingHeader) . PHP_EOL,
            'declare(strict_types=1);',
            '',
            preg_replace(
                array_keys($replacements),
                array_values($replacements),
                $content,
            ) ?? $content,
        ]);
    }
}
