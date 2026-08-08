<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivacySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'read_message',
        'search_engine',
        'home_city',
        'trip_type',
        'length_stay',
        'booked_services',
        'ai_features',
    ];

    protected $casts = [
        'read_message' => 'boolean',
        'search_engine' => 'boolean',
        'home_city' => 'boolean',
        'trip_type' => 'boolean',
        'length_stay' => 'boolean',
        'booked_services' => 'boolean',
        'ai_features' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
