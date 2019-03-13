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
    private $invoked;

    public function __construct(
        string $method,
        array $params = [],
        $result = null,
        $throws = null,
        $invoked = null)
    {
        $this->method = $method;
        $this->params = $params;
        $this->result = $result;
        $this->throws = $throws;
        $this->invoked = $invoked;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function build(PHPUnit_Framework_MockObject_MockObject $mock) : void
    {
        $builder = $this->mockExpects($mock, $this->invoked);

        $builder = $this->mockMethod($builder, $this->method);

        if (!empty($this->params)) {
            $builder = $this->mockParams($builder, $this->params);
        }

        if ($this->result !== null) {
            $builder = $this->mockResult($builder, $this->result);
        }

        if ($this->throws !== null) {
            $this->mockThrows($builder, $this->throws);
        }
    }

    public function prepareInvocation($invoked) : PHPUnit_Framework_MockObject_Matcher_Invocation
    {
        if ($invoked === null) {
            return TestCase::any();
        }

        if (\is_object($invoked)) {
            return $invoked;
        }

        if (\is_numeric($invoked)) {
            return $this->prepareInvocationNumeric((int)$invoked);
        }

        if (\is_string($invoked)) {
            return $this->prepareInvocationString($invoked);
        }

        throw new TestException("prepareInvocation cannot prepare '$invoked'");
    }

    protected function prepareInvocationNumeric(int $times): PHPUnit_Framework_MockObject_Matcher_Invocation
    {
        switch ($times) {
            case 0:
                return TestCase::never();
            case 1:
                return TestCase::once();
            default:
                return TestCase::exactly($times);
        }
    }

    protected function prepareInvocationString(string $invoked): PHPUnit_Framework_MockObject_Matcher_Invocation
    {
        if (preg_match("/(?'method'\w+)(?:\s+(?'count'\d+))?/", $invoked, $matches)) {
            $method = $matches['method'];
            if (!isset($matches['count'])) {
                switch ($method) {
                    case 'once':
                        return TestCase::once();
                    case 'any':
                        return TestCase::any();
                    case 'never':
                        return TestCase::never();
                    case 'atLeastOnce':
                        return TestCase::atLeastOnce();
                }
            } else {
                $count = (int)$matches['count'];
                switch ($method) {
                    case 'atLeast':
                        return TestCase::atLeast($count);
                    case 'exactly':
                        return TestCase::exactly($count);
                    case 'atMost':
                        return TestCase::atMost($count);
                }
            }
        }

        throw new TestException("prepareInvocationString cannot handle '$invoked'");
    }

    protected function mockExpects(
        PHPUnit_Framework_MockObject_MockObject $mock,
        $invoked) : PHPUnit_Framework_MockObject_Builder_InvocationMocker
    {
        return $mock->expects($this->prepareInvocation($invoked));
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
