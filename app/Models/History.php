<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class History extends Model
{
    protected $fillable = [
        'file_name',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Find a history record by file name.
     */
    public static function findByFileName(string $fileName): ?self
    {
        return static::where('file_name', $fileName)->first();
    }

    /**
     * Update status by file name.
     */
    public static function updateStatusByFileName(string $fileName, string $status): bool
    {
        $history = static::findByFileName($fileName);
        if ($history) {
            $history->status = $status;
            return $history->save();
        }
        return false;
    }

    /**
     * Create or update a history record by file name.
     */
    public static function createOrUpdateByFileName(string $fileName, string $status = 'pending'): self
    {
        $history = static::findByFileName($fileName);
        
        if ($history) {
            $history->status = $status;
            $history->save();
        } else {
            $history = static::create([
                'file_name' => $fileName,
                'status' => $status,
            ]);
        }
        
        return $history;
    }

    /**
     * Get formatted date attribute.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M j, Y g:i A');
    }

    /**
     * Get time ago attribute.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Scope to get recent files first.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
