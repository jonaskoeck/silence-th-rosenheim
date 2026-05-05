<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActionType;
use App\Enums\Weekday;
use App\Models\Concerns\HasDisplayTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerAction extends Model
{
    use HasDisplayTimezone;

    protected $fillable = [
        'server_id',
        'weekday',
        'time',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'type' => ActionType::class,
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return array<int, Weekday>
     */
    public function weekdays(): array
    {
        return Weekday::unpack((int) $this->weekday);
    }
}
