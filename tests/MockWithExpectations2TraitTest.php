<?php

declare (strict_types=1);

namespace Nopolabs\Test\Tests;

use Exception;
use Nopolabs\Test\MockWithExpectations2Trait;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_Matcher_Invocation;

class MockWithExpectations2TraitTest extends TestCase
{
    use MockWithExpectations2Trait;

    public function testExpectationsList()
    {
        /** @var MyClass $myTest */
        $myTest = $this->mockWithExpectations(MyClass::class, [
            ['a', ['x'], 'y'],
            ['b', ['y'], 'z'],
            ['c', 'never'],
        ]);

        $this->assertSame('z', $myTest->myFunction('x'));
    }

    public function testExpectationsListLoosely()
    {
        /** @var MyClass $myTest */
        $myTest = $this->mockWithExpectations(MyClass::class, [
            ['a'],
            ['b', [$this->anything()], 'z'],
            ['c', 'never'],
        ]);

        $this->assertEquals('z', $myTest->myFunction('x'));
    }

    public function testExpectationsMap()
    {
        /** @var MyClass $myTest */
        $myTest = $this->mockWithExpectations(MyClass::class, [
            'c' => 'never',
            'b' => [['y'], 'z'],
            'a' => [['x'], 'y'],
        ]);

        $this->assertEquals('z', $myTest->myFunction('x'));
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
                ['fun', 'result' => function ($arg) {
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
    public function testSetExpectation(array $expectation, $expected, $count = 1)
    {
        $mock = $this->createMock(MyClass::class);

        $this->setExpectation($mock, $expectation);

        if ($count > 0) {
            $params = $expected['params'] ?? [];

            $actual = 'function never called';
            for ($i = 0; $i < $count; $i++) {
                $actual = \call_user_func_array([$mock, 'fun'], $params);
            }

            $this->assertSame($expected['result'], $actual);
        }
    }

    public function testSetExpectationWithThrows()
    {
        $mock = $this->createMock(MyClass::class);

        $this->setExpectation($mock, ['fun', 'throws' => new Exception('boom!')]);

        try {
            $mock->fun();
            $this->fail('expected an exception');
        } catch (Exception $e) {
            $this->assertEquals('boom!', $e->getMessage());
        }
    }

    public function testSetExpectationParamsArrayCasting()
    {
        $mock = $this->createMock(MyClass::class);

        $this->setExpectation($mock, ['fun', 'not an array', 42]);

        $this->assertSame(42, $mock->fun('not an array'));
    }

    public function testSetExpectationCannotHaveBothResultAndThrows()
    {
        $mock = $this->createMock(MyClass::class);

        try {
            $this->setExpectation($mock, ['fun', 'result' => true, 'throws' => new Exception()]);
            $this->fail('expected an exception');
        } catch (Exception $e) {
            $this->assertEquals("cannot expect both 'result' and 'throws'", $e->getMessage());
        }
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
    public function testSetExpectationResult($result)
    {
        $mock = $this->createMock(MyClass::class);

        $this->setExpectation($mock, ['fun', [], $result]);
        $mock->expects($this->exactly(1))->method('fun');

        $this->assertSame($result, $mock->fun());
    }

    public function testSetAtExpectationForMethodWithNoParamsNoResultNoThrows()
    {
        $mock = $this->createMock(MyClass::class);

        $this->setExpectations($mock, [['fun'], ['fun'], ['fun']]);

        $mock->expects($this->exactly(3))->method('fun');

        $mock->fun();
        $mock->fun();
        $mock->fun();
    }

    public function testMockWithExpectations_interfaceMissingMethods()
    {
        $mock = $this->mockWithExpectations(TestInterface::class, [
            'method1' => ['result' => 'hello'],
        ]);

        $this->assertSame('hello', $mock->method1());
    }

    public function testMockWithExpectations_interfaceMissingMethodsAt()
    {
        $mock = $this->mockWithExpectations(TestInterface::class, [
            ['method1', 'result' => 'hello'],
        ]);

        $this->assertSame('hello', $mock->method1());
    }

    public function testMockWithExpectations_abstractMissingMethods()
    {
        $mock = $this->mockWithExpectations(TestAbstractClass::class, [
            'method1' => ['result' => 'hello'],
        ]);

        $this->assertSame('hello', $mock->method1());
    }

    public function testMockWithExpectations_abstractMissingMethodsAt()
    {
        $mock = $this->mockWithExpectations(TestAbstractClass::class, [
            ['method1', 'result' => 'hello'],
        ]);

        $this->assertSame('hello', $mock->method1());
    }
}
