<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worker extends Model
{
    use HasFactory;

    public const RATE_TYPE_HOUR = 'hour';
    public const RATE_TYPE_DAY = 'day';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'bank_title',
        'account_number',
        'hourly_rate',
        'rate_type',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
        ];
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function defaultRateType(): string
    {
        return in_array($this->rate_type, [self::RATE_TYPE_HOUR, self::RATE_TYPE_DAY], true)
            ? $this->rate_type
            : self::RATE_TYPE_HOUR;
    }

    public function rateTypeLabel(): string
    {
        return $this->defaultRateType() === self::RATE_TYPE_DAY ? 'day' : 'hour';
    }

    public function formattedRate(): string
    {
        return '€'.number_format((float) $this->hourly_rate, 2).'/'.$this->rateTypeLabel();
    }

    public function calculateEntryAmount(TimeEntry $entry): float
    {
        return $entry->calculatedAmount((float) $this->hourly_rate, $this->defaultRateType());
    }
}
