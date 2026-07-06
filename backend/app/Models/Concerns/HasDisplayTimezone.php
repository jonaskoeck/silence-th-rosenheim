<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

trait HasDisplayTimezone
{
    protected function asDateTime($value)
    {
        if (is_string($value) && ! is_numeric($value) && ! $this->isStandardDateFormat($value)) {
            $value = Carbon::createFromFormat($this->getDateFormat(), $value, (string) config('app.timezone'));
        }

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
            ->setTimezone((string) config('app.timezone'))
            ->format($this->getDateFormat());
    }
}
