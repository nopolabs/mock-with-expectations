<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_Matcher_Invocation;

class InvocationFactoryTest extends TestCase
{
    /** @var InvocationFactory */
    private $invocationFactory;

    protected function setUp() : void
    {
        $this->invocationFactory = new InvocationFactory();
    }

    public function prepareInvocationDataProvider()
    {
        return [
            '0' => [0, $this->never()],
            '1' => [1, $this->once()],
            '2' => [2, $this->exactly(2)],
            '3' => [3, $this->exactly(3)],
            "'0'" => ['0', $this->never()],
            "'1'" => ['1', $this->once()],
            "'2'" => ['2', $this->exactly(2)],
            "'3'" => ['3', $this->exactly(3)],
            "'once'" => ['once', $this->once()],
            "'any'" => ['any', $this->any()],
            "'never'" => ['never', $this->never()],
            "'atLeastOnce'" => ['atLeastOnce', $this->atLeastOnce()],
            "'atLeast 2'" => ['atLeast 2', $this->atLeast(2)],
            "'exactly 2'" => ['exactly 2', $this->exactly(2)],
            "'atMost 2'" => ['atMost 2', $this->atMost(2)],
        ];
    }

    /**
     * @dataProvider prepareInvocationDataProvider
     */
    public function testPrepareInvocation($invoked, PHPUnit_Framework_MockObject_Matcher_Invocation $expected)
    {
        $this->assertEquals($expected, $this->invocationFactory->prepareInvocation($invoked));
    }

    public function failsDataProvider() : array
    {
        return [
            ['two times', "prepareInvocationString cannot handle 'two times'"],
            ['maybe', "prepareInvocationMethod cannot handle 'maybe'"],
            ['about 2', "prepareInvocationMethodCount cannot handle 'about 2'"],
        ];
    }

    /**
     * @dataProvider failsDataProvider
     */
    public function testPrepareInvocation_fails(string $invoked, string $expected)
    {
        $this->expectException(TestException::class);
        $this->expectExceptionMessage($expected);
        $this->invocationFactory->prepareInvocation($invoked);
    }
}
