<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

trait MockWithExpectationsTrait
{
    /** @var MockWithExpectations */
    private $mockWithExpectations;

    /** @var InvocationFactory */
    private $invocationFactory;

    /** @var ExpectationsFactory */
    private $expectationsFactory;

    /** @var MockFactory */
    private $mockFactory;

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

    protected function setInvocationFactory(InvocationFactory $invocationFactory) : void
    {
        $this->invocationFactory = $invocationFactory;
    }

    protected function getInvocationFactory() : InvocationFactory
    {
        if (!$this->invocationFactory) {
            $this->invocationFactory = new InvocationFactory();
        }

        return $this->invocationFactory;
    }

    protected function setExpectationsFactory(ExpectationsFactory $expectationsFactory) : void
    {
        $this->expectationsFactory = $expectationsFactory;
    }

    protected function getExpectationsFactory() : ExpectationsFactory
    {
        if (!$this->expectationsFactory) {
            $invocationFactory = $this->getInvocationFactory();
            $this->expectationsFactory = new ExpectationsFactory($invocationFactory);
        }

        return $this->expectationsFactory;
    }

    protected function setMockFactory(MockFactory $mockFactory) : void
    {
        $this->mockFactory = $mockFactory;
    }

    protected function getMockFactory() : MockFactory
    {
        if (!$this->mockFactory) {
            if ($this instanceof TestCase) {
                $this->mockFactory = new MockFactory($this);
            } else {
                throw new TestException(\get_class($this).' is not an instance of '.TestCase::class);
            }
        }

        return $this->mockFactory;
    }
}
