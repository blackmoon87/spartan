<?php

declare(strict_types=1);

namespace Tests\Sample\Controllers;

use App\Core\Controller;
use App\Core\Cache;
use Tests\Sample\Models\Patient;
use Tests\Sample\Models\Appointment;
use Tests\Sample\Models\Invoice;

class ClinicController extends Controller
{
    /**
     * Display the clinic dashboard
     */
    public function index(): string
    {
        $patientModel = new Patient();
        $appointmentModel = new Appointment();
        $invoiceModel = new Invoice();

        // 1. Fetch Stats
        $totalPatients = $patientModel->table()->count();

        // Sum revenue and pending bills
        $invoicesRaw = $invoiceModel->table()->get();
        $totalRevenue = 0.0;
        $totalPending = 0.0;
        foreach ($invoicesRaw as $inv) {
            $totalRevenue += (float)$inv['paid_amount'];
            $totalPending += ((float)$inv['total_amount'] - (float)$inv['paid_amount']);
        }

        $upcomingCount = $appointmentModel->table()
            ->where('status', 'scheduled')
            ->count();

        // 2. Fetch Patients (with eager loading fallback or plain join)
        $patients = $patientModel->table()->orderBy('id', 'DESC')->get();

        // 3. Fetch Appointments joined with patient details
        $appointments = $appointmentModel->table()
            ->select('clinic_appointments.*', 'clinic_patients.name as patient_name', 'clinic_patients.phone as patient_phone')
            ->join('clinic_patients', 'clinic_appointments.patient_id', 'clinic_patients.id')
            ->orderBy('clinic_appointments.appointment_date', 'ASC')
            ->get();

        // 4. Fetch Invoices joined with patient and appointment details
        $invoices = $invoiceModel->table()
            ->select('clinic_invoices.*', 'clinic_patients.name as patient_name', 'clinic_appointments.appointment_date')
            ->join('clinic_patients', 'clinic_invoices.patient_id', 'clinic_patients.id')
            ->join('clinic_appointments', 'clinic_invoices.appointment_id', 'clinic_appointments.id')
            ->orderBy('clinic_invoices.id', 'DESC')
            ->get();

        return $this->render('clinic/dashboard', [
            'totalPatients' => $totalPatients,
            'totalRevenue' => $totalRevenue,
            'totalPending' => $totalPending,
            'upcomingCount' => $upcomingCount,
            'patients' => $patients,
            'appointments' => $appointments,
            'invoices' => $invoices,
            'errors' => $this->session->getFlash('validation_errors', []),
            'old' => $this->session->getFlash('old_input', []),
            'success' => $this->session->getFlash('success_message', ''),
            'error' => $this->session->getFlash('error_message', '')
        ]);
    }

    /**
     * Add a new patient
     */
    public function storePatient(): void
    {
        $data = $this->request->getBody();

        $v = $this->validate($data, [
            'name'  => 'required|string|min:2',
            'phone' => 'required|string|min:7',
            'email' => 'required|email|unique:clinic_patients,email',
            'medical_history' => 'nullable|string'
        ]);

        if ($v->fails()) {
            $this->session->setFlash('validation_errors', $v->errors());
            $this->session->setFlash('old_input', $data);
            $this->session->setFlash('error_message', 'Patient details validation failed!');
            $this->redirect('/clinic');
            return;
        }

        $patientModel = new Patient();
        $patientId = $patientModel->create([
            'name'  => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'medical_history' => $data['medical_history'] ?? ''
        ]);

        $this->session->setFlash('success_message', "Patient #{$patientId} added successfully!");
        $this->redirect('/clinic');
    }

    /**
     * Book a new appointment and auto-generate invoice
     */
    public function storeAppointment(): void
    {
        $data = $this->request->getBody();

        $v = $this->validate($data, [
            'patient_id' => 'required|integer',
            'appointment_date' => 'required|string',
            'treatment_notes' => 'nullable|string',
            'procedure_cost' => 'required|regex:/^\d+(\.\d{1,2})?$/'
        ]);

        if ($v->fails()) {
            $this->session->setFlash('validation_errors', $v->errors());
            $this->session->setFlash('old_input', $data);
            $this->session->setFlash('error_message', 'Appointment details validation failed!');
            $this->redirect('/clinic');
            return;
        }

        $appointmentModel = new Appointment();
        $invoiceModel = new Invoice();

        // 1. Create appointment
        $appointmentId = $appointmentModel->create([
            'patient_id' => (int)$data['patient_id'],
            'appointment_date' => $data['appointment_date'],
            'status' => 'scheduled',
            'treatment_notes' => $data['treatment_notes'] ?? ''
        ]);

        // 2. Create corresponding Invoice
        $invoiceModel->create([
            'patient_id' => (int)$data['patient_id'],
            'appointment_id' => (int)$appointmentId,
            'total_amount' => (float)$data['procedure_cost'],
            'paid_amount' => 0.0,
            'status' => 'unpaid'
        ]);

        $this->session->setFlash('success_message', "Appointment scheduled & invoice created!");
        $this->redirect('/clinic');
    }

    /**
     * Update appointment status
     */
    public function updateAppointment(int $id): void
    {
        $data = $this->request->getBody();

        $v = $this->validate($data, [
            'status' => 'required|in:scheduled,completed,cancelled'
        ]);

        if ($v->fails()) {
            $this->session->setFlash('error_message', 'Invalid appointment status value!');
            $this->redirect('/clinic');
            return;
        }

        $appointmentModel = new Appointment();
        $appointmentModel->table()->where('id', $id)->update([
            'status' => $data['status']
        ]);

        $this->session->setFlash('success_message', "Appointment #{$id} status updated to {$data['status']}.");
        $this->redirect('/clinic');
    }

    /**
     * Pay an invoice (partial or full)
     */
    public function payInvoice(int $id): void
    {
        $data = $this->request->getBody();

        $v = $this->validate($data, [
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/'
        ]);

        if ($v->fails()) {
            $this->session->setFlash('error_message', 'Invalid payment amount specified!');
            $this->redirect('/clinic');
            return;
        }

        $invoiceModel = new Invoice();
        $invoice = $invoiceModel->find($id);

        if (!$invoice) {
            $this->session->setFlash('error_message', 'Invoice not found!');
            $this->redirect('/clinic');
            return;
        }

        $payment = (float)$data['amount'];
        $newPaidAmount = (float)$invoice['paid_amount'] + $payment;
        $totalAmount = (float)$invoice['total_amount'];

        if ($newPaidAmount > $totalAmount) {
            $newPaidAmount = $totalAmount;
        }

        $status = 'unpaid';
        if ($newPaidAmount >= $totalAmount) {
            $status = 'paid';
        } elseif ($newPaidAmount > 0) {
            $status = 'partial';
        }

        $invoiceModel->table()->where('id', $id)->update([
            'paid_amount' => $newPaidAmount,
            'status' => $status
        ]);

        $this->session->setFlash('success_message', "Payment of $" . number_format($payment, 2) . " recorded successfully!");
        $this->redirect('/clinic');
    }

    /**
     * Live HTMX patient search API
     */
    public function searchPatients(): string
    {
        $query = $this->request->post('query', '');
        $patientModel = new Patient();

        if (trim($query) === '') {
            $patients = $patientModel->table()->orderBy('id', 'DESC')->get();
        } else {
            $patients = $patientModel->table()
                ->where('name', '%' . $query . '%', 'LIKE')
                ->orWhere('phone', '%' . $query . '%', 'LIKE')
                ->orWhere('email', '%' . $query . '%', 'LIKE')
                ->get();
        }

        // Return a partial list of patient options for select list or grid view
        $html = '';
        foreach ($patients as $p) {
            $html .= sprintf(
                '<option value="%d">%s (%s)</option>',
                $p['id'],
                htmlspecialchars($p['name']),
                htmlspecialchars($p['phone'])
            );
        }

        if (empty($html)) {
            $html = '<option value="">No patients found</option>';
        }

        return $html;
    }
}
