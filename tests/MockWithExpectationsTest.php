<?php
declare(strict_types=1);

namespace Nopolabs\Test\Tests;

namespace Nopolabs\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class MockWithExpectationsTest extends TestCase
{
    /** @var TestCase|PHPUnit_Framework_MockObject_MockObject */
    private $testCase;

    /** @var MockWithExpectations */
    private $mockWithExpectations;

    protected function setUp() : void
    {
        $this->testCase = $this->createMock(TestCase::class);
        $invocationFactory = new InvocationFactory();
        $expectationsFactory = new ExpectationsFactory($invocationFactory);
        $mockFactory = new MockFactory($this->testCase);
        $this->mockWithExpectations = new MockWithExpectations($expectationsFactory, $mockFactory);

        $this->testCase->expects($this->any())
            ->method('getMockBuilder')
            ->willReturnCallback(function(string $className) {
                return $this->getMockBuilder($className);
            });
        }

    public function mockWithExpectationsDataProvider() : array
    {
        return [
            [[
                ['a', ['x'], 'y'],
                ['b', ['y'], 'z'],
                ['c', 'never'],
            ]],
            [[
                ['a'],
                ['b', [$this->anything()], 'z'],
                ['c', 'never'],
            ]],
            [[
                ['a', 'params' => ['x'], 'y'],
                ['b', ['y'], 'result' => 'z'],
                ['method' => 'c', 'never'],
            ]],
            [[
                ['a', 'params' => ['x'], 'y'],
                ['b', ['y'], 'result' => 'z'],
                ['c', 'invoked' => 'never'],
            ]],
            [[
                'c' => 'never',
                'b' => [['y'], 'z'],
                'a' => [['x'], 'y'],
            ]],
            [[
                'c' => ['invoked' => 'never'],
                'b' => ['params' => ['y'], 'z'],
                'a' => [['x'], 'result' => 'y'],
            ]],
        ];
    }

    /**
     * @dataProvider mockWithExpectationsDataProvider
     */
    public function testCreateMockWithExpectations(array $expectations) : void
    {
        /** @var MyClass $myTest */
        $myTest = $this->mockWithExpectations->createMockWithExpectations(MyClass::class, $expectations);

        $this->assertSame('z', $myTest->myFunction('x'));
    }

    public function expectationDataProvider()
    {
        $data = [
            [['fun'], null],
            [['method' => 'fun'], null],
            [
                ['fun', 'result' => 'foo'],
                ['params' => [], 'result' => 'foo']
            ],
            [
                ['fun', 'result' => ['foo', 'bar']],
                ['params' => [], 'result' => ['foo', 'bar']]
            ],
            [
                ['fun', ['foo'], 'result' => 'bar'],
                ['params' => ['foo'], 'result' => 'bar']
            ],
            [
                ['fun', ['foo'], 'bar'],
                ['params' => ['foo'], 'result' => 'bar']
            ],
            [
                ['fun', 'params' => ['foo'], 'result' => 'bar'],
                ['params' => ['foo'], 'result' => 'bar']
            ],
            [
                ['fun', 'params' => ['foo'], 'result' => 'bar'],
                ['params' => ['foo'], 'result' => 'bar']
            ],
            [
                ['fun', 'params' => ['foo', 2], 'result' => 'bar'],
                ['params' => ['foo', 2], 'result' => 'bar']
            ],
            [
                ['fun', ['foo', 2], 'bar'],
                ['params' => ['foo', 2], 'result' => 'bar']
            ],
            [
                ['fun', 'params' => ['foo', 2, 3], 'result' => 'bar'],
                ['params' => ['foo', 2, 3], 'result' => 'bar']
            ],
            [['fun', 'invoked' => 0], [], 0],
            [['fun', 'invoked' => 1], ['params' => [], 'result' => null], 1],
            [['fun', 'invoked' => 2], ['params' => [], 'result' => null], 2],
            [['fun', 'invoked' => 'once'], ['params' => [], 'result' => null], 1],
            [['fun', 'invoked' => 'any'], ['params' => [], 'result' => null], 17],
            [['fun', 'invoked' => 'never'], ['params' => [], 'result' => null], 0],
            [['fun', 'invoked' => 'atLeastOnce'], ['params' => [], 'result' => null], 1],
            [['fun', 'invoked' => 'atLeastOnce'], ['params' => [], 'result' => null], 2],
            [['fun', 'invoked' => 'atLeast 2'], ['params' => [], 'result' => null], 2],
            [['fun', 'invoked' => 'atLeast 2'], ['params' => [], 'result' => null], 3],
            [['fun', 'invoked' => 'exactly 7'], ['params' => [], 'result' => null], 7],
            [['fun', 'invoked' => 'atMost 2'], ['params' => [], 'result' => null], 1],
            [['fun', 'invoked' => 'atMost 2'], ['params' => [], 'result' => null], 2],
            [
                ['fun', 'result' => function($arg) {
                    return ($arg === 'foo' ? 'bar' : 'wat?');
                }],
                ['params' => ['foo'], 'result' => 'bar']
            ],
        ];

        return \array_slice($data, 0, 100);
    }

    /**
     * @dataProvider expectationDataProvider
     */
    public function testAddExpectation(array $expectation, $expected, $count = 1)
    {
        $mock = $this->createMock(MyClass::class);

        $this->mockWithExpectations->addExpectation($mock, $expectation);

        if ($count > 0) {
            $params = $expected['params'] ?? [];

            $actual = 'function never called';
            for ($i = 0; $i < $count; $i++) {
                $actual = \call_user_func_array([$mock, 'fun'], $params);
            }

            $this->assertSame($expected['result'], $actual);
        }
    }

    public function testAddExpectationWithThrows()
    {
        $mock = $this->createMock(MyClass::class);
        $this->expectExceptionMessage('boom!');

        $this->mockWithExpectations->addExpectation($mock, ['fun', 'throws' => new Exception('boom!')]);

        $mock->fun();
    }

    public function testAddExpectationParamsArrayCasting()
    {
        $mock = $this->createMock(MyClass::class);

        $this->mockWithExpectations->addExpectation($mock, ['fun', 'not an array', 42]);

        $this->assertSame(42, $mock->fun('not an array'));
    }

    public function testAddExpectationCannotHaveBothResultAndThrows()
    {
        $mock = $this->createMock(MyClass::class);
        $this->expectException(TestException::class);
        $this->expectExceptionMessage("cannot expect both 'result' and 'throws'");

        $this->mockWithExpectations->addExpectation($mock, ['fun', 'result' => true, 'throws' => new Exception()]);
    }

    public function expectationResultDataProvider()
    {
        return [
            [null],
            [false],
            [0],
            [[]],
            [true],
            [1],
            ['hello'],
            [1,2,3],
        ];
    }

    /**
     * @dataProvider expectationResultDataProvider
     */
    public function testAddExpectationResult($result)
    {
        $mock = $this->createMock(MyClass::class);

        $this->mockWithExpectations->addExpectation($mock, ['fun', [], $result]);
        $mock->expects($this->exactly(1))->method('fun');

        $this->assertSame($result, $mock->fun());
    }

    public function testAddExpectations() : void
    {
        $expectations = [
            ['fun', ['foo', 2], 'bar', 'invoked' => $this->at(0)],
            ['fun', ['foo'], 'baz', 'invoked' => $this->at(1)],
            ['fun', ['bloop'], 'throws' => new Exception('bloop'), 'invoked' => $this->at(2)],
        ];

        $mock = $this->createMock(MyClass::class);

        $this->mockWithExpectations->addExpectations($mock, $expectations);

        $this->assertSame('bar', $mock->fun('foo', 2));
        $this->assertSame('baz', $mock->fun('foo'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('bloop');
        $mock->fun('bloop');
    }

    public function missingMethodsDataProvider() : array
    {
        return [
            [ TestInterface::class, [
                'method1' => ['result' => 'hello'],
            ]],
            [ TestInterface::class, [
                ['method1', 'result' => 'hello'],
            ]],
            [ TestAbstractClass::class, [
                'method1' => ['result' => 'hello'],
            ]],
            [ TestAbstractClass::class, [
                ['method1', 'result' => 'hello'],
            ]],
        ];
    }

    /**
     * @dataProvider missingMethodsDataProvider
     */
    public function testMissingMethods(string $className, array $expectations) : void
    {
        $mock = $this->mockWithExpectations->createMockWithExpectations($className, $expectations);

        $this->assertSame('hello', $mock->method1());
    }
}
