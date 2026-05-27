<?php

declare(strict_types=1);

namespace Tests\Sample\Models;

use App\Core\Model;
use App\Core\RelationQuery;

class Patient extends Model
{
    protected string $table = 'clinic_patients';
    protected bool $timestamps = true;

    /**
     * Get appointments for the patient
     */
    public function appointments(): RelationQuery
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    /**
     * Get invoices for the patient
     */
    public function invoices(): RelationQuery
    {
        return $this->hasMany(Invoice::class, 'patient_id');
    }
}
