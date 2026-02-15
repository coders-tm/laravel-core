<?php

namespace Coderstm\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use IteratorAggregate;
use JsonSerializable;
use Stringable;
use Traversable;

class FluentData implements ArrayAccess, Countable, IteratorAggregate, Jsonable, JsonSerializable, Stringable
{
    protected mixed $data;

    public function __construct(mixed $data = [])
    {
        if ($data instanceof Collection) {
            $this->data = $data->all();
        } elseif ($data instanceof self) {
            $this->data = $data->toArray();
        } elseif ($data instanceof Arrayable) {
            $this->data = $data->toArray();
        } elseif (is_object($data)) {
            $this->data = (array) $data;
        } else {
            $this->data = $data;
        }
    }

    public function count(): int
    {
        if (is_null($this->data)) {
            return 0;
        }

        return count((array) $this->data);
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    public function getIterator(): Traversable
    {
        if (is_array($this->data) || is_object($this->data)) {
            return new ArrayIterator($this->mapItems($this->data));
        }

        return new ArrayIterator([]);
    }

    protected function mapItems(mixed $data): array
    {
        $results = [];
        foreach ((array) $data as $key => $val) {
            $results[$key] = is_array($val) || is_object($val) ? new self($val) : $val;
        }

        return $results;
    }

    public function __get(string $key): mixed
    {
        return $this->offsetGet($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->offsetSet($key, $value);
    }

    public function __isset(string $key): bool
    {
        return $this->offsetExists($key);
    }

    public function __unset(string $key): void
    {
        $this->offsetUnset($key);
    }

    public function offsetExists(mixed $key): bool
    {
        if (is_array($this->data)) {
            return array_key_exists($key, $this->data);
        }

        return false;
    }

    public function offsetGet(mixed $key): mixed
    {
        $val = null;
        if (is_array($this->data)) {
            $val = $this->data[$key] ?? null;
        }
        if (is_array($val) || is_object($val)) {
            return new self($val);
        }

        return $val;
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        if (! is_array($this->data)) {
            $this->data = [];
        }
        if (is_null($key)) {
            $this->data[] = $value;
        } else {
            $this->data[$key] = $value;
        }
    }

    public function offsetUnset(mixed $key): void
    {
        if (is_array($this->data)) {
            unset($this->data[$key]);
        }
    }

    public function toArray(): array
    {
        if (is_null($this->data)) {
            return [];
        }

        return (array) $this->data;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }

    public function toInt(): int
    {
        if (is_scalar($this->data)) {
            return (int) $this->data;
        }

        return 0;
    }

    public function toFloat(): float
    {
        if (is_scalar($this->data)) {
            return (float) $this->data;
        }

        return 0.0;
    }

    public function toBool(): bool
    {
        return ! empty($this->data);
    }

    public function __toString(): string
    {
        if (is_scalar($this->data)) {
            return (string) $this->data;
        }
        if (is_null($this->data)) {
            return '';
        }

        return $this->toJson();
    }
}
