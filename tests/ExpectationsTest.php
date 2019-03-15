<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class ExpectationsTest extends TestCase
{
    /** @var Expectation|PHPUnit_Framework_MockObject_MockObject */
    private $expectation1;

    /** @var Expectation|PHPUnit_Framework_MockObject_MockObject */
    private $expectation2;

    /** @var Expectations */
    private $expectations;

    protected function setUp() : void
    {
        $this->expectation1 = $this->createMock(Expectation::class);
        $this->expectation2 = $this->createMock(Expectation::class);

        $this->expectations = new Expectations([
            $this->expectation1,
            $this->expectation2,
        ]);
    }

    public function testGetExpectedMethods() : void
    {
        $this->expectation1->expects($this->once())
            ->method('getMethod')
            ->willReturn('method1');

        $this->expectation2->expects($this->once())
            ->method('getMethod')
            ->willReturn('method2');

        $this->assertSame(['method1', 'method2'], $this->expectations->getExpectedMethods());
    }

    public function testSet() : void
    {
        $mock = $this->createPartialMock(PHPUnit_Framework_MockObject_MockObject::class, []);

        $this->expectation1->expects($this->once())
            ->method('set')
            ->with($mock);

        $this->expectation2->expects($this->once())
            ->method('set')
            ->with($mock);

        $this->expectations->set($mock);
    }
}
