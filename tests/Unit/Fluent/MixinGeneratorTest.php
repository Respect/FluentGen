<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 */

declare(strict_types=1);

namespace Respect\FluentGen\Test\Unit\Fluent;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Respect\FluentGen\Config;
use Respect\FluentGen\Fluent\InterfaceConfig;
use Respect\FluentGen\Fluent\MethodBuilder;
use Respect\FluentGen\Fluent\MixinGenerator;
use Respect\FluentGen\NamespaceScanner;
use Respect\FluentGen\Test\Fixtures\Handler;

use function array_keys;

#[CoversClass(MixinGenerator::class)]
final class MixinGeneratorTest extends TestCase
{
    private const string FIXTURES_DIR = __DIR__ . '/../../Fixtures';
    private const string FIXTURES_NS = 'Respect\\FluentGen\\Test\\Fixtures';

    #[Test]
    public function itShouldGenerateInterfacesWithoutComposable(): void
    {
        $generator = new MixinGenerator(
            config: $this->config(),
            scanner: new NamespaceScanner(
                nodeType: Handler::class,
                excludedClassNames: ['NotHandler'],
            ),
            methodBuilder: new MethodBuilder(classSuffix: 'Handler'),
            interfaces: [
                new InterfaceConfig(suffix: 'Builder', returnType: 'static', static: true),
                new InterfaceConfig(suffix: 'Chain', returnType: 'static'),
            ],
        );

        $files = $generator->generate();

        self::assertCount(2, $files);
        self::assertArrayHasKey('/tmp/fluentgen-test/Builder.php', $files);
        self::assertArrayHasKey('/tmp/fluentgen-test/Chain.php', $files);
    }

    #[Test]
    public function itShouldNotGeneratePrefixInterfacesWithoutComposable(): void
    {
        $generator = new MixinGenerator(
            config: $this->config(),
            scanner: new NamespaceScanner(
                nodeType: Handler::class,
                excludedClassNames: ['NotHandler'],
            ),
            methodBuilder: new MethodBuilder(classSuffix: 'Handler'),
            interfaces: [
                new InterfaceConfig(suffix: 'Builder', returnType: 'static', static: true),
            ],
        );

        $files = $generator->generate();
        $filenames = array_keys($files);

        self::assertCount(1, $filenames);
        self::assertSame('/tmp/fluentgen-test/Builder.php', $filenames[0]);
    }

    #[Test]
    public function itShouldIncludeMethodsInGeneratedInterface(): void
    {
        $generator = new MixinGenerator(
            config: $this->config(),
            scanner: new NamespaceScanner(
                nodeType: Handler::class,
                excludedClassNames: ['NotHandler'],
            ),
            methodBuilder: new MethodBuilder(classSuffix: 'Handler'),
            interfaces: [
                new InterfaceConfig(suffix: 'Builder', returnType: 'static', static: true),
            ],
        );

        $files = $generator->generate();
        $content = $files['/tmp/fluentgen-test/Builder.php'];

        self::assertStringContainsString('function foo(', $content);
        self::assertStringContainsString('function bar(', $content);
        self::assertStringContainsString('function empty(', $content);
    }

    #[Test]
    public function itShouldGenerateWithComposablePrefixes(): void
    {
        $generator = new MixinGenerator(
            config: $this->config(),
            scanner: new NamespaceScanner(nodeType: Handler::class),
            methodBuilder: new MethodBuilder(classSuffix: 'Handler'),
            interfaces: [
                new InterfaceConfig(suffix: 'Builder', returnType: 'static', static: true),
            ],
        );

        $files = $generator->generate();

        // NotHandler has #[Composable('not')], so a NotHandlerBuilder prefix interface is generated
        self::assertArrayHasKey('/tmp/fluentgen-test/NotHandlerBuilder.php', $files);
        self::assertCount(2, $files);

        $prefixContent = $files['/tmp/fluentgen-test/NotHandlerBuilder.php'];
        self::assertStringContainsString('function notFoo(', $prefixContent);
        self::assertStringContainsString('function notBar(', $prefixContent);
        self::assertStringContainsString('function notEmpty(', $prefixContent);
    }

    #[Test]
    public function itShouldProduceEmptyOutputWithNoInterfaces(): void
    {
        $generator = new MixinGenerator(
            config: $this->config(),
            scanner: new NamespaceScanner(nodeType: Handler::class),
        );

        $files = $generator->generate();

        self::assertCount(0, $files);
    }

    #[Test]
    public function itShouldExcludeScannerFilteredClasses(): void
    {
        $generator = new MixinGenerator(
            config: $this->config(),
            scanner: new NamespaceScanner(
                nodeType: Handler::class,
                excludedClassNames: ['FooHandler', 'BarHandler', 'NotHandler'],
            ),
            methodBuilder: new MethodBuilder(classSuffix: 'Handler'),
            interfaces: [
                new InterfaceConfig(suffix: 'Chain', returnType: 'static'),
            ],
        );

        $files = $generator->generate();
        $content = $files['/tmp/fluentgen-test/Chain.php'];

        self::assertStringNotContainsString('function foo(', $content);
        self::assertStringNotContainsString('function bar(', $content);
        self::assertStringContainsString('function empty(', $content);
    }

    private function config(): Config
    {
        return new Config(
            sourceDir: self::FIXTURES_DIR,
            sourceNamespace: self::FIXTURES_NS,
            outputDir: '/tmp/fluentgen-test',
            outputNamespace: 'App\\Mixins',
        );
    }
}
