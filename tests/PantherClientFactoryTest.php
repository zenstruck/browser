<?php

/*
 * This file is part of the zenstruck/browser package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Browser\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Zenstruck\Browser\Bridge\Behat\Kernel\PantherClientFactory;

/**
 * @author Hugo Hamon <hello@kodero.fr>
 */
final class PantherClientFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function is_final_class(): void
    {
        $reflection = new \ReflectionClass(PantherClientFactory::class);

        $this->assertTrue($reflection->isFinal());
    }

    /**
     * @test
     */
    public function extends_panther_test_case(): void
    {
        $this->assertTrue(
            is_subclass_of(PantherClientFactory::class, PantherTestCase::class)
        );
    }

    /**
     * @test
     */
    public function create_primary_is_public_static_method_with_client_return_type(): void
    {
        $method = new \ReflectionMethod(PantherClientFactory::class, 'createPrimary');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(Client::class, $method->getReturnType()->getName());
    }

    /**
     * @test
     */
    public function create_primary_is_declared_in_factory_not_inherited(): void
    {
        $method = new \ReflectionMethod(PantherClientFactory::class, 'createPrimary');

        $this->assertSame(PantherClientFactory::class, $method->getDeclaringClass()->getName());
    }

    /**
     * @test
     */
    public function create_primary_has_correct_parameters(): void
    {
        $method = new \ReflectionMethod(PantherClientFactory::class, 'createPrimary');
        $params = $method->getParameters();

        $this->assertCount(3, $params);

        $this->assertSame('options', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
        $this->assertTrue($params[0]->isOptional());
        $this->assertSame([], $params[0]->getDefaultValue());

        $this->assertSame('kernelOptions', $params[1]->getName());
        $this->assertSame('array', $params[1]->getType()->getName());
        $this->assertTrue($params[1]->isOptional());
        $this->assertSame([], $params[1]->getDefaultValue());

        $this->assertSame('managerOptions', $params[2]->getName());
        $this->assertSame('array', $params[2]->getType()->getName());
        $this->assertTrue($params[2]->isOptional());
        $this->assertSame([], $params[2]->getDefaultValue());
    }

    /**
     * @test
     */
    public function create_primary_body_delegates_to_self_create_panther_client(): void
    {
        $body = $this->methodBody('createPrimary');

        $this->assertStringContainsString('self::createPantherClient($options, $kernelOptions, $managerOptions)', $body);
    }

    /**
     * @test
     */
    public function create_additional_is_public_static_method_with_client_return_type(): void
    {
        $method = new \ReflectionMethod(PantherClientFactory::class, 'createAdditional');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(Client::class, $method->getReturnType()->getName());
    }

    /**
     * @test
     */
    public function create_additional_is_declared_in_factory_not_inherited(): void
    {
        $method = new \ReflectionMethod(PantherClientFactory::class, 'createAdditional');

        $this->assertSame(PantherClientFactory::class, $method->getDeclaringClass()->getName());
    }

    /**
     * @test
     */
    public function create_additional_has_no_parameters(): void
    {
        $method = new \ReflectionMethod(PantherClientFactory::class, 'createAdditional');

        $this->assertCount(0, $method->getParameters());
    }

    /**
     * @test
     */
    public function create_additional_body_delegates_to_self_create_additional_panther_client(): void
    {
        $body = $this->methodBody('createAdditional');

        $this->assertStringContainsString('self::createAdditionalPantherClient()', $body);
    }

    /**
     * @test
     */
    public function inherits_create_panther_client_as_protected_static_method(): void
    {
        $this->assertTrue(
            method_exists(PantherClientFactory::class, 'createPantherClient')
        );

        $method = new \ReflectionMethod(PantherClientFactory::class, 'createPantherClient');

        $this->assertTrue($method->isProtected());
        $this->assertTrue($method->isStatic());
        $this->assertSame(Client::class, $method->getReturnType()->getName());
    }

    /**
     * @test
     */
    public function inherits_create_additional_panther_client_as_protected_static_method(): void
    {
        $this->assertTrue(
            method_exists(PantherClientFactory::class, 'createAdditionalPantherClient')
        );

        $method = new \ReflectionMethod(PantherClientFactory::class, 'createAdditionalPantherClient');

        $this->assertTrue($method->isProtected());
        $this->assertTrue($method->isStatic());
        $this->assertSame(Client::class, $method->getReturnType()->getName());
    }

    /**
     * @test
     */
    public function create_primary_parameters_match_parent_signature(): void
    {
        $primaryParams = (new \ReflectionMethod(PantherClientFactory::class, 'createPrimary'))->getParameters();
        $parentParams = (new \ReflectionMethod(PantherTestCase::class, 'createPantherClient'))->getParameters();

        $this->assertCount(\count($parentParams), $primaryParams);

        foreach ($primaryParams as $i => $param) {
            $this->assertSame($parentParams[$i]->getName(), $param->getName(), \sprintf('Parameter %d name mismatch.', $i));
            $this->assertSame($parentParams[$i]->getType()->getName(), $param->getType()->getName(), \sprintf('Parameter %d type mismatch.', $i));
            $this->assertSame($parentParams[$i]->isOptional(), $param->isOptional(), \sprintf('Parameter %d optional mismatch.', $i));
            $this->assertSame($parentParams[$i]->getDefaultValue(), $param->getDefaultValue(), \sprintf('Parameter %d default value mismatch.', $i));
        }
    }

    /**
     * @test
     */
    public function create_additional_parameters_match_parent_signature(): void
    {
        $additionalParams = (new \ReflectionMethod(PantherClientFactory::class, 'createAdditional'))->getParameters();
        $parentParams = (new \ReflectionMethod(PantherTestCase::class, 'createAdditionalPantherClient'))->getParameters();

        $this->assertCount(\count($parentParams), $additionalParams);
    }

    /**
     * @test
     */
    public function both_methods_return_same_type_as_parent(): void
    {
        $factoryClientMethod = new \ReflectionMethod(PantherClientFactory::class, 'createPrimary');
        $parentClientMethod = new \ReflectionMethod(PantherTestCase::class, 'createPantherClient');

        $this->assertSame(
            $parentClientMethod->getReturnType()->getName(),
            $factoryClientMethod->getReturnType()->getName()
        );

        $factoryAdditionalMethod = new \ReflectionMethod(PantherClientFactory::class, 'createAdditional');
        $parentAdditionalMethod = new \ReflectionMethod(PantherTestCase::class, 'createAdditionalPantherClient');

        $this->assertSame(
            $parentAdditionalMethod->getReturnType()->getName(),
            $factoryAdditionalMethod->getReturnType()->getName()
        );
    }

    private function methodBody(string $methodName): string
    {
        $method = new \ReflectionMethod(PantherClientFactory::class, $methodName);
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $lines = \file($filename);

        return \implode('', \array_slice($lines, $startLine, $endLine - $startLine - 1));
    }
}
