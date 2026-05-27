<?php

declare(strict_types=1);

namespace Tests\Sample\Models;

use App\Core\Model;
use App\Core\RelationQuery;

class Appointment extends Model
{
    protected string $table = 'clinic_appointments';
    protected bool $timestamps = true;

    /**
     * Get patient for the appointment
     */
    public function patient(): RelationQuery
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
