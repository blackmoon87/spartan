@extends('layouts.clinic_main')

@section('content')
<div x-data="{ activeTab: 'dashboard', showPaymentModal: false, selectedInvoiceId: null, invoiceTotal: 0, invoicePending: 0 }">
    
    <!-- Toast/Flash Notification Alerts -->
    @if(!empty($success))
        <div class="alert alert-success">
            <span>✓ {{ $success }}</span>
        </div>
    @endif
    @if(!empty($error))
        <div class="alert alert-error">
            <span>⚠ {{ $error }}</span>
        </div>
    @endif

    <!-- 1. Stats Counter Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-title">Total Patients</div>
            <div class="stat-value text-teal">{{ $totalPatients }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Active Appointments</div>
            <div class="stat-value text-blue">{{ $upcomingCount }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Total Income</div>
            <div class="stat-value text-green">${{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Pending Balances</div>
            <div class="stat-value text-orange">${{ number_format($totalPending, 2) }}</div>
        </div>
    </div>

    <!-- 2. Dashboard Navigation Tabs -->
    <div class="tabs-container">
        <button class="tab-btn" :class="{ 'active': activeTab === 'dashboard' }" @click="activeTab = 'dashboard'">
            📋 Summary
        </button>
        <button class="tab-btn" :class="{ 'active': activeTab === 'patients' }" @click="activeTab = 'patients'">
            👥 Patients
        </button>
        <button class="tab-btn" :class="{ 'active': activeTab === 'appointments' }" @click="activeTab = 'appointments'">
            📅 Appointments
        </button>
        <button class="tab-btn" :class="{ 'active': activeTab === 'invoices' }" @click="activeTab = 'invoices'">
            💳 Invoices
        </button>
    </div>

    <!-- 3. Tab Contents -->

    <!-- TAB 1: Summary Dashboard -->
    <div x-show="activeTab === 'dashboard'" x-transition>
        <div class="grid-two-cols">
            <!-- Recent Appointments Card -->
            <div class="card">
                <div class="card-header">Upcoming Appointments</div>
                <div class="card-body">
                    @if(empty($appointments))
                        <p class="text-muted">No scheduled appointments.</p>
                    @else
                        <div class="appointment-list">
                            @foreach(array_slice($appointments, 0, 5) as $app)
                                <div class="list-item">
                                    <div>
                                        <div class="item-title">{{ htmlspecialchars($app['patient_name']) }}</div>
                                        <div class="item-subtitle">{{ $app['appointment_date'] }}</div>
                                    </div>
                                    <span class="status-tag status-{{ $app['status'] }}">{{ ucfirst($app['status']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Invoices Card -->
            <div class="card">
                <div class="card-header">Recent Invoices</div>
                <div class="card-body">
                    @if(empty($invoices))
                        <p class="text-muted">No invoices generated yet.</p>
                    @else
                        <div class="invoice-list">
                            @foreach(array_slice($invoices, 0, 5) as $inv)
                                <div class="list-item">
                                    <div>
                                        <div class="item-title">{{ htmlspecialchars($inv['patient_name']) }}</div>
                                        <div class="item-subtitle">Total: ${{ number_format((float)$inv['total_amount'], 2) }}</div>
                                    </div>
                                    <div>
                                        <span class="status-tag status-{{ $inv['status'] }}">{{ ucfirst($inv['status']) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: Patients -->
    <div x-show="activeTab === 'patients'" x-transition>
        <div class="grid-two-cols">
            <!-- Patients List & Search -->
            <div class="card">
                <div class="card-header flex-header">
                    <span>Patient List</span>
                    <input type="text" 
                           name="query" 
                           class="search-input" 
                           placeholder="Search patients..."
                           hx-post="/clinic/patients/search"
                           hx-trigger="keyup changed delay:200ms"
                           hx-target="#patient-select-list"
                           hx-include="[name=_csrf]">
                </div>
                <div class="card-body scrollable-body">
                    <div class="patient-grid">
                        @if(empty($patients))
                            <p class="text-muted">No patients registered.</p>
                        @else
                            @foreach($patients as $patient)
                                <div class="patient-card">
                                    <div class="patient-name">{{ htmlspecialchars($patient['name']) }}</div>
                                    <div class="patient-detail">📞 {{ htmlspecialchars($patient['phone']) }}</div>
                                    <div class="patient-detail">✉ {{ htmlspecialchars($patient['email']) }}</div>
                                    @if(!empty($patient['medical_history']))
                                        <div class="patient-note">⚠️ {{ htmlspecialchars($patient['medical_history']) }}</div>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <!-- Add Patient Form -->
            <div class="card">
                <div class="card-header">Register New Patient</div>
                <div class="card-body">
                    <form action="/clinic/patient" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Charlie" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 555-0199" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. charlie@mail.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Medical History / Allergies</label>
                            <textarea name="medical_history" class="form-control" rows="3" placeholder="e.g. Penicillin allergy, diabetic"></textarea>
                        </div>
                        <button type="submit" class="btn btn-teal">Add Patient</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: Appointments -->
    <div x-show="activeTab === 'appointments'" x-transition>
        <div class="grid-two-cols">
            <!-- Book Appointment Form -->
            <div class="card">
                <div class="card-header">Book Dental Appointment</div>
                <div class="card-body">
                    <form action="/clinic/appointment" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Select Patient</label>
                            <select name="patient_id" id="patient-select-list" class="form-control" required>
                                @if(empty($patients))
                                    <option value="">No patients registered</option>
                                @else
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient['id'] }}">{{ htmlspecialchars($patient['name']) }} ({{ htmlspecialchars($patient['phone']) }})</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Appointment Date & Time</label>
                            <input type="datetime-local" name="appointment_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Procedure / Treatment Cost ($)</label>
                            <input type="number" step="0.01" name="procedure_cost" class="form-control" placeholder="e.g. 150.00" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Treatment Notes / Symptoms</label>
                            <textarea name="treatment_notes" class="form-control" rows="2" placeholder="e.g. Root canal therapy, teeth cleaning"></textarea>
                        </div>
                        <button type="submit" class="btn btn-blue">Schedule Visit</button>
                    </form>
                </div>
            </div>

            <!-- Appointments List -->
            <div class="card">
                <div class="card-header">Schedule Calendar</div>
                <div class="card-body scrollable-body">
                    @if(empty($appointments))
                        <p class="text-muted">No appointments booked.</p>
                    @else
                        <div class="appointment-grid">
                            @foreach($appointments as $app)
                                <div class="appointment-card">
                                    <div class="flex-row">
                                        <div class="patient-name">{{ htmlspecialchars($app['patient_name']) }}</div>
                                        <span class="status-tag status-{{ $app['status'] }}">{{ ucfirst($app['status']) }}</span>
                                    </div>
                                    <div class="patient-detail">📅 {{ $app['appointment_date'] }}</div>
                                    @if(!empty($app['treatment_notes']))
                                        <div class="patient-note">📝 {{ htmlspecialchars($app['treatment_notes']) }}</div>
                                    @endif

                                    <!-- Status update quick action spoofed methods -->
                                    @if($app['status'] === 'scheduled')
                                        <div class="btn-group-row">
                                            <form action="/clinic/appointment/{{ $app['id'] }}" method="POST" class="inline-form">
                                                @csrf
                                                <input type="hidden" name="_method" value="PUT">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn-small btn-green">Complete</button>
                                            </form>
                                            <form action="/clinic/appointment/{{ $app['id'] }}" method="POST" class="inline-form">
                                                @csrf
                                                <input type="hidden" name="_method" value="PUT">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="btn-small btn-red">Cancel</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 4: Invoices -->
    <div x-show="activeTab === 'invoices'" x-transition>
        <div class="card">
            <div class="card-header">Billing & Invoices Registry</div>
            <div class="card-body scrollable-body">
                @if(empty($invoices))
                    <p class="text-muted">No bills generated.</p>
                @else
                    <table class="clinic-table">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Patient Name</th>
                                <th>Appointment Date</th>
                                <th>Total Cost</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $inv)
                                <tr>
                                    <td>#{{ $inv['id'] }}</td>
                                    <td>{{ htmlspecialchars($inv['patient_name']) }}</td>
                                    <td>{{ $inv['appointment_date'] }}</td>
                                    <td>${{ number_format((float)$inv['total_amount'], 2) }}</td>
                                    <td>${{ number_format((float)$inv['paid_amount'], 2) }}</td>
                                    <td>
                                        <span class="status-tag status-{{ $inv['status'] }}">{{ ucfirst($inv['status']) }}</span>
                                    </td>
                                    <td>
                                        @if($inv['status'] !== 'paid')
                                            <button class="btn-small btn-teal"
                                                    @click="selectedInvoiceId = {{ $inv['id'] }}; 
                                                            invoiceTotal = {{ $inv['total_amount'] }}; 
                                                            invoicePending = {{ $inv['total_amount'] - $inv['paid_amount'] }}; 
                                                            showPaymentModal = true;">
                                                Pay Bill
                                            </button>
                                        @else
                                            <span class="text-green">✓ Paid</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <!-- Alpine.js Payment Modal Dialog -->
    <div class="modal" x-show="showPaymentModal" x-transition style="display: none;">
        <div class="modal-content" @click.away="showPaymentModal = false">
            <div class="modal-header">Record Payment for Invoice #<span x-text="selectedInvoiceId"></span></div>
            <form :action="'/clinic/invoice/' + selectedInvoiceId + '/pay'" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="margin-bottom: 1rem;">
                        <span class="text-muted">Total Invoice Cost: </span><strong>$<span x-text="invoiceTotal.toFixed(2)"></span></strong>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <span class="text-muted">Remaining Balance: </span><strong class="text-orange">$<span x-text="invoicePending.toFixed(2)"></span></strong>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Amount ($)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" :max="invoicePending" :value="invoicePending" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showPaymentModal = false">Cancel</button>
                    <button type="submit" class="btn btn-teal">Record Payment</button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- CSS Styles tailoring the clinic view -->
<style>
    /* Stats Layout */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-title {
        color: var(--color-muted);
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
    }

    .text-teal { color: #22d3ee; }
    .text-blue { color: #60a5fa; }
    .text-green { color: #34d399; }
    .text-orange { color: #fb923c; }

    /* Tabs */
    .tabs-container {
        display: flex;
        gap: 0.5rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1px;
        margin-bottom: 2rem;
    }

    .tab-btn {
        background: none;
        border: none;
        color: var(--color-muted);
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: var(--transition-smooth);
    }

    .tab-btn:hover, .tab-btn.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    /* Grid Layouts */
    .grid-two-cols {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    @media (max-width: 768px) {
        .grid-two-cols {
            grid-template-columns: 1fr;
        }
    }

    .card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        display: flex;
        flex-direction: column;
    }

    .card-header {
        font-size: 1.2rem;
        font-weight: 700;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-body {
        padding: 1.5rem;
    }

    .scrollable-body {
        max-height: 480px;
        overflow-y: auto;
    }

    /* List styling */
    .list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border-color);
        background-color: rgba(15, 23, 42, 0.3);
        border-radius: var(--radius-md);
        margin-bottom: 0.75rem;
    }

    .item-title {
        font-weight: 600;
        color: var(--color-text);
    }

    .item-subtitle {
        font-size: 0.85rem;
        color: var(--color-muted);
    }

    /* Status Tags */
    .status-tag {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.25rem 0.6rem;
        border-radius: 4px;
    }

    .status-scheduled { background-color: rgba(96, 165, 250, 0.15); color: #60a5fa; }
    .status-completed { background-color: rgba(52, 211, 153, 0.15); color: #34d399; }
    .status-cancelled { background-color: rgba(248, 113, 113, 0.15); color: #f87171; }
    .status-unpaid { background-color: rgba(248, 113, 113, 0.15); color: #f87171; }
    .status-partial { background-color: rgba(251, 146, 60, 0.15); color: #fb923c; }
    .status-paid { background-color: rgba(52, 211, 153, 0.15); color: #34d399; }

    /* Forms */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--color-muted);
        margin-bottom: 0.5rem;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        background-color: rgba(15, 23, 42, 0.6);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        color: var(--color-text);
        font-family: inherit;
        font-size: 1rem;
        transition: var(--transition-smooth);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }

    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
        background-position: right 0.75rem center;
        background-repeat: no-repeat;
        background-size: 1.25rem;
    }

    .btn {
        width: 100%;
        padding: 0.85rem;
        border: none;
        border-radius: var(--radius-md);
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition-smooth);
        font-family: inherit;
    }

    .btn-teal { background-color: var(--primary); color: #0b0f19; }
    .btn-teal:hover { background-color: var(--primary-hover); }
    .btn-blue { background-color: #3b82f6; color: #ffffff; }
    .btn-blue:hover { background-color: #2563eb; }
    .btn-secondary { background-color: #374151; color: var(--color-text); }
    .btn-secondary:hover { background-color: #4b5563; }

    .btn-small {
        padding: 0.35rem 0.75rem;
        border: none;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: var(--transition-smooth);
    }

    .btn-small.btn-teal { background-color: var(--primary); color: #0b0f19; }
    .btn-small.btn-green { background-color: var(--success); color: #0b0f19; }
    .btn-small.btn-red { background-color: var(--danger); color: #ffffff; }

    .btn-group-row {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }

    .inline-form {
        display: inline;
    }

    /* Patients & Search */
    .search-input {
        background-color: rgba(15, 23, 42, 0.6);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        padding: 0.5rem 1rem;
        color: var(--color-text);
        font-size: 0.875rem;
        font-family: inherit;
        outline: none;
        transition: var(--transition-smooth);
        width: 220px;
    }

    .search-input:focus {
        border-color: var(--primary);
    }

    .patient-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .patient-card {
        padding: 1rem;
        background-color: rgba(15, 23, 42, 0.3);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
    }

    .patient-name {
        font-weight: 700;
        font-size: 1.05rem;
        color: var(--color-text);
    }

    .patient-detail {
        font-size: 0.875rem;
        color: var(--color-muted);
    }

    .patient-note {
        font-size: 0.875rem;
        color: #f87171;
        margin-top: 0.5rem;
        background-color: rgba(239, 68, 68, 0.05);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        border: 1px dashed rgba(239, 68, 68, 0.2);
    }

    /* Appointments */
    .appointment-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .appointment-card {
        padding: 1rem;
        background-color: rgba(15, 23, 42, 0.3);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
    }

    .flex-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Invoices Table */
    .clinic-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .clinic-table th, .clinic-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .clinic-table th {
        color: var(--color-muted);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.05em;
    }

    .clinic-table tr:hover {
        background-color: rgba(255, 255, 255, 0.02);
    }

    /* Modal dialog */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.6);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        backdrop-filter: blur(4px);
    }

    .modal-content {
        background-color: #111827;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        width: 100%;
        max-width: 450px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);
    }

    .modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 1.2rem;
        font-weight: 700;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }
</style>
@endsection
