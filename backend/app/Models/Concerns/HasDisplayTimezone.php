<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Carbon\CarbonInterface;

trait HasDisplayTimezone
{
    protected function asDateTime($value)
    {
        $date = parent::asDateTime($value);

        return $date instanceof CarbonInterface
            ? $date->setTimezone((string) config('app.display_timezone'))
            : $date;
    }

    public function fromDateTime($value)
    {
        if (empty($value)) {
            return $value;
        }

        return parent::asDateTime($value)
            ->setTimezone('UTC')
            ->format($this->getDateFormat());
    }
}
