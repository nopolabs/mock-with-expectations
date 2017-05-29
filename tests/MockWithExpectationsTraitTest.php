<?php
namespace Nopolabs\Test\Tests;

use Exception;
use Nopolabs\Test\MockWithExpectationsTrait;
use PHPUnit\Framework\TestCase;

class MockWithExpectationsTraitTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function testAtExpectations()
    {
        /** @var MyClass $myTest */
        $myTest = $this->newPartialMockWithExpectations(MyClass::class, [
            ['a', ['params' => ['x'], 'result' => 'y']],
            ['b', ['params' => ['y'], 'result' => 'z']],
            ['c', 'never'],
        ]);

        $this->assertEquals('z', $myTest->myFunction('x'));
    }

    public function testExpectations()
    {
        /** @var MyClass $myTest */
        $myTest = $this->newPartialMockWithExpectations(MyClass::class, [
            'c' => 'never',
            'b' => ['params' => ['y'], 'result' => 'z'],
            'a' => ['params' => ['x'], 'result' => 'y'],
        ]);

        $this->assertEquals('z', $myTest->myFunction('x'));
    }

    public function expectationDataProvider()
    {
        $data = [
            [[], null],
            [
                ['result' => 'foo'],
                ['params' => [], 'result' => 'foo']
            ],
            [
                ['result' => ['foo', 'bar']],
                ['params' => [], 'result' => ['foo', 'bar']]
            ],
            [
                ['params' => ['foo'], 'result' => 'bar'],
                ['params' => ['foo'], 'result' => 'bar']
            ],
            [
                ['params' => ['foo', 2], 'result' => 'bar'],
                ['params' => ['foo', 2], 'result' => 'bar']
            ],
            [
                ['params' => ['foo', 2, 3], 'result' => 'bar'],
                ['params' => ['foo', 2, 3], 'result' => 'bar']
            ],
            [['invoked' => 0], [], 0],
            [['invoked' => 1], ['params' => [], 'result' => null], 1],
            [['invoked' => 2], ['params' => [], 'result' => null], 2],
            [['invoked' => 'once'], ['params' => [], 'result' => null], 1],
            [['invoked' => 'any'], ['params' => [], 'result' => null], 17],
            [['invoked' => 'never'], ['params' => [], 'result' => null], 0],
            [['invoked' => 'atLeastOnce'], ['params' => [], 'result' => null], 1],
            [['invoked' => 'atLeastOnce'], ['params' => [], 'result' => null], 2],
            [['invoked' => 'atLeast 2'], ['params' => [], 'result' => null], 2],
            [['invoked' => 'atLeast 2'], ['params' => [], 'result' => null], 3],
            [['invoked' => 'exactly 7'], ['params' => [], 'result' => null], 7],
            [['invoked' => 'atMost 2'], ['params' => [], 'result' => null], 1],
            [['invoked' => 'atMost 2'], ['params' => [], 'result' => null], 2],
            [
                ['result' => function ($arg) {
                    return ($arg === 'foo' ? 'bar' : 'wat?');
                }],
                ['params' => ['foo'], 'result' => 'bar']
            ],
        ];

        return array_slice($data, 19, 100);
    }

    /**
     * @dataProvider expectationDataProvider
     */
    public function testSetExpectation(array $expectation, $expected, $count = 1)
    {
        $myTest = $this->createMock(MyClass::class);

        $this->setExpectation($myTest, 'fun', $expectation);

        if ($count > 0) {
            $params = $expected['params'] ?? [];

            $actual = 'function never called';
            for ($i = 0; $i < $count; $i++) {
                $actual = call_user_func_array([$myTest, 'fun'], $params);
            }

            $this->assertSame($expected['result'], $actual);
        }
    }

    public function testSetExpectationWithThrows()
    {
        $myTest = $this->createMock(MyClass::class);

        $this->setExpectation($myTest, 'fun', ['throws' => new Exception('boom!')]);

        try {
            $myTest->fun();
            $this->fail('expected an exception');
        } catch (Exception $e) {
            $this->assertEquals('boom!', $e->getMessage());
        }
    }

    public function testSetExpectationParamsMustBeAnArray()
    {
        $myTest = $this->createMock(MyClass::class);

        try {
            $this->setExpectation($myTest, 'fun', ['params' => 'not a array']);
            $this->fail('expected an exception');
        } catch (Exception $e) {
            $this->assertEquals("expected params to be an array, got 'not a array'", $e->getMessage());
        }
    }

    public function testSetExpectationCannotHaveBothResultAndThrows()
    {
        $myTest = $this->createMock(MyClass::class);

        try {
            $this->setExpectation($myTest, 'fun', ['result' => true, 'throws' => new Exception()]);
            $this->fail('expected an exception');
        } catch (Exception $e) {
            $this->assertEquals("cannot expect both 'result' and 'throws'", $e->getMessage());
        }
    }

    public function testSetAtExpectationForMethodWithNoParamsNoResultNoThrows()
    {
        $myTest = $this->createMock(MyClass::class);

        $this->setAtExpectations($myTest, [['fun'], ['fun'], ['fun']]);

        $myTest->expects($this->exactly(3))->method('fun');

        $myTest->fun();
        $myTest->fun();
        $myTest->fun();
    }

    public function testNewPartialMockWithExpectations_interfaceMissingMethods()
    {
        $mock = $this->newPartialMockWithExpectations(TestInterface::class, [
            'method1' => ['result' => 'hello'],
        ]);

        $this->assertSame('hello', $mock->method1());
    }

    public function testNewPartialMockWithExpectations_interfaceMissingMethodsAt()
    {
        $mock = $this->newPartialMockWithExpectations(TestInterface::class, [
            ['method1', ['result' => 'hello']],
        ]);

        $this->assertSame('hello', $mock->method1());
    }

    public function testNewPartialMockWithExpectations_abstractMissingMethods()
    {
        $mock = $this->newPartialMockWithExpectations(TestAbstractClass::class, [
            'method1' => ['result' => 'hello'],
        ]);

        $this->assertSame('hello', $mock->method1());
    }

    public function testNewPartialMockWithExpectations_abstractMissingMethodsAt()
    {
        $mock = $this->newPartialMockWithExpectations(TestAbstractClass::class, [
            ['method1', ['result' => 'hello']],
        ]);

        $this->assertSame('hello', $mock->method1());
    }

    public function convertToMatcherDataProvider()
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
     * @dataProvider convertToMatcherDataProvider
     * @param $invoked
     * @param $expected
     */
    public function testConvertToMatcher($invoked, $expected)
    {
        $this->assertEquals($expected, $this->convertToMatcher($invoked));
    }
}