<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServerLabel;
use App\Models\Concerns\HasDisplayTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    use HasDisplayTimezone, HasFactory;

    protected $fillable = [
        'project_id',
        'open_stack_server_id',
        'name',
        'label',
        'status',
        'schedule_active',
        'schedule_name',
        'discovered_by_run_id',
    ];

    protected function casts(): array
    {
        return [
            'label' => ServerLabel::class,
            'schedule_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ServerAction::class);
    }

    public function discoveredByRun(): BelongsTo
    {
        return $this->belongsTo(InventoryRun::class, 'discovered_by_run_id');
    }
}
