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
}
