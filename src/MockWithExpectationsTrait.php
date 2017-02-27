<?php
namespace Nopolabs\Test;

use PHPUnit_Framework_MockObject_MockObject;

trait MockWithExpectationsTrait
{
    protected function newPartialMockWithExpectations(
        $originalClassName,
        array $expectations,
        array $constructorArgs = null)
    {
        if (empty($expectations)) {
            $methods = [];
        } elseif ($this->isAssociative($expectations)) {
            $methods = array_unique(array_keys($expectations));
        } else {
            $methods = array_unique(array_map(function ($expectation) {
                return $expectation[0];
            }, $expectations));
        }

        $mock = $this->newPartialMock($originalClassName, $methods, $constructorArgs);

        if ($this->isAssociative($expectations)) {
            $this->setExpectations($mock, $expectations);
        } else {
            $this->setAtExpectations($mock, $expectations);
        }

        return $mock;
    }

    protected function newPartialMock(
        $originalClassName,
        array $methods,
        array $constructorArgs = null)
    {
        $builder = $this->getMockBuilder($originalClassName)
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
            if ($expectation === 'never') {
                $mock->expects($this->never())->method($method);
            } else {
                $this->setExpectation($mock, $method, $this->once(), $expectation);
            }
        }
    }

    protected function setAtExpectations(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $atExpectations)
    {
        foreach ($atExpectations as $at => $atExpectation) {
            list($method, $expectation) = $atExpectation;
            if ($expectation === 'never') {
                $mock->expects($this->never())->method($method);
            } else {
                $this->setExpectation($mock, $method, $this->at($at), $expectation);
            }
        }
    }

    protected function setExpectation(
        PHPUnit_Framework_MockObject_MockObject $mock,
        $method,
        $matcher,
        array $expectation)
    {
        $params = isset($expectation['params']) ? $expectation['params'] : [];
        $result = isset($expectation['result']) ? $expectation['result'] : null;
        $builder = $mock->expects($matcher)->method($method);
        call_user_func_array([$builder, 'with'], $params);
        $builder->willReturn($result);
    }

    private function isAssociative(array $array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}

