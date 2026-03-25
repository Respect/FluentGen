<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 */

declare(strict_types=1);

namespace Respect\FluentGen\Fluent;

use InvalidArgumentException;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;
use Respect\Fluent\Attributes\Composable;
use Respect\Fluent\Attributes\ComposableParameter;
use Respect\FluentGen\CodeGenerator;
use Respect\FluentGen\Config;
use Respect\FluentGen\FileRenderer;
use Respect\FluentGen\NamespaceScanner;

use function array_diff;
use function array_keys;
use function ctype_upper;
use function ksort;
use function sprintf;
use function str_starts_with;
use function strlen;
use function uksort;

final readonly class PrefixConstantsGenerator implements CodeGenerator
{
    public function __construct(
        private Config $config,
        private NamespaceScanner $scanner,
        private string $outputClassName,
        private MethodBuilder $methodBuilder = new MethodBuilder(),
        private FileRenderer $renderer = new FileRenderer(),
    ) {
    }

    /** @return array<string, string> filename => content */
    public function generate(): array
    {
        $nodes = $this->scanner->scan(
            $this->config->sourceDir,
            $this->config->sourceNamespace,
        );
        $prefixes = $this->discoverPrefixes($nodes);
        $fqcnToPrefixMap = $this->buildFqcnMap($nodes);
        $composable = $this->buildComposable($nodes, $prefixes);
        $composableWithArgument = $this->buildComposableWithArgument($prefixes);
        $forbidden = $this->buildForbidden($nodes, $prefixes, $fqcnToPrefixMap);

        $namespace = new PhpNamespace($this->config->outputNamespace);
        $class = $namespace->addClass($this->outputClassName);
        $class->setFinal();

        $class->addConstant('COMPOSABLE', $composable)->setPublic()->setType('array');
        $class->addConstant('COMPOSABLE_WITH_ARGUMENT', $composableWithArgument)->setPublic()->setType('array');
        $class->addConstant('FORBIDDEN', $forbidden)->setPublic()->setType('array');

        $outputFile = $this->config->outputDir . '/' . $this->outputClassName . '.php';

        return [$outputFile => $this->renderer->render($namespace, $outputFile)];
    }

    /**
     * @param array<string, ReflectionClass<object>> $nodes
     *
     * @return array<string, array{prefix: string, prefixParameter: bool}>
     */
    private function discoverPrefixes(array $nodes): array
    {
        $prefixes = [];

        foreach ($nodes as $reflection) {
            $attributes = $reflection->getAttributes(Composable::class);
            if ($attributes === []) {
                continue;
            }

            $attr = $attributes[0]->newInstance();
            if ($attr->prefix === null) {
                continue;
            }

            $hasPrefixParameter = false;
            $constructor = $reflection->getConstructor();
            if ($constructor !== null) {
                foreach ($constructor->getParameters() as $param) {
                    if ($param->getAttributes(ComposableParameter::class) !== []) {
                        $hasPrefixParameter = true;
                        break;
                    }
                }
            }

            $prefix = $this->methodBuilder->classToPrefix($reflection->getShortName());
            $prefixes[$prefix] = [
                'prefix' => $prefix,
                'prefixParameter' => $hasPrefixParameter,
            ];
        }

        ksort($prefixes);

        return $prefixes;
    }

    /**
     * @param array<string, ReflectionClass<object>> $nodes
     * @param array<string, array{prefix: string, prefixParameter: bool}> $prefixes
     *
     * @return array<string, true>
     */
    private function buildComposable(array $nodes, array $prefixes): array
    {
        $composable = [];

        foreach (array_keys($prefixes) as $prefix) {
            $composable[$prefix] = true;

            foreach (array_keys($nodes) as $name) {
                $lcName = $this->methodBuilder->classToPrefix($name);
                if ($lcName === $prefix) {
                    continue;
                }

                if (!str_starts_with($lcName, $prefix)) {
                    continue;
                }

                if (!ctype_upper($lcName[strlen($prefix)])) {
                    continue;
                }

                $composable[$lcName] = true;
            }
        }

        uksort($composable, static fn(string $a, string $b): int => strlen($b) <=> strlen($a) ?: $a <=> $b);

        return $composable;
    }

    /**
     * @param array<string, ReflectionClass<object>> $nodes
     *
     * @return array<class-string, string>
     */
    private function buildFqcnMap(array $nodes): array
    {
        $map = [];

        foreach ($nodes as $reflection) {
            $map[$reflection->getName()] = $this->methodBuilder->classToPrefix($reflection->getShortName());
        }

        return $map;
    }

    /**
     * @param array<string, ReflectionClass<object>> $nodes
     * @param array<string, array{prefix: string, prefixParameter: bool}> $prefixes
     * @param array<class-string, string> $fqcnToPrefixMap
     *
     * @return array<string, array<string, true>>
     */
    private function buildForbidden(array $nodes, array $prefixes, array $fqcnToPrefixMap): array
    {
        $forbidden = [];
        $prefixNames = array_keys($prefixes);

        foreach ($nodes as $name => $reflection) {
            $attributes = $reflection->getAttributes(Composable::class);
            if ($attributes === []) {
                continue;
            }

            $attr = $attributes[0]->newInstance();

            $resolvedWith = $this->resolveClassStrings($attr->with, $fqcnToPrefixMap, $name);
            $resolvedWithout = $this->resolveClassStrings($attr->without, $fqcnToPrefixMap, $name);
            $blockedPrefixes = $attr->optIn ? array_diff($prefixNames, $resolvedWith) : $resolvedWithout;

            if ($blockedPrefixes === []) {
                continue;
            }

            $entry = [];
            foreach ($blockedPrefixes as $prefix) {
                $entry[$prefix] = true;
            }

            ksort($entry);
            $forbidden[$name] = $entry;
        }

        ksort($forbidden);

        return $forbidden;
    }

    /**
     * @param array<string, array{prefix: string, prefixParameter: bool}> $prefixes
     *
     * @return array<string, true>
     */
    private function buildComposableWithArgument(array $prefixes): array
    {
        $composableWithArgument = [];

        foreach ($prefixes as $prefix => $info) {
            if (!$info['prefixParameter']) {
                continue;
            }

            $composableWithArgument[$prefix] = true;
        }

        ksort($composableWithArgument);

        return $composableWithArgument;
    }

    /**
     * @param list<class-string> $classStrings
     * @param array<class-string, string> $fqcnToPrefixMap
     *
     * @return list<string>
     */
    private function resolveClassStrings(array $classStrings, array $fqcnToPrefixMap, string $context): array
    {
        $resolved = [];

        foreach ($classStrings as $fqcn) {
            if (!isset($fqcnToPrefixMap[$fqcn])) {
                throw new InvalidArgumentException(
                    sprintf('Composable on %s references unknown class %s', $context, $fqcn),
                );
            }

            $resolved[] = $fqcnToPrefixMap[$fqcn];
        }

        return $resolved;
    }
}
