<?php
declare(strict_types=1);

namespace Nopolabs\Test;


use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_Matcher_Invocation;

class InvocationFactory
{
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

    private function prepareInvocationNumeric(int $times): PHPUnit_Framework_MockObject_Matcher_Invocation
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

    private function prepareInvocationString(string $invoked): PHPUnit_Framework_MockObject_Matcher_Invocation
    {
        $parsed = $this->parseInvoked($invoked);

        if (isset($parsed['method'], $parsed['count'])) {
            return $this->prepareInvocationMethodCount($parsed['method'], (int)$parsed['count']);
        }

        if (isset($parsed['method'])) {
            return $this->prepareInvocationMethod($parsed['method']);
        }

        throw new TestException("prepareInvocationString cannot handle '$invoked'");
    }

    private function parseInvoked(string $invoked) : array
    {
        if (preg_match("/^(?'method'\w+)(?:\s+(?'count'\d+))?$/", $invoked, $matches)) {
            return $matches;
        }

        return [];
    }

    private function prepareInvocationMethodCount(string $method, int $count) : PHPUnit_Framework_MockObject_Matcher_Invocation
    {
        switch ($method) {
            case 'atLeast':
                return TestCase::atLeast($count);
            case 'exactly':
                return TestCase::exactly($count);
            case 'atMost':
                return TestCase::atMost($count);
        }

        throw new TestException("prepareInvocationMethodCount cannot handle '$method $count'");
    }

    private function prepareInvocationMethod($method) : PHPUnit_Framework_MockObject_Matcher_Invocation
    {
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

        throw new TestException("prepareInvocationMethod cannot handle '$method'");
    }
}
