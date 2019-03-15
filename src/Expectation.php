<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use Closure;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_Builder_InvocationMocker;
use PHPUnit_Framework_MockObject_Matcher_Invocation;
use PHPUnit_Framework_MockObject_MockObject;

class Expectation
{
    private $method;
    private $params;
    private $result;
    private $throws;
    private $invocation;

    public function __construct(
        string $method,
        array $params = [],
        $result = null,
        $throws = null,
        PHPUnit_Framework_MockObject_Matcher_Invocation $invocation = null)
    {
        $this->method = $method;
        $this->params = $params;
        $this->result = $result;
        $this->throws = $throws;
        $this->invocation = $invocation ?? TestCase::any();
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function set(PHPUnit_Framework_MockObject_MockObject $mock) : void
    {
        $builder = $this->mockExpects($mock, $this->invocation);

        $this->mockMethod($builder, $this->method);

        if (!empty($this->params)) {
            $this->mockParams($builder, $this->params);
        }

        if ($this->result !== null) {
            $this->mockResult($builder, $this->result);
        }

        if ($this->throws !== null) {
            $this->mockThrows($builder, $this->throws);
        }
    }

    protected function mockExpects(
        PHPUnit_Framework_MockObject_MockObject $mock,
        PHPUnit_Framework_MockObject_Matcher_Invocation $invocation) : PHPUnit_Framework_MockObject_Builder_InvocationMocker
    {
        return $mock->expects($invocation);
    }

    protected function mockMethod(
        PHPUnit_Framework_MockObject_Builder_InvocationMocker $builder,
        string $method) : PHPUnit_Framework_MockObject_Builder_InvocationMocker
    {
        return $builder->method($method);
    }

    protected function mockParams(
        PHPUnit_Framework_MockObject_Builder_InvocationMocker $builder,
        array $params) : PHPUnit_Framework_MockObject_Builder_InvocationMocker
    {
        return \call_user_func_array([$builder, 'with'], $params);
    }

    protected function mockResult(
        PHPUnit_Framework_MockObject_Builder_InvocationMocker $builder,
        $result) : PHPUnit_Framework_MockObject_Builder_InvocationMocker
    {
        if ($result instanceof Closure) {
            return $builder->willReturnCallback($result);
        }

        return $builder->willReturn($result);
    }

    protected function mockThrows(
        PHPUnit_Framework_MockObject_Builder_InvocationMocker $builder,
        $throws) : PHPUnit_Framework_MockObject_Builder_InvocationMocker
    {
        if (\is_string($throws)) {
            $throws = new Exception($throws);
        }

        return $builder->willThrowException($throws);
    }
}
