<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'project_id',
        'work_date',
        'hours',
        'hourly_rate_override',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'immutable_date',
            'hourly_rate_override' => 'decimal:2',
        ];
    }

    protected function workDate(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => CarbonImmutable::parse($value)->toDateString(),
        );
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function effectiveHourlyRate(float $defaultRate): float
    {
        return $this->hourly_rate_override !== null
            ? (float) $this->hourly_rate_override
            : $defaultRate;
    }
}
