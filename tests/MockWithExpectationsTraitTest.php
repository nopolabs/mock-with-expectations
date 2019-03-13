<?php

declare (strict_types=1);

namespace Nopolabs\Test\Tests;

use Exception;
use Nopolabs\Test\MockWithExpectations;
use Nopolabs\Test\MockWithExpectationsTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_Matcher_Invocation;

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

    public function testSetExpectation() : void
    {
        $expectation = ['fun', ['foo', 'bar'], 'baz'];

        $mock = $this->createMock(MyClass::class);

        $this->setExpectation($mock, $expectation);

        $this->assertSame('baz', $mock->fun('foo', 'bar'));
    }

    public function testSetExpectations() : void
    {
        $expectations = [
            ['fun', ['foo', 'bar'], 'baz', 'invoked' => TestCase::at(0)],
            ['fun', ['foo', 'bar'], 'blat', 'invoked' => TestCase::at(1)],
        ];

        $mock = $this->createMock(MyClass::class);

        $this->setExpectations($mock, $expectations);

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
