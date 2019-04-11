<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

trait MockWithExpectationsTrait
{
    private $mockWithExpectations;

    /**
     * This trait expects to be used in a sub-class of PHPUnit\Framework\TestCase
     * which provides implementations of these functions:
     */
    abstract public function getMockBuilder($className);
    abstract public function registerMockObject(PHPUnit_Framework_MockObject_MockObject $mockObject);

    public function mockWithExpectations(
        $className,
        array $expectations = [],
        array $constructorArgs = null): PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMockWithExpectations()->createMockWithExpectations(
            $className,
            $expectations,
            $constructorArgs
        );
    }

    public function addExpectation(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $expectation) : void
    {
        $this->addExpectations($mock, [$expectation]);
    }

    public function addExpectations(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $expectations) : void
    {
        $this->getMockWithExpectations()->addExpectations($mock, $expectations);
    }

    protected function getMockWithExpectations() : MockWithExpectations
    {
        if ($this->mockWithExpectations === null) {
            $expectationsFactory = $this->getExpectationsFactory();
            $mockFactory = $this->getMockFactory();
            $this->mockWithExpectations = new MockWithExpectations($expectationsFactory, $mockFactory);
        }

        return $this->mockWithExpectations;
    }

    protected function getInvocationFactory() : InvocationFactory
    {
        return new InvocationFactory();
    }

    protected function getExpectationsFactory() : ExpectationsFactory
    {
        $invocationFactory = $this->getInvocationFactory();

        return new ExpectationsFactory($invocationFactory);
    }

    protected function getMockFactory() : MockFactory
    {
        if ($this instanceof TestCase) {
            return new MockFactory($this);
        }

        throw new TestException(\get_class($this).' is not an instance of '.TestCase::class);
    }
}
