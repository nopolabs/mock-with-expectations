<?php
namespace Nopolabs\Test\Tests;

use Exception;

class MyClass
{
    public function fun()
    {
        throw new Exception('fun() was not mocked!');
    }

    public function myFunction($value)
    {
        $a = $this->a($value);
        $b = $this->b($a);
        return $b;
    }

    protected function a($a)
    {
        return "a($a)";
    }

    protected function b($b)
    {
        return "b($b)";
    }

    protected function c($c)
    {
        return "c($c)";
    }
}