<?php

namespace App\Models;

use Database\Factories\CareerEventTopicFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CareerEventTopic extends Model
{
    /** @use HasFactory<CareerEventTopicFactory> */
    use HasFactory;

    protected $fillable = ['slug', 'label'];

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(CareerEvent::class, 'career_event_topic');
    }
}
