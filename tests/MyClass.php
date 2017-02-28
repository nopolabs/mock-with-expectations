<?php
namespace Nopolabs\Test\Tests;

class MyClass
{
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