<?php
namespace Nopolabs\Test;

use PHPUnit\Framework\Exception;
use PHPUnit_Framework_MockObject_Matcher_Invocation;
use PHPUnit_Framework_MockObject_MockBuilder;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionClass;
use ReflectionMethod;

/**
 * This trait expects to be used in a sub-class of PHPUnit\Framework\TestCase
 */
trait MockWithExpectationsTrait
{
    protected function newPartialMockWithExpectations(
        $className,
        array $expectations = [],
        array $constructorArgs = null): PHPUnit_Framework_MockObject_MockObject
    {
        if ($this->isAssociative($expectations)) {
            return $this->newPartialMockWithExpectationsMap($className, $expectations, $constructorArgs);
        }

        return $this->newPartialMockWithExpectationsList($className, $expectations, $constructorArgs);
    }

    protected function newPartialMockWithExpectationsMap(
        $className,
        array $expectations,
        array $constructorArgs = null): PHPUnit_Framework_MockObject_MockObject
    {
        $methods = array_unique(array_keys($expectations));
        $missingMethods = $this->getMissingMethods($className, $methods);
        foreach ($missingMethods as $method) {
            $expectations[$method] = 'never';
            $methods[] = $method;
        }
        $mock = $this->newPartialMock($className, $methods, $constructorArgs);
        $this->setExpectations($mock, $expectations);

        return $mock;
    }

    protected function newPartialMockWithExpectationsList(
        $className,
        array $expectations,
        array $constructorArgs = null): PHPUnit_Framework_MockObject_MockObject
    {
        $methods = array_unique(array_column($expectations, 0));
        $missingMethods = $this->getMissingMethods($className, $methods);
        foreach ($missingMethods as $method) {
            $expectations[] = [$method, 'never'];
            $methods[] = $method;
        }
        $mock = $this->newPartialMock($className, $methods, $constructorArgs);
        $this->setAtExpectations($mock, $expectations);

        return $mock;
    }

    protected function newPartialMock(
        $className,
        array $methods = [],
        array $constructorArgs = null): PHPUnit_Framework_MockObject_MockObject
    {
        /** @var PHPUnit_Framework_MockObject_MockBuilder $builder */
        $builder = $this->getMockBuilder($className)
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(empty($methods) ? null : $methods);

        if ($constructorArgs === null) {
            $builder->disableOriginalConstructor();
        } else {
            $builder->setConstructorArgs($constructorArgs);
        }

        return $builder->getMock();
    }

    protected function setExpectations(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $expectations)
    {
        foreach ($expectations as $method => $expectation) {
            if (!is_array($expectation)) {
                $expectation = ['invoked' => $expectation];
            }
            $this->setExpectation($mock, $method, $expectation);
        }
    }

    protected function setAtExpectations(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $atExpectations)
    {
        $index = 0;
        foreach ($atExpectations as $atExpectation) {
            array_push($atExpectation, []);
            list($method, $expectation) = $atExpectation;
            if ($expectation === 'never') {
                $mock->expects($this->never())->method($method);
            } elseif (is_array($expectation)) {
                $expectation['invoked'] = $this->at($index++);
                $this->setExpectation($mock, $method, $expectation);
            } else {
                throw new Exception("setAtExpectations cannot understand expectation '$expectation'");
            }
        }
    }

    protected function setExpectation(
        PHPUnit_Framework_MockObject_MockObject $mock,
        $method,
        array $expectation)
    {
        if (isset($expectation['invoked'])) {
            if ($expectation['invoked'] instanceof PHPUnit_Framework_MockObject_Matcher_Invocation) {
                $matcher = $expectation['invoked'];
            } else {
                $matcher = $this->convertToMatcher($expectation['invoked']);
            }
        } else {
            $matcher = $this->once();
        }

        $params = $expectation['params'] ?? null;
        $result = $expectation['result'] ?? null;
        $throws = $expectation['throws'] ?? null;

        if ($params && !is_array($params)) {
            throw new Exception("expected params to be an array, got '$params'");
        }

        if ($result && $throws) {
            throw new Exception("cannot expect both 'result' and 'throws'");
        }

        $result = $expectation['result'] ?? null;

        $throws = $expectation['throws'] ?? null;

        $builder = $mock->expects($matcher)->method($method);

        if ($params) {
            call_user_func_array([$builder, 'with'], $params);
        }

        if ($result) {
            $builder->willReturn($result);
        }

        if ($throws) {
            $builder->willThrowException($throws);
        }
    }

    protected function convertToMatcher($invoked): PHPUnit_Framework_MockObject_Matcher_Invocation
    {
        if (is_numeric($invoked)) {
            $times = (int) $invoked;
            if ($times === 0) {
                return $this->never();
            }
            if ($times === 1) {
                return $this->once();
            }
            return $this->exactly($times);
        }
        if ($invoked === 'once') {
            return $this->once();
        }
        if ($invoked === 'any') {
            return $this->any();
        }
        if ($invoked === 'never') {
            return $this->never();
        }
        if ($invoked === 'atLeastOnce') {
            return $this->atLeastOnce();
        }
        if (preg_match('/(\w+)\s+(\d+)/', $invoked, $matches)) {
            $method = $matches[1];
            $count = (int) $matches[2];
            if ($method === 'atLeast') {
                return $this->atLeast($count);
            }
            if ($method === 'exactly') {
                return $this->exactly($count);
            }
            if ($method === 'atMost') {
                return $this->atMost($count);
            }
        }
        throw new Exception("convertToMatcher cannot convert '$invoked'");
    }

    private function isAssociative(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function getMissingMethods($className, array $methods) : array
    {
        $reflection = new ReflectionClass($className);

        if ($reflection->isInterface()) {
            $publicMethods = array_map(function (ReflectionMethod $method) {
                return $method->name;
            }, $reflection->getMethods(ReflectionMethod::IS_PUBLIC));

            return array_diff($publicMethods, $methods);
        }

        if ($reflection->isAbstract()) {
            $abstractMethods = array_map(function(ReflectionMethod $method) {
                return $method->name;
            }, $reflection->getMethods(ReflectionMethod::IS_ABSTRACT));

            return array_diff($abstractMethods, $methods);
        }

        return [];
    }
}
