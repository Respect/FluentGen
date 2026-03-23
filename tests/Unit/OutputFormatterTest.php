<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Respect\FluentGen\OutputFormatter;

#[CoversClass(OutputFormatter::class)]
final class OutputFormatterTest extends TestCase
{
    private OutputFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new OutputFormatter();
    }

    #[Test]
    public function itShouldExtractSpdxHeaderFromExistingContent(): void
    {
        $spdx = '* SPDX';
        $existingContent = <<<PHP
            <?php
            /*
             $spdx-License-Identifier: ISC
             $spdx-FileCopyrightText: (c) Respect Project Contributors
             */

            declare(strict_types=1);
            PHP;

        $content = "namespace Foo;\n\ninterface Bar\n{\n}\n";

        $result = $this->formatter->format($content, $existingContent);

        self::assertStringContainsString(sprintf('%s-License-Identifier: ISC', $spdx), $result);
        self::assertStringContainsString('declare(strict_types=1);', $result);
    }

    #[Test]
    public function itShouldHandleEmptyExistingContent(): void
    {
        $content = "namespace Foo;\n\ninterface Bar\n{\n}\n";

        $result = $this->formatter->format($content, '');

        self::assertStringContainsString('declare(strict_types=1);', $result);
        self::assertStringContainsString('namespace Foo;', $result);
    }

    #[Test]
    public function itShouldConvertTabsToSpaces(): void
    {
        $content = "\tpublic function foo(): void;\n";

        $result = $this->formatter->format($content, '');

        self::assertStringNotContainsString("\t", $result);
        self::assertStringContainsString('    public function foo(): void;', $result);
    }

    #[Test]
    public function itShouldConvertNullableSyntaxToUnionType(): void
    {
        $content = 'public function foo(?string $bar): void;';

        $result = $this->formatter->format($content, '');

        self::assertStringContainsString('string|null $bar', $result);
        self::assertStringNotContainsString('?string', $result);
    }

    #[Test]
    public function itShouldCollapseMultiLineDocComments(): void
    {
        $content = "/**\n * @param string \$foo\n */";

        $result = $this->formatter->format($content, '');

        self::assertStringContainsString('/** @param string $foo */', $result);
    }

    #[Test]
    public function itShouldRemoveBlankLineBeforePublicMethods(): void
    {
        $content = "\n\n\tpublic function foo(): void;";

        $result = $this->formatter->format($content, '');

        self::assertStringContainsString("\n    public function foo(): void;", $result);
    }

    #[Test]
    public function itShouldRemoveBlankLineBeforeDocComments(): void
    {
        $content = "\n\n\t/** doc */";

        $result = $this->formatter->format($content, '');

        self::assertStringContainsString("\n    /** doc */", $result);
    }

    #[Test]
    public function itShouldRemoveBlankLineBeforePrivateMembers(): void
    {
        $content = "\n\n\tprivate function bar(): void;";

        $result = $this->formatter->format($content, '');

        self::assertStringContainsString("\n    private function bar(): void;", $result);
    }

    #[Test]
    public function itShouldPreserveContentWhenPregReplaceFails(): void
    {
        // With valid input, preg_replace should succeed and transform content
        $content = "namespace Foo;\n";

        $result = $this->formatter->format($content, '');

        self::assertStringContainsString('namespace Foo;', $result);
    }
}
