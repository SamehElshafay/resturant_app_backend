<?php

namespace App\Models\CommercialPlaceModels;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $table = 'appointments';

    protected $fillable = [
        'open_time',
        'close_time',
        'day_name',
        'commercial_place',
    ];

    protected $casts = [
        'open_time'  => 'datetime:H:i:s',
        'close_time' => 'datetime:H:i:s',
    ];

    public function commercialPlace(){
        return $this->belongsTo(CommercialPlace::class, 'commercial_place');
    }

    protected $appends = ['is_closed'];

    public function getIsClosedAttribute(): bool {
        $now = Carbon::now()->format('H:i:s');

        return ! (
            $now >= $this->open_time &&
            $now <= $this->close_time
            );
    }
      
}
