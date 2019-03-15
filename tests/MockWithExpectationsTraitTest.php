<?php

declare (strict_types=1);

namespace Nopolabs\Test\Tests;

namespace Nopolabs\Test;

use PHPUnit\Framework\TestCase;

class MockWithExpectationsTraitTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function testMockWithExpectations() : void
    {
        $expectations = [
            ['a', ['x'], 'y'],
            ['b', ['y'], 'z'],
            ['c', 'never'],
        ];

        /** @var MyClass $myTest */
        $myTest = $this->mockWithExpectations(MyClass::class, $expectations);

        $this->assertSame('z', $myTest->myFunction('x'));
    }

    public function testAddExpectation() : void
    {
        $expectation = ['fun', ['foo', 'bar'], 'baz'];

        $mock = $this->createMock(MyClass::class);

        $this->addExpectation($mock, $expectation);

        $this->assertSame('baz', $mock->fun('foo', 'bar'));
    }

    public function testAddExpectations() : void
    {
        $expectations = [
            ['fun', ['foo', 'bar'], 'baz', 'invoked' => TestCase::at(0)],
            ['fun', ['foo', 'bar'], 'blat', 'invoked' => TestCase::at(1)],
        ];

        $mock = $this->createMock(MyClass::class);

        $this->addExpectations($mock, $expectations);

        $this->assertSame('baz', $mock->fun('foo', 'bar'));
        $this->assertSame('blat', $mock->fun('foo', 'bar'));
    }

    public function testGetMockWithExpectations() : void
    {
        $mockWithExpectations = $this->getMockWithExpectations();

        $this->assertInstanceOf(MockWithExpectations::class, $mockWithExpectations);
        $this->assertSame($mockWithExpectations, $this->getMockWithExpectations());
    }
}
