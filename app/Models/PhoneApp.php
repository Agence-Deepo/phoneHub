<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneApp extends Model
{
    protected $fillable = [
        'phone_id',
        'app_name',
        'package_name',
        'app_version_id',
        'status',
    ];

    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }
}
