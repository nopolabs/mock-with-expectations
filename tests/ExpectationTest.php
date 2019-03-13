<?php
declare(strict_types=1);

use Nopolabs\Test\Expectation;
use Nopolabs\Test\Tests\MyClass;
use PHPUnit\Framework\TestCase;

class ExpectationTest extends TestCase
{
    public function testGetMethod() : void
    {
        $this->assertSame('methodName', (new Expectation('methodName'))->getMethod());
    }

    public function buildDataProvider() : array
    {
        return [
            ['fun'],
        ];
    }

    /**
     * @dataProvider buildDataProvider
     */
    public function testBuild(
        string $method,
        array $params = [],
        $result = null,
        $throws = null,
        $invoked = null) : void
    {
        $mock = $this->createMock(MyClass::class);

        $builder = $this->createMock(PHPUnit_Framework_MockObject_Builder_InvocationMocker::class);

        /** @var Expectation|PHPUnit_Framework_MockObject_MockObject $expectation */
        $expectation = $this->getMockBuilder(Expectation::class)
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(['mockExpects', 'mockMethod', 'mockParams', 'mockResult', 'mockThrows'])
            ->setConstructorArgs([$method, $params, $result, $throws, $invoked])
            ->getMock();

        $expectation->expects($this->once())
            ->method('mockExpects')
            ->with($mock, $invoked)
            ->willReturn($builder);

        $expectation->expects($this->once())
            ->method('mockMethod')
            ->with($builder, $method)
            ->willReturn($builder);

        if (!empty($this->params)) {
            $expectation->expects($this->once())
                ->method('mockParams')
                ->with($builder, $params);
        }

        if ($result !== null) {
            $expectation->expects($this->once())
                ->method('mockResult')
                ->with($builder, $result);
        }

        if ($throws !== null) {
            $expectation->expects($this->once())
                ->method('mockThrows')
                ->with($builder, $throws);
        }

        $expectation->build($mock);
    }

    public function prepareInvocationDataProvider()
    {
        return [
            [0, $this->never()],
            [1, $this->once()],
            [2, $this->exactly(2)],
            [3, $this->exactly(3)],
            ['0', $this->never()],
            ['1', $this->once()],
            ['2', $this->exactly(2)],
            ['3', $this->exactly(3)],
            ['once', $this->once()],
            ['any', $this->any()],
            ['never', $this->never()],
            ['atLeastOnce', $this->atLeastOnce()],
            ['atLeast 2', $this->atLeast(2)],
            ['exactly 2', $this->exactly(2)],
            ['atMost 2', $this->atMost(2)],
        ];
    }

    /**
     * @dataProvider prepareInvocationDataProvider
     */
    public function testPrepareInvocation($invoked, $expected)
    {
        $expectation = new Expectation('method', [], null, null, $invoked);

        $invocation = $expectation->prepareInvocation($invoked);

        $this->assertEquals($expected, $invocation);
    }

    public function invokedDataProvider() : array
    {
        $data = array_map(
            function($args) {
                list($invoked, $expected) = $args;
                return [['invoked' => $invoked], $expected];
            },
            $this->convertToInvocationDataProvider()
        );
        $data[] = [[], $this->any()];
        $data[] = [['invoked' => $this->at(17)], $this->at(17)];

        return $data;
    }
}
