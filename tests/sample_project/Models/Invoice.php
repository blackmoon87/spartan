<?php

declare(strict_types=1);

namespace Tests\Sample\Models;

use App\Core\Model;
use App\Core\RelationQuery;

class Invoice extends Model
{
    protected string $table = 'clinic_invoices';
    protected bool $timestamps = true;

    /**
     * Get patient for the invoice
     */
    public function patient(): RelationQuery
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get appointment for the invoice
     */
    public function appointment(): RelationQuery
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
