<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_Builder_InvocationMocker;
use PHPUnit_Framework_MockObject_Matcher_Invocation;
use PHPUnit_Framework_MockObject_MockObject;

class ExpectationTest extends TestCase
{
    public function testGetMethod() : void
    {
        $this->assertSame('methodName', (new Expectation('methodName'))->getMethod());
    }

    public function setDataProvider() : array
    {
        return [
            ['fun', [], null, null, $this->never()],
            ['fun', ['foo'], null, null, $this->any()],
            ['fun', ['foo'], 'bar', null, $this->any()],
            ['fun', ['foo'], null, 'boom!', $this->any()],
            ['fun', ['foo'], 'bar', null, $this->once()],
        ];
    }

    /**
     * @dataProvider setDataProvider
     */
    public function testSet(
        string $method,
        array $params,
        $result,
        $throws,
        PHPUnit_Framework_MockObject_Matcher_Invocation $invocation) : void
    {
        $mock = $this->createMock(MyClass::class);
        $builder = $this->createMock(PHPUnit_Framework_MockObject_Builder_InvocationMocker::class);

        /** @var Expectation|PHPUnit_Framework_MockObject_MockObject $expectation */
        $expectation = $this->getMockBuilder(Expectation::class)
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(['mockExpects', 'mockMethod', 'mockParams', 'mockResult', 'mockThrows'])
            ->setConstructorArgs([$method, $params, $result, $throws, $invocation])
            ->getMock();

        $expectation->expects($this->once())
            ->method('mockExpects')
            ->with($mock, $this->isInstanceOf(PHPUnit_Framework_MockObject_Matcher_Invocation::class))
            ->willReturn($builder);

        $expectation->expects($this->once())
            ->method('mockMethod')
            ->with($builder, $method)
            ->willReturn($builder);

        if (empty($params)) {
            $expectation->expects($this->never())
                ->method('mockParams');
        } else {
            $expectation->expects($this->once())
                ->method('mockParams')
                ->with($builder, $params)
                ->willReturn($builder);
        }

        if ($result === null) {
            $expectation->expects($this->never())
                ->method('mockResult');
        } else {
            $expectation->expects($this->once())
                ->method('mockResult')
                ->with($builder, $result)
                ->willReturn($builder);
        }

        if ($throws === null) {
            $expectation->expects($this->never())
                ->method('mockThrows');
        } else {
            $expectation->expects($this->once())
                ->method('mockThrows')
                ->with($builder, $throws)
                ->willReturn($builder);
        }

        $expectation->set($mock);
    }
}
