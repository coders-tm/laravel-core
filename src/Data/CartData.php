<?php

namespace Coderstm\Data;

use ArrayAccess;
use JsonSerializable;

class CartData implements ArrayAccess, JsonSerializable
{
    public int $count = 0;

    public int $uniqueItemCount = 0;

    public mixed $items = [];

    public float $subtotal = 0.0;

    public string $formattedSubtotal = '$0.00';

    public bool $isEmpty = true;

    public string $currency = 'USD';

    public string $currencySymbol = '$';

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if ($key == 'items') {
                    $value = fluent_data($value);
                }
                $this->{$key} = $value;
            }
        }
    }

    public function toArray(): array
    {
        return ['count' => $this->count, 'uniqueItemCount' => $this->uniqueItemCount, 'items' => $this->items, 'subtotal' => $this->subtotal, 'formattedSubtotal' => $this->formattedSubtotal, 'isEmpty' => $this->isEmpty, 'currency' => $this->currency, 'currencySymbol' => $this->currencySymbol];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->{$offset} ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (property_exists($this, $offset)) {
            $this->{$offset} = $value;
        }
    }

    public function offsetUnset($offset): void {}

    public function __get($name)
    {
        return $this->{$name} ?? null;
    }

    public function __isset($name): bool
    {
        return property_exists($this, $name);
    }
}
