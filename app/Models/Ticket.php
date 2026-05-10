<?php

namespace App\Models;

use App\Support\Enums\TicketPlatform;
use App\Support\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Ticket extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => TicketStatus::class,
        'platform' => TicketPlatform::class,
        'occurrence_time' => 'datetime',
        'assigned_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_valid' => 'boolean',
    ];

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TicketNote::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }
}
