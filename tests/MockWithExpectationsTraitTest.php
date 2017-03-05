<?php
namespace Nopolabs\Test\Tests;

use Nopolabs\Test\MockWithExpectationsTrait;
use PHPUnit\Framework\TestCase;

class MockWithExpectationsTraitTest extends TestCase
{
    use MockWithExpectationsTrait;

    public function testExpectations()
    {
        /** @var MyClass $myTest */
        $myTest = $this->newPartialMockWithExpectations(MyClass::class, [
            'b' => ['params' => ['y'], 'result' => 'z'],
            'a' => ['params' => ['x'], 'result' => 'y'],
            'c' => 'never',
        ]);

        $this->assertEquals('z', $myTest->myFunction('x'));
    }

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