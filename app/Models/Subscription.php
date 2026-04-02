<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    // The name of the table created in the migration
    protected $table = 'subscription_apps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'app_name',
        'status',
        'starts_at',
        'ends_at',
        'plan_name',
        'price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    // --- Relationships ---

    /**
     * Get the client that owns the subscription.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // --- Helper Scopes (Optional but Recommended) ---

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('ends_at', '>', now());
    }

    /**
     * Scope a query to filter by a specific application name.
     */
    public function scopeForApp($query, string $appName)
    {
        return $query->where('app_name', $appName);
    }
}
