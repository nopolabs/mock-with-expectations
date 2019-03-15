<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use PHPUnit_Framework_MockObject_MockObject;

class MockWithExpectations
{
    /** @var ExpectationsFactory */
    private $expectationsFactory;

    /** @var MockFactory */
    private $mockFactory;

    public function __construct(
        ExpectationsFactory $expectationsFactory,
        MockFactory $mockFactory)
    {
        $this->expectationsFactory = $expectationsFactory;
        $this->mockFactory = $mockFactory;
    }

    public function createMockWithExpectations(
        string $className,
        array $expects = [],
        array $constructorArgs = null) : PHPUnit_Framework_MockObject_MockObject
    {
        $expectations = $this->expectationsFactory->createExpectations($expects);
        $expectedMethods = $expectations->getExpectedMethods();
        $mock = $this->mockFactory->newPartialMock($className, $expectedMethods, $constructorArgs);
        $expectations->set($mock);

        return $mock;
    }

    public function addExpectation(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $expectation) : PHPUnit_Framework_MockObject_MockObject
    {
        $this->addExpectations($mock, [$expectation]);

        return $mock;
    }

    public function addExpectations(
        PHPUnit_Framework_MockObject_MockObject $mock,
        array $expectations) : PHPUnit_Framework_MockObject_MockObject
    {
        $this->expectationsFactory
            ->createExpectations($expectations)
            ->set($mock);

        return $mock;
    }
}
