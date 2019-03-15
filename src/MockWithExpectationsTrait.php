<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * This trait expects to be used in a sub-class of PHPUnit\Framework\TestCase
 */
trait MockWithExpectationsTrait
{
    private $mockWithExpectations;

    protected function mockWithExpectations(
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

    protected function addExpectation(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $expectation) : void
    {
        $this->addExpectations($mock, [$expectation]);
    }

    protected function addExpectations(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $expectations) : void
    {
        $this->getMockWithExpectations()->addExpectations($mock, $expectations);
    }

    protected function getMockWithExpectations() : MockWithExpectations
    {
        if ($this->mockWithExpectations === null) {
            if ($this instanceof TestCase) {
                $invocationFactory = new InvocationFactory();
                $expectationsFactory = new ExpectationsFactory($invocationFactory);
                $mockFactory = new MockFactory($this);
                $this->mockWithExpectations = new MockWithExpectations($expectationsFactory, $mockFactory);
            } else {
                throw new TestException(\get_class($this).' is not an instance of '.TestCase::class);
            }
        }

        return $this->mockWithExpectations;
    }
}
