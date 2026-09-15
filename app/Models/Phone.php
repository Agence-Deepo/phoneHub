<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phone extends Model
{
    protected $fillable = [
        'geelark_id',
        'name',
        'serial_no',
        'status',
        'country',
        'group_name',
        'tags',
        'proxy',
        'mobile_type',
        'remote_url',
        'screenshot_url',
        'screenshot_task_id',
        'remark',
        'equipment_info',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'equipment_info' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function apps(): HasMany
    {
        return $this->hasMany(PhoneApp::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class);
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'online' => 'En ligne',
            'starting' => 'Démarrage…',
            'offline' => 'Arrêté',
            'error' => 'Erreur',
            default => ucfirst($this->status),
        };
    }

    public static function mapApiStatus(?int $status): string
    {
        return match ($status) {
            0 => 'online',
            1 => 'starting',
            2 => 'offline',
            default => 'offline',
        };
    }
}
