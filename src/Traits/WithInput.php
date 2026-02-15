<?php

namespace Coderstm\Traits;

use Illuminate\Support\Arr;
use stdClass;
use Symfony\Component\VarDumper\VarDumper;

trait WithInput
{
    public function exists($key)
    {
        return $this->has($key);
    }

    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        $input = $this->all();
        foreach ($keys as $value) {
            if (! Arr::has($input, $value)) {
                return false;
            }
        }

        return true;
    }

    public function hasAny($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $input = $this->all();

        return Arr::hasAny($input, $keys);
    }

    public function whenHas($key, callable $callback, ?callable $default = null)
    {
        if ($this->has($key)) {
            return $callback(data_get($this->all(), $key)) ?: $this;
        }
        if ($default) {
            return $default();
        }

        return $this;
    }

    public function filled($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $value) {
            if ($this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    public function isNotFilled($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $value) {
            if (! $this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    public function anyFilled($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        foreach ($keys as $key) {
            if ($this->filled($key)) {
                return true;
            }
        }

        return false;
    }

    public function whenFilled($key, callable $callback, ?callable $default = null)
    {
        if ($this->filled($key)) {
            return $callback(data_get($this->all(), $key)) ?: $this;
        }
        if ($default) {
            return $default();
        }

        return $this;
    }

    public function missing($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        return ! $this->has($keys);
    }

    protected function isEmptyString($key)
    {
        $value = $this->input($key);

        return ! is_bool($value) && ! is_array($value) && trim((string) $value) === '';
    }

    public function keys()
    {
        return array_merge(array_keys($this->input()), $this->files->keys());
    }

    public function all($keys = null)
    {
        $input = $this->input();
        if (! $keys) {
            return $input;
        }
        $results = [];
        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            Arr::set($results, $key, Arr::get($input, $key));
        }

        return $results;
    }

    public function input($key = null, $default = null)
    {
        return data_get($this->getInputSource()->all() + $this->attributes->all(), $key, $default);
    }

    public function boolean($key = null, $default = false)
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function only($keys)
    {
        $results = [];
        $input = $this->all();
        $placeholder = new stdClass;
        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            $value = data_get($input, $key, $placeholder);
            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }

        return $results;
    }

    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $results = $this->all();
        Arr::forget($results, $keys);

        return $results;
    }

    public function dd(...$keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        call_user_func_array([$this, 'dump'], $keys);
        exit(1);
    }

    public function dump($keys = [])
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        VarDumper::dump(count($keys) > 0 ? $this->only($keys) : $this->all());

        return $this;
    }
}
