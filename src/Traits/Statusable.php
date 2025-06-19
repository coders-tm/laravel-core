<?php

namespace Coderstm\Traits;

trait Statusable
{
    public function attachStatus(string $status)
    {
        return $this->status()->firstOrCreate([
            'label' => $status,
        ]);
    }

    public function detachStatus(array $status)
    {
        return $this->status()->whereIn('label', $status)->delete();
    }

    public function updateStatus($collection, $status)
    {
        // return if status doesn't contain to collection
        if (!$collection->contains($status)) {
            return false;
        }

        // remove current status if available
        $this->detachStatus($collection->diff([$status])->all());

        // attach the status
        return $this->attachStatus($status);
    }

    public function hasStatus(string $status)
    {
        return $this->status->contains('label', $status);
    }

    public function hasAnyStatus(array $status)
    {
        return $this->status->contains(function ($item, $key) use ($status) {
            return in_array($item->label, $status);
        });
    }

    public function scopeWhereHasStatus($query, $status)
    {
        return $query->whereHas('status', function ($q) use ($status) {
            $q->where('label', $status);
        });
    }

    public function scopeWhereInStatus($query, array $status = [])
    {
        return $query->whereHas('status', function ($q) use ($status) {
            $q->whereIn('label', $status);
        });
    }
}
