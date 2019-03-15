<?php
declare(strict_types=1);

namespace Nopolabs\Test;

use PHPUnit_Framework_MockObject_MockObject;

class Expectations
{
    /** @var array */
    private $expectations;

    public function __construct(array $expectations)
    {
        $this->expectations = $expectations;
    }

    public function getExpectedMethods() : array
    {
        return array_unique(array_map(
            function(Expectation $expectation) {
                return $expectation->getMethod();
            }, $this->expectations
        ));
    }

    public function set(PHPUnit_Framework_MockObject_MockObject $mock) : void
    {
        array_walk(
            $this->expectations,
            function(Expectation $expectation) use ($mock) {
                $expectation->set($mock);
            }
        );
    }
}
