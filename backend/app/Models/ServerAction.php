<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActionType;
use App\Enums\Weekday;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerAction extends Model
{
    protected $fillable = [
        'server_id',
        'weekday',
        'time',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => Weekday::class,
            'type' => ActionType::class,
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
