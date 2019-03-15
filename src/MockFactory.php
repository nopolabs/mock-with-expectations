<?php
declare(strict_types=1);

namespace Nopolabs\Test;


use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockBuilder;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionClass;
use ReflectionMethod;

class MockFactory
{
    /** @var TestCase */
    private $testCase;

    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    public function newPartialMock(
        string $className,
        array $expectedMethods,
        array $constructorArgs = null): PHPUnit_Framework_MockObject_MockObject
    {
        $methods = $this->getMethodsToMock($className, $expectedMethods);

        $builder = $this->getMockBuilder($className);
        $builder->disableOriginalClone();
        $builder->disableArgumentCloning();
        $builder->disallowMockingUnknownTypes();
        if (!empty($methods)) {
            $builder->setMethods($methods);
        }
        if ($constructorArgs === null) {
            $builder->disableOriginalConstructor();
        } else {
            $builder->setConstructorArgs($constructorArgs);
        }

        return $builder->getMock();
    }

    protected function getMockBuilder(string $className) : PHPUnit_Framework_MockObject_MockBuilder
    {
        return $this->testCase->getMockBuilder($className);
    }

    private function getMethodsToMock(
        string $className,
        array $expectedMethods) : array
    {
        $missingMethods = $this->getMissingMethods($className);

        return array_unique(array_merge($expectedMethods, $missingMethods));
    }

    private function getMissingMethods(string $className) : array
    {
        $reflection = new ReflectionClass($className);

        if ($reflection->isInterface()) {
            return $this->getPublicMethods($reflection);
        }

        if ($reflection->isAbstract()) {
            return $this->getAbstractMethods($reflection);
        }

        return [];
    }

    private function getPublicMethods(ReflectionClass $reflection) : array
    {
        return $this->getReflectedMethods($reflection, ReflectionMethod::IS_PUBLIC);
    }

    private function getAbstractMethods(ReflectionClass $reflection) : array
    {
        return $this->getReflectedMethods($reflection, ReflectionMethod::IS_ABSTRACT);
    }

    private function getReflectedMethods(ReflectionClass $reflection, int $filter) : array
    {
        return array_map(function(ReflectionMethod $method) {
            return $method->name;
        }, $reflection->getMethods($filter));
    }
}