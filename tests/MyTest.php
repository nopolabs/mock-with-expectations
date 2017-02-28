<?php
namespace Nopolabs\Test\Tests;

use Nopolabs\Test\MockWithExpectationsTrait;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
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
}