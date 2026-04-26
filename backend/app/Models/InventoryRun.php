<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryRun extends Model
{
    protected $fillable = [
        'start_time',
        'end_time',
        'triggered_automatically',
        'had_errors',
        'found_new_servers',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'triggered_automatically' => 'boolean',
            'had_errors' => 'boolean',
            'found_new_servers' => 'boolean',
        ];
    }

    public function projectsAsLastRun(): HasMany
    {
        return $this->hasMany(Project::class, 'last_inventory_run_id');
    }

    public function discoveredServers(): HasMany
    {
        return $this->hasMany(Server::class, 'discovered_by_run_id');
    }
}
