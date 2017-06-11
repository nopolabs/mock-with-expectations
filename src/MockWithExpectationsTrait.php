<?php
namespace Nopolabs\Test;

use Closure;
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
            $matcher = $this->any();
        }

        $params = $expectation['params'] ?? null;
        $result = $expectation['result'] ?? null;
        $throws = $expectation['throws'] ?? null;

        if ($params !== null && !is_array($params)) {
            throw new Exception("expected params to be an array, got '$params'");
        }

        if ($result !== null && $throws !== null) {
            throw new Exception("cannot expect both 'result' and 'throws'");
        }

        $builder = $mock->expects($matcher)->method($method);

        if ($params !== null) {
            call_user_func_array([$builder, 'with'], $params);
        }

        if ($result !== null) {
            if ($result instanceof Closure) {
                $builder->willReturnCallback($result);
            } else {
                $builder->willReturn($result);
            }
        }

        if ($throws !== null) {
            $builder->willThrowException($throws);
        }
    }

    protected function convertToMatcher($invoked): PHPUnit_Framework_MockObject_Matcher_Invocation
    {
        if (is_numeric($invoked)) {
            return $this->convertNumeric($invoked);
        }
        if (preg_match("/(?'method'\w+)(?:\s+(?'count'\d+))?/", $invoked, $matches)) {
            if (isset($matches['count'])) {
                return $this->convertTwoWords($matches['method'], (int)$matches['count']);
            }
            return $this->convertOneWord($matches['method']);
        }
        throw new Exception("convertToMatcher cannot convert '$invoked'");
    }

    protected function convertNumeric(int $times): PHPUnit_Framework_MockObject_Matcher_Invocation
    {
        if ($times === 0) {
            return $this->never();
        }
        if ($times === 1) {
            return $this->once();
        }
        return $this->exactly($times);
    }

    protected function convertOneWord(string $method): PHPUnit_Framework_MockObject_Matcher_Invocation
    {
        if ($method === 'once') {
            return $this->once();
        }
        if ($method === 'any') {
            return $this->any();
        }
        if ($method === 'never') {
            return $this->never();
        }
        if ($method === 'atLeastOnce') {
            return $this->atLeastOnce();
        }
        throw new Exception("convertOneWord cannot convert '$method'");
    }

    protected function convertTwoWords(string $method, int $count): PHPUnit_Framework_MockObject_Matcher_Invocation
    {
        if ($method === 'atLeast') {
            return $this->atLeast($count);
        }
        if ($method === 'exactly') {
            return $this->exactly($count);
        }
        if ($method === 'atMost') {
            return $this->atMost($count);
        }
        throw new Exception("convertTwoWords cannot convert '$method $count'");
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
