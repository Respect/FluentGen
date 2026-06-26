<?php

/*
 * SPDX-License-Identifier: ISC
 * SPDX-FileCopyrightText: (c) Respect Project Contributors
 * SPDX-FileContributor: Henrique Moody <henriquemoody@gmail.com>
 */

declare(strict_types=1);

namespace Respect\FluentGen\Fluent;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;
use ReflectionParameter;
use Respect\Fluent\Attributes\Composable;
use Respect\Fluent\Attributes\ComposableParameter;
use Respect\FluentGen\CodeGenerator;
use Respect\FluentGen\Config;
use Respect\FluentGen\FileRenderer;
use Respect\FluentGen\NamespaceScanner;

use function in_array;
use function ksort;

/**
 * @phpstan-type PrefixInfo array{
 *     name: string,
 *     prefix: string,
 *     optIn: bool,
 *     fqcn: class-string,
 *     prefixParameter: ReflectionParameter|null,
 *     reflection: ReflectionClass<object>,
 * }
 */
final readonly class MixinGenerator implements CodeGenerator
{
    /** @param array<InterfaceConfig> $interfaces */
    public function __construct(
        private Config $config,
        private NamespaceScanner $scanner,
        private MethodBuilder $methodBuilder = new MethodBuilder(),
        private array $interfaces = [],
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
        [$prefixes, $filters] = $this->discoverPrefixesAndFilters($nodes);

        $files = [];

        foreach ($this->interfaces as $interfaceConfig) {
            $prefixInterfaceNames = [];

            foreach ($prefixes as $prefix) {
                $interfaceName = $prefix['name'] . $interfaceConfig->suffix;
                $prefixInterfaceNames[] = $this->config->outputNamespace . '\\' . $interfaceName;

                $this->generateInterface(
                    $interfaceName,
                    $interfaceConfig,
                    $nodes,
                    $filters,
                    $prefix,
                    $files,
                );
            }

            $this->generateRootInterface(
                $interfaceConfig,
                $prefixInterfaceNames,
                $nodes,
                $filters,
                $files,
            );
        }

        return $files;
    }

    /**
     * @param array<string, ReflectionClass<object>> $nodes
     *
     * @return array{array<string, PrefixInfo>, array<string, Composable>}
     */
    private function discoverPrefixesAndFilters(array $nodes): array
    {
        $prefixes = [];
        $filters = [];

        foreach ($nodes as $name => $reflection) {
            $attributes = $reflection->getAttributes(Composable::class);
            if ($attributes === []) {
                continue;
            }

            $attr = $attributes[0]->newInstance();
            $filters[$name] = $attr;

            if ($attr->prefix === null) {
                continue;
            }

            $constructor = $reflection->getConstructor();
            $prefixParameter = null;

            if ($constructor !== null) {
                foreach ($constructor->getParameters() as $param) {
                    if ($param->getAttributes(ComposableParameter::class) !== []) {
                        $prefixParameter = $param;
                        break;
                    }
                }
            }

            $prefix = $this->methodBuilder->classToPrefix($reflection->getShortName());
            $prefixes[$prefix] = [
                'name' => $reflection->getShortName(),
                'prefix' => $prefix,
                'optIn' => $attr->optIn,
                'fqcn' => $reflection->getName(),
                'prefixParameter' => $prefixParameter,
                'reflection' => $reflection,
            ];
        }

        ksort($prefixes);

        return [$prefixes, $filters];
    }

    /**
     * @param array<string, ReflectionClass<object>> $nodes
     * @param array<string, Composable> $filters
     * @param PrefixInfo $prefix
     * @param array<string, string> $files
     */
    private function generateInterface(
        string $interfaceName,
        InterfaceConfig $config,
        array $nodes,
        array $filters,
        array $prefix,
        array &$files,
    ): void {
        $namespace = new PhpNamespace($this->config->outputNamespace);
        $interface = $namespace->addInterface($interfaceName);

        foreach ($nodes as $name => $reflection) {
            $filter = $filters[$name] ?? null;

            if ($prefix['optIn']) {
                if ($filter === null || !in_array($prefix['fqcn'], $filter->with, true)) {
                    continue;
                }
            } elseif ($filter !== null && in_array($prefix['fqcn'], $filter->without, true)) {
                continue;
            }

            $method = $this->methodBuilder->build(
                $namespace,
                $reflection,
                $config->returnType,
                $prefix['prefix'],
                $config->static,
                $prefix['prefixParameter'],
                $this->mapperFor($config),
                $prefix['reflection'],
            );

            $interface->addMember($method);
        }

        $this->addFile($interfaceName, $namespace, $files);
    }

    private function mapperFor(InterfaceConfig $config): AssuranceTypeMapper|null
    {
        if (!$config->emitNarrowing) {
            return null;
        }

        return new AssuranceTypeMapper($config->chainType, $config->templateParam ?? 'TSure');
    }

    /**
     * @param array<string> $prefixInterfaceNames
     * @param array<string, ReflectionClass<object>> $nodes
     * @param array<string, Composable> $filters
     * @param array<string, string> $files
     */
    private function generateRootInterface(
        InterfaceConfig $config,
        array $prefixInterfaceNames,
        array $nodes,
        array $filters,
        array &$files,
    ): void {
        $interfaceName = $config->suffix;
        $namespace = new PhpNamespace($this->config->outputNamespace);
        $interface = $namespace->addInterface($interfaceName);

        foreach ($config->rootExtends as $extend) {
            $namespace->addUse($extend);
            $interface->addExtend($extend);
        }

        foreach ($prefixInterfaceNames as $prefixInterfaceName) {
            $namespace->addUse($prefixInterfaceName);
            $interface->addExtend($prefixInterfaceName);
        }

        if ($config->templateParam !== null) {
            $interface->addComment('@template-covariant ' . $config->templateParam);
        }

        if ($config->rootComment !== null) {
            $interface->addComment($config->rootComment);
        }

        foreach ($config->rootUses as $use) {
            $namespace->addUse($use);
        }

        foreach ($nodes as $reflection) {
            $method = $this->methodBuilder->build(
                $namespace,
                $reflection,
                $config->returnType,
                null,
                $config->static,
                null,
                $this->mapperFor($config),
            );

            $interface->addMember($method);
        }

        foreach ($config->terminalMethods as $terminal) {
            $interface->addMember($this->buildTerminalMethod($terminal));
        }

        $this->addFile($interfaceName, $namespace, $files);
    }

    private function buildTerminalMethod(TerminalMethod $terminal): Method
    {
        $method = new Method($terminal->name);
        $method->setPublic()->setReturnType($terminal->returnType);

        foreach ($terminal->parameters as $parameterName => $parameterType) {
            $method->addParameter($parameterName)->setType($parameterType);
        }

        foreach ($terminal->optionalParameters as $parameterName => $parameterType) {
            $method->addParameter($parameterName, null)->setType($parameterType);
        }

        foreach ($terminal->comments as $line) {
            $method->addComment($line);
        }

        return $method;
    }

    /** @param array<string, string> $files */
    private function addFile(string $interfaceName, PhpNamespace $namespace, array &$files): void
    {
        $filename = $this->config->outputDir . '/' . $interfaceName . '.php';
        $files[$filename] = $this->renderer->render($namespace, $filename);
    }
}
