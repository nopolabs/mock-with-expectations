<?php
declare (strict_types=1);

namespace Nopolabs\Test;

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
        return $this->getMockWithExpectations()->mockWithExpectations(
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
            $this->mockWithExpectations = new MockWithExpectations($this);
        }

        return $this->mockWithExpectations;
    }
}
