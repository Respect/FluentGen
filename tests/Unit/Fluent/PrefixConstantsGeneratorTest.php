<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Unit\Fluent;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Respect\FluentGen\Config;
use Respect\FluentGen\Fluent\MethodBuilder;
use Respect\FluentGen\Fluent\PrefixConstantsGenerator;
use Respect\FluentGen\NamespaceScanner;
use Respect\FluentGen\Test\Fixtures\BarHandler;
use Respect\FluentGen\Test\Fixtures\FooHandler;
use Respect\FluentGen\Test\Fixtures\Handler;
use Respect\FluentGen\Test\Fixtures\NotHandler;

#[CoversClass(PrefixConstantsGenerator::class)]
final class PrefixConstantsGeneratorTest extends TestCase
{
    #[Test]
    public function itShouldStripClassSuffixFromPrefix(): void
    {
        $generator = $this->createGenerator(classSuffix: 'Handler');
        $output = $generator->generate();
        $content = current($output);

        self::assertStringContainsString("'not' => true", $content);
        self::assertStringNotContainsString("'notHandler'", $content);
    }

    #[Test]
    public function itShouldBuildForbiddenFromComposableWithout(): void
    {
        $generator = $this->createGenerator(classSuffix: 'Handler');
        $output = $generator->generate();
        $content = current($output);

        self::assertStringContainsString('FORBIDDEN', $content);
    }

    #[Test]
    public function itShouldDetectComposableWithArgument(): void
    {
        $generator = $this->createGenerator(classSuffix: 'Handler');
        $output = $generator->generate();
        $content = current($output);

        self::assertStringContainsString('COMPOSABLE_WITH_ARGUMENT', $content);
    }

    #[Test]
    public function itShouldUseStrippedPrefixInComposableMap(): void
    {
        $generator = $this->createGenerator(classSuffix: 'Handler');
        $output = $generator->generate();
        $content = current($output);

        // With suffix stripping: NotHandler → 'not', not 'notHandler'
        self::assertStringContainsString("'not' => true", $content);
        self::assertStringNotContainsString("'notHandler' => true", $content);
    }

    private function createGenerator(string $classSuffix = ''): PrefixConstantsGenerator
    {
        return new PrefixConstantsGenerator(
            config: new Config(
                sourceDir: __DIR__ . '/../../Fixtures',
                sourceNamespace: 'Respect\\FluentGen\\Test\\Fixtures',
                outputDir: '/tmp/fluentgen-test',
                outputNamespace: 'App\\Mixins',
            ),
            scanner: new NamespaceScanner(nodeType: Handler::class),
            outputClassName: 'PrefixConstants',
            methodBuilder: new MethodBuilder(classSuffix: $classSuffix),
        );
    }
}
