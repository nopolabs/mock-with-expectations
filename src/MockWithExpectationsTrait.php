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

    protected function setExpectation(
        PHPUnit_Framework_MockObject_MockObject $mock,
        $expectation) : void
    {
        $this->getMockWithExpectations()->setExpectation($mock, $expectation);
    }

    protected function setExpectations(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $expectations) : void
    {
        $this->getMockWithExpectations()->setExpectations($mock, $expectations);
    }

    protected function getMockWithExpectations() : MockWithExpectations
    {
        if ($this->mockWithExpectations === null) {
            if ($this instanceof TestCase) {
                $this->mockWithExpectations = new MockWithExpectations($this);
            } else {
                throw new TestException(\get_class($this).' is not an instance of '.TestCase::class);
            }
        }

        return $this->mockWithExpectations;
    }
}
