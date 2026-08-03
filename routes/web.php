<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentRecordController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormRecordController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\OnlineBookingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/clear', function () {
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    return "Cleared!";
});
Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.store');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');
Route::get('online-booking', [OnlineBookingController::class, 'index'])->name('online-booking.index');
Route::get('online-booking/slots', [OnlineBookingController::class, 'slots'])->name('online-booking.slots');
Route::post('online-booking', [OnlineBookingController::class, 'store'])->name('online-booking.store');
Route::get('online-booking/confirmation/{appointment}', [OnlineBookingController::class, 'confirmation'])->name('online-booking.confirmation');

Route::middleware(['auth:staff', 'active.staff'])->group(function () {
Route::get('/',[CalendarController::class,'dashboard'])->name('dashboard');
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
Route::get('/calendar/events', [CalendarController::class, 'getEvents'])->name('calendar.events');
Route::get('/calendar/staff-schedules', [CalendarController::class, 'getStaffSchedules'])->name('calendar.staffSchedules');
Route::get('/calendar/clients/search', [CalendarController::class, 'searchClients'])->name('calendar.clients.search');
Route::post('/calendar/appointments', [CalendarController::class, 'storeAppointment'])->name('calendar.store');
Route::get('/calendar/appointments/{id}', [CalendarController::class, 'getAppointment'])->name('calendar.show');
Route::put('/calendar/appointments/{id}', [CalendarController::class, 'updateAppointment'])->name('calendar.update');
Route::post('/calendar/appointments/{id}/assign-client', [CalendarController::class, 'assignClient'])->name('calendar.assignClient');
Route::post('/calendar/quick-client', [CalendarController::class, 'quickCreateClient'])->name('calendar.quickClient');

// Schedule API endpoints
Route::post('/schedule-api/create', [ScheduleController::class, 'storeApi'])->name('schedule.storeApi');
Route::put('/schedule-api/{id}', [ScheduleController::class, 'updateApi'])->name('schedule.updateApi');

Route::middleware(['role:admin,business_owner'])->group(function () {
    // Payroll API endpoints
    Route::post('/payroll-api/generate', [PayrollController::class, 'generatePayroll'])->name('payroll.generate');
    Route::get('/payroll-api/report', [PayrollController::class, 'report'])->name('payroll.report');

    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('staff/{staff}', [StaffController::class, 'show'])->name('staff.show');
    Route::get('staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
});

Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

Route::get('services', [ServiceController::class, 'index'])->name('services.index');
Route::get('services/create', [ServiceController::class, 'create'])->name('services.create');
Route::post('services', [ServiceController::class, 'store'])->name('services.store');
Route::get('services/{service}', [ServiceController::class, 'show'])->name('services.show');
Route::get('services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
Route::put('services/{service}', [ServiceController::class, 'update'])->name('services.update');
Route::delete('services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
Route::get('packages/create', [PackageController::class, 'create'])->name('packages.create');
Route::post('packages', [PackageController::class, 'store'])->name('packages.store');
Route::get('packages/{package}', [PackageController::class, 'show'])->name('packages.show');
Route::get('packages/{package}/edit', [PackageController::class, 'edit'])->name('packages.edit');
Route::put('packages/{package}', [PackageController::class, 'update'])->name('packages.update');
Route::delete('packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');
Route::get('schedule/create', [ScheduleController::class, 'create'])->name('schedule.create');
Route::post('schedule', [ScheduleController::class, 'store'])->name('schedule.store');
Route::get('schedule/{schedule}', [ScheduleController::class, 'show'])->name('schedule.show');
Route::get('schedule/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedule.edit');
Route::put('schedule/{schedule}', [ScheduleController::class, 'update'])->name('schedule.update');
Route::delete('schedule/{schedule}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');

Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');

Route::get('payment-records', [PaymentRecordController::class, 'index'])->name('payment-records.index');
Route::post('payment-records', [PaymentRecordController::class, 'store'])->name('payment-records.store');
Route::delete('payment-records/{paymentRecord}', [PaymentRecordController::class, 'destroy'])->name('payment-records.destroy');

Route::middleware(['role:admin,business_owner'])->group(function () {
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/appointments', [ReportController::class, 'appointments'])->name('reports.appointments');
    Route::get('reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('reports/invoices', [ReportController::class, 'invoices'])->name('reports.invoices');
    Route::get('reports/payments', [ReportController::class, 'payments'])->name('reports.payments');
    Route::get('reports/clients', [ReportController::class, 'clients'])->name('reports.clients');
    Route::get('reports/staff', [ReportController::class, 'staff'])->name('reports.staff');
    Route::get('reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');

    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
    Route::post('payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('payroll/export/csv', [PayrollController::class, 'exportCsv'])->name('payroll.export.csv');
    Route::post('payroll/{payroll}/mark-paid', [PayrollController::class, 'markPaid'])->name('payroll.mark-paid');
    Route::get('payroll/{payroll}/download', [PayrollController::class, 'download'])->name('payroll.download');
    Route::get('payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::get('payroll/{payroll}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
    Route::put('payroll/{payroll}', [PayrollController::class, 'update'])->name('payroll.update');
    Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
});

Route::get('forms', [FormController::class, 'index'])->name('forms.index');
Route::get('forms/create', [FormController::class, 'create'])->name('forms.create');
Route::post('forms', [FormController::class, 'store'])->name('forms.store');
Route::get('forms/{form}', [FormController::class, 'show'])->name('forms.show');
Route::get('forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
Route::put('forms/{form}', [FormController::class, 'update'])->name('forms.update');
Route::delete('forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');

Route::get('form-records', [FormRecordController::class, 'index'])->name('form-records.index');
Route::get('form-records/create', [FormRecordController::class, 'create'])->name('form-records.create');
Route::post('form-records', [FormRecordController::class, 'store'])->name('form-records.store');
Route::get('form-records/{formRecord}', [FormRecordController::class, 'show'])->name('form-records.show');
Route::delete('form-records/{formRecord}', [FormRecordController::class, 'destroy'])->name('form-records.destroy');

Route::get('locations', [LocationController::class, 'index'])->name('locations.index');
Route::get('locations/create', [LocationController::class, 'create'])->name('locations.create');
Route::post('locations', [LocationController::class, 'store'])->name('locations.store');
Route::get('locations/{location}', [LocationController::class, 'show'])->name('locations.show');
Route::get('locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
Route::put('locations/{location}', [LocationController::class, 'update'])->name('locations.update');
Route::delete('locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

Route::middleware(['role:admin,business_owner'])->group(function () {
    Route::get('business-settings', [BusinessSettingController::class, 'index'])->name('business-settings.index');
    Route::put('business-settings', [BusinessSettingController::class, 'update'])->name('business-settings.update');

    Route::get('subscription', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('subscription/activate', [\App\Http\Controllers\SubscriptionController::class, 'activate'])->name('subscription.activate');
});
});
