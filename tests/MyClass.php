<?php
namespace Nopolabs\Test;

use Exception;

class MyClass
{
    public function fun()
    {
        throw new Exception('fun() was not mocked!');
    }

    public function myFunction($value, int $count = 1)
    {
        while ($count-- > 0) {
            $value = $this->a($value);
            $value = $this->b($value);
        }

        return $value;
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

    protected function d($d)
    {
        return "d($d)";
    }

    protected function e($e)
    {
        throw new Exception($e);
    }
}
