<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_Matcher_Invocation;

class ExpectationsFactory
{
    /** @var InvocationFactory */
    private $invocationFactory;

    public function __construct(InvocationFactory $invocationFactory)
    {
        $this->invocationFactory = $invocationFactory;
    }

    public function createExpectations(array $expectations) : Expectations
    {
        if ($this->isAssociative($expectations)) {
            return $this->buildExpectationsMap($expectations);
        }

        return $this->buildExpectationsList($expectations);
    }

    protected function buildExpectation(
        string $method,
        array $params,
        $result,
        $throws,
        $invoked) : Expectation
    {
        $invocation = $this->invocationFactory->prepareInvocation($invoked);

        return $this->newExpectation($method, $params, $result, $throws, $invocation);
    }

    protected function newExpectation(
        string $method,
        array $params,
        $result,
        $throws,
        PHPUnit_Framework_MockObject_Matcher_Invocation $invocation) : Expectation
    {
        return new Expectation($method, $params, $result, $throws, $invocation);
    }

    protected function newExpectations(array $expectations) : Expectations
    {
        return new Expectations($expectations);
    }

    private function buildExpectationsMap(array $map) : Expectations
    {
        $expectations = [];

        foreach ($map as $method => $expects) {
            if (!\is_array($expects)) {
                $expects = ['invoked' => $expects];
            }
            $expects['method'] = $method;
            list($method, $params, $result, $throws, $invoked) = $this->normalizeExpectation($expects);
            $expectations[] = $this->buildExpectation($method, $params, $result, $throws, $invoked);
        }

        return $this->newExpectations($expectations);
    }

    private function buildExpectationsList(array $list) : Expectations
    {
        $expectations = [];

        $index = 0;
        foreach ($list as $expectation) {
            list($method, $params, $result, $throws, $invoked) = $this->normalizeExpectation((array)$expectation);
            $invoked = $invoked ?? TestCase::at($index++);
            $expectations[] = $this->buildExpectation($method, $params, $result, $throws, $invoked);
        }

        return $this->newExpectations($expectations);
    }

    private function normalizeExpectation(array $expects) : array
    {
        $method = $expects['method'] ?? null;
        $params = $expects['params'] ?? null;
        $result = $expects['result'] ?? null;
        $throws = $expects['throws'] ?? null;
        $invoked = $expects['invoked'] ?? null;

        unset(
            $expects['method'],
            $expects['params'],
            $expects['result'],
            $expects['throws'],
            $expects['invoked']
        );

        $method = (string)($method ?? array_shift($expects));
        if ($invoked === null && $expects === ['never']) {
            $invoked = array_shift($expects);
        }
        $params = (array)($params ?? array_shift($expects));
        $result = $result ?? array_shift($expects);
        $throws = $throws ?? array_shift($expects);
        $invoked = $invoked ?? array_shift($expects);

        if ($result !== null && $throws !== null) {
            throw new TestException("cannot expect both 'result' and 'throws'");
        }

        return [$method, $params, $result, $throws, $invoked];
    }

    private function isAssociative(array $array) : bool
    {
        return array_keys($array) !== range(0, \count($array) - 1);
    }
}
