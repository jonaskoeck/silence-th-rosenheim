<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasDisplayTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasDisplayTimezone, HasFactory;

    protected $fillable = [
        'name',
        'open_stack_project_id',
        'app_credential_id',
        'app_credential_secret',
        'last_inventory_run_id',
    ];

    protected $hidden = [
        'app_credential_id',
        'app_credential_secret',
    ];

    protected function casts(): array
    {
        return [
            'app_credential_id' => 'encrypted',
            'app_credential_secret' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Project $project) {
            if (empty($project->name)) {
                $project->updateQuietly(['name' => $project->open_stack_project_id]);
            }
        });
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function lastInventoryRun(): BelongsTo
    {
        return $this->belongsTo(InventoryRun::class, 'last_inventory_run_id');
    }
}
