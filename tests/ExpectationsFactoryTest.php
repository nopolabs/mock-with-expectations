<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class ExpectationsFactoryTest extends TestCase
{
    /** @var ExpectationsFactory|PHPUnit_Framework_MockObject_MockObject */
    private $expectationsFactory;

    protected function setUp() : void
    {
        $builder = $this->getMockBuilder(ExpectationsFactory::class);
        $builder->disableOriginalClone();
        $builder->disableArgumentCloning();
        $builder->disallowMockingUnknownTypes();
        $builder->setMethods(['newExpectation']);
        $builder->setConstructorArgs([new InvocationFactory()]);

        $this->expectationsFactory = $builder->getMock();
    }

    public function buildExpectationsDataProvider() : array
    {
        return [
            [
                [
                    'method1' => [[1,2,3]],
                ],
                [
                    ['method1', [1,2,3], null, null, $this->any()],
                ],
            ],
            [
                [
                    'method2' => [[], true],
                ],
                [
                    ['method2', [], true, null, $this->any()],
                ],
            ],
            [
                [
                    'method3' => [[false], null, 'boom!', 2],
                ],
                [
                    ['method3', [false], null, 'boom!', $this->exactly(2)],
                ],
            ],
            [
                [
                    'method1' => [[1,2,3]],
                    'method2' => [[], true],
                    'method3' => [[false], null, 'boom!', 2],
                ],
                [
                    ['method1', [1,2,3], null, null, $this->any()],
                    ['method2', [], true, null, $this->any()],
                    ['method3', [false], null, 'boom!', $this->exactly(2)],
                ],
            ],
            [
                [
                    ['method1', [1,2,3]],
                    ['method2', [], true],
                    ['method3', [false], null, 'boom!'],
                ],
                [
                    ['method1', [1,2,3], null, null, $this->at(0)],
                    ['method2', [], true, null, $this->at(1)],
                    ['method3', [false], null, 'boom!', $this->at(2)],
                ],
            ],
        ];
    }

    /**
     * @dataProvider buildExpectationsDataProvider
     */
    public function testCreateExpectations(array $expects, array $expected) : void
    {
        $index = 0;
        $methods = [];
        foreach ($expected as list($method, $params, $result, $throws, $invocation)) {
            $expectation = $this->createMock(Expectation::class);

            $expectation->expects($this->once())
                ->method('getMethod')
                ->willReturn($method);

            $this->expectationsFactory->expects($this->at($index++))
                ->method('newExpectation')
                ->with($method, $params, $result, $throws, $invocation)
                ->willReturn($expectation);

            $methods[] = $method;
        }

        $expectations = $this->expectationsFactory->createExpectations($expects);

        $this->assertSame(array_unique($methods), $expectations->getExpectedMethods());
    }
}
