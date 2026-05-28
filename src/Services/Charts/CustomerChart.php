<?php

namespace Coderstm\Services\Charts;

use Coderstm\Coderstm;
use Illuminate\Support\Facades\DB;

class CustomerChart extends AbstractChart
{
    public function get(): array
    {
        $userModel = Coderstm::$userModel;
        $dateFormat = $this->getDateFormatExpression('created_at');
        $customerData = $userModel::select(DB::raw("{$dateFormat} as label"), DB::raw('COUNT(*) as total'))->whereBetween('created_at', [$this->startDate, $this->endDate])->groupBy('label')->orderBy(DB::raw('MIN(created_at)'), 'ASC')->get()->mapWithKeys(fn ($item) => [$this->formatLabel($item->label) => $item->total]);
        $labels = $this->getMonthLabels();
        $formattedData = [];
        foreach ($labels as $label) {
            $formattedData[$label] = $customerData[$label] ?? 0;
        }

        return $formattedData;
    }
}
