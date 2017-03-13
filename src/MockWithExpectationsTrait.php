<?php
namespace Nopolabs\Test;

use PHPUnit\Framework\Exception;
use PHPUnit_Framework_MockObject_Matcher_Invocation;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * This trait expects to be used in a sub-class of PHPUnit\Framework\TestCase
 */
trait MockWithExpectationsTrait
{
    protected function newPartialMockWithExpectations(
        $className,
        array $expectations = [],
        array $constructorArgs = null)
    {
        if ($this->isAssociative($expectations)) {
            $methods = array_unique(array_keys($expectations));
            $mock = $this->newPartialMock($className, $methods, $constructorArgs);
            $this->setExpectations($mock, $expectations);
        } else {
            $methods = array_unique(array_column($expectations, 0));
            $mock = $this->newPartialMock($className, $methods, $constructorArgs);
            $this->setAtExpectations($mock, $expectations);
        }

        return $mock;
    }

    protected function newPartialMock(
        $className,
        array $methods = [],
        array $constructorArgs = null)
    {
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
        $at = 0;
        foreach ($atExpectations as $atExpectation) {
            list($method, $expectation) = $atExpectation;
            if ($expectation === 'never') {
                $mock->expects($this->never())->method($method);
            } elseif (is_array($expectation)) {
                $expectation['invoked'] = $this->at($at++);
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
        $params = isset($expectation['params']) ? $expectation['params'] : [];
        $result = isset($expectation['result']) ? $expectation['result'] : null;
        $builder = $mock->expects($matcher)->method($method);
        call_user_func_array([$builder, 'with'], $params);
        $builder->willReturn($result);
    }

    /**
     * @param $invoked
     * @return PHPUnit_Framework_MockObject_Matcher_Invocation
     */
    protected function convertToMatcher($invoked)
    {
        if (is_numeric($invoked)) {
            $times = (int) $invoked;
            if ($times === 0) {
                return $this->never();
            } elseif ($times === 1) {
                return $this->once();
            } else {
                return $this->exactly($times);
            }
        } elseif ($invoked === 'once') {
            return $this->once();
        } elseif ($invoked === 'any') {
            return $this->any();
        } elseif ($invoked === 'never') {
            return $this->never();
        } elseif ($invoked === 'atLeastOnce') {
            return $this->atLeastOnce();
        } elseif (preg_match('/(\w+)\s+(\d+)/', $invoked, $matches)) {
            $method = $matches[1];
            $count = (int) $matches[2];
            if ($method === 'atLeast') {
                return $this->atLeast($count);
            } elseif ($method === 'exactly') {
                return $this->exactly($count);
            } elseif ($method === 'atMost') {
                return $this->atMost($count);
            }
        }
        throw new Exception("convertToMatcher cannot convert '$invoked'");
    }

    private function isAssociative(array $array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}

