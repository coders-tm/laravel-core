<?php

namespace Coderstm\Services;

use ArrayAccess;
use Coderstm\Traits\WithInput;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\InputBag;

class Resource implements Arrayable, ArrayAccess
{
    use WithInput;

    public $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = new InputBag($attributes);
    }

    public function merge(array $input)
    {
        $this->getInputSource()->add($input);

        return $this;
    }

    public function replace(array $input)
    {
        $this->getInputSource()->replace($input);

        return $this;
    }

    protected function getInputSource()
    {
        return $this->attributes;
    }

    public function toArray()
    {
        return $this->all();
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return Arr::has($this->all(), $offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->getInputSource()->set($offset, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->getInputSource()->remove($offset);
    }

    public function __isset($key)
    {
        return ! is_null($this->__get($key));
    }

    public function __get($key)
    {
        return Arr::get($this->all(), $key);
    }
}
