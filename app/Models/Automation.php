<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Automation extends Model
{
    protected $fillable = [
        'phone_id',
        'type',
        'status',
        'geelark_task_id',
        'config',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }

    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'running' => 'En cours',
            'success' => 'Réussi',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            default => ucfirst($this->status),
        };
    }
}
