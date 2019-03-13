<?php
declare (strict_types=1);

namespace Nopolabs\Test;

use PHPUnit_Framework_MockObject_MockBuilder;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionClass;
use ReflectionMethod;

/**
 * This trait expects to be used in a sub-class of PHPUnit\Framework\TestCase
 */
trait MockWithExpectations2Trait
{
    // implemented by PHPUnit\Framework\TestCase
    abstract public function getMockBuilder($className);

    protected function mockWithExpectations(
        $className,
        array $expectations = [],
        array $constructorArgs = null): PHPUnit_Framework_MockObject_MockObject
    {
        $expectations = $this->prepareExpectations($expectations);

        return $this->newPartialMockWithExpectations($className, $expectations, $constructorArgs);
    }

    protected function prepareExpectation(array $expects) : Expectation
    {
        list($method, $params, $result, $throws, $invoked) = $this->normalizeExpectations($expects);

        return new Expectation($method, $params, $result, $throws, $invoked);
    }

    protected function prepareExpectations(array $expectations) : array
    {
        if ($this->isAssociative($expectations)) {
            return $this->prepareExpectationsMap($expectations);
        }

        return $this->prepareExpectationsList($expectations);
    }

    protected function prepareExpectationsMap(array $map) : array
    {
        $expectations = [];

        foreach ($map as $method => $expects) {
            if (!\is_array($expects)) {
                $expects = ['invoked' => $expects];
            }
            $expects['method'] = $method;
            $expectations[] = $this->prepareExpectation($expects);
        }

        return $expectations;
    }

    protected function prepareExpectationsList(array $list) : array
    {
        $expectations = [];

        $index = 0;
        foreach ($list as $expects) {
            list($method, $params, $result, $throws, $invoked) = $this->normalizeExpectations($expects);
            $invoked = $invoked ?? self::at($index++);
            $expectations[] = new Expectation($method, $params, $result, $throws, $invoked);
        }

        return $expectations;
    }

    protected function normalizeExpectations(array $expects) : array
    {
        $method = $expects['method'] ?? null;
        $params = $expects['params'] ?? null;
        $result = $expects['result'] ?? null;
        $throws = $expects['throws'] ?? null;
        $invoked = $expects['invoked'] ?? null;

        unset(
            $expects['method'],
            $expects['params'],
            $expects['result'],
            $expects['throws'],
            $expects['invoked']
        );

        $method = (string)($method ?? array_shift($expects));
        if ($invoked === null && $expects === ['never']) {
            $invoked = array_shift($expects);
        }
        $params = (array)($params ?? array_shift($expects));
        $result = $result ?? array_shift($expects);

        if ($result !== null && $throws !== null) {
            throw new TestException("cannot expect both 'result' and 'throws'");
        }

        return [$method, $params, $result, $throws, $invoked];
    }

    protected function newPartialMockWithExpectations(
        $className,
        array $expectations,
        array $constructorArgs = null): PHPUnit_Framework_MockObject_MockObject
    {
        $methods = $this->getMethodsToMock($className, $expectations);
        $mock = $this->newPartialMock($className, $methods, $constructorArgs);
        $this->setExpectations($mock, $expectations);

        return $mock;
    }

    protected function setExpectations(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $expectations)
    {
        foreach ($expectations as $expectation) {
            $this->setExpectation($mock, $expectation);
        }
    }

    protected function setExpectation(
        PHPUnit_Framework_MockObject_MockObject $mock,
        $expectation)
    {
        if (!$expectation instanceof Expectation) {
            $expectation = $this->prepareExpectation((array)$expectation);
        }

        $expectation->build($mock);
    }

    protected function newPartialMock(
        $className,
        array $methods = [],
        array $constructorArgs = null): PHPUnit_Framework_MockObject_MockObject
    {
        /** @var PHPUnit_Framework_MockObject_MockBuilder $builder */
        $builder = $this->getMockBuilder($className);
        $builder->disableOriginalClone();
        $builder->disableArgumentCloning();
        $builder->disallowMockingUnknownTypes();
        $builder->setMethods(empty($methods) ? null : $methods);

        if ($constructorArgs === null) {
            $builder->disableOriginalConstructor();
        } else {
            $builder->setConstructorArgs($constructorArgs);
        }

        return $builder->getMock();
    }

    private function isAssociative(array $array): bool
    {
        return array_keys($array) !== range(0, \count($array) - 1);
    }

    protected function getMethodsToMock($className, array $expectations) : array
    {
        $expectedMethods = $this->getExpectedMethods($expectations);
        $missingMethods = $this->getMissingMethods($className);

        return array_unique(array_merge($expectedMethods, $missingMethods));
    }

    private function getExpectedMethods(array $expectations) : array
    {
        return array_unique(array_map(
            function(Expectation $expectation) {
                return $expectation->getMethod();
            }, $expectations
        ));
    }

    private function getMissingMethods($className) : array
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
        return array_map(function (ReflectionMethod $method) {
            return $method->name;
        }, $reflection->getMethods(ReflectionMethod::IS_PUBLIC));
    }

    private function getAbstractMethods(ReflectionClass $reflection) : array
    {
        return array_map(function (ReflectionMethod $method) {
            return $method->name;
        }, $reflection->getMethods(ReflectionMethod::IS_ABSTRACT));
    }
}
