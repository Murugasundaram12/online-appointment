<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Quotation;
use App\Models\Service;
use App\Models\Staff;
use App\Services\QuotationConversionService;
use App\Services\QuotationCreationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    private QuotationConversionService $quotationConversionService;

    public function __construct(
        private QuotationCreationService $quotationCreationService,
        ?QuotationConversionService $quotationConversionService = null
    ) {
        $this->quotationConversionService = $quotationConversionService ?? app(QuotationConversionService::class);
    }

    public function create(Request $request)
    {
        $clients = Client::where('status', 'active')->orderBy('name')->get(['id', 'name', 'email', 'phone']);
        if ($clients->isEmpty()) {
            $clients = Client::orderBy('name')->get(['id', 'name', 'email', 'phone']);
        }
        $staff = Staff::where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'location_id']);
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $appointments = Appointment::with(['client:id,name', 'staff:id,name', 'service:id,name,price'])
            ->where('status', '!=', 'cancelled')
            ->latest('start_time')
            ->limit(250)
            ->get();
        $nextQuotationNumber = $this->quotationCreationService->generateQuotationNumber();
        $currency = \App\Models\BusinessSetting::where('key', 'currency')->value('value') ?: '$';

        if ($request->wantsJson()) {
            return response()->json([
                'clients' => $clients,
                'staff' => $staff,
                'services' => $services,
                'appointments' => $appointments,
                'next_quotation_number' => $nextQuotationNumber,
                'currency' => $currency,
            ]);
        }

        return view('quotations.create', [
            'clients' => $clients,
            'staff' => $staff,
            'services' => $services,
            'appointments' => $appointments,
            'nextQuotationNumber' => $nextQuotationNumber,
            'currency' => $currency,
        ]);
    }

    public function store(Request $request)
    {
        $currentStaff = Auth::guard('staff')->user();

        $validated = $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'staff_id'       => 'required|exists:staff,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'issued_date'    => 'required|date',
            'valid_until'    => 'required|date|after_or_equal:issued_date',
            'notes'          => 'nullable|string|max:2000',
            'terms'          => 'nullable|string|max:2000',
            'items'          => 'required|array|min:1',
            'items.*.service_id'      => 'nullable|exists:services,id',
            'items.*.staff_id'        => 'nullable|exists:staff,id',
            'items.*.description'     => 'nullable|string|max:255',
            'items.*.quantity'        => 'required|numeric|gt:0',
            'items.*.unit_price'      => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_rate'        => 'nullable|numeric|min:0|max:100',
        ], [
            'client_id.required' => 'Client is required.',
            'client_id.exists'   => 'The selected client is invalid.',
            'staff_id.required'  => 'Practitioner is required.',
            'staff_id.exists'    => 'The selected practitioner is invalid.',
            'items.required'     => 'At least one quotation item is required.',
            'items.min'          => 'At least one quotation item is required.',
            'items.*.quantity.gt'=> 'Quantity must be greater than zero.',
            'items.*.unit_price.min' => 'Unit price cannot be negative.',
            'items.*.tax_rate.max'   => 'Tax rate cannot exceed 100%.',
        ]);

        $this->authorizeQuotationCreation($currentStaff, (int) $validated['staff_id'], !empty($validated['appointment_id']) ? (int) $validated['appointment_id'] : null);

        // Validate appointment belongs to client
        if (!empty($validated['appointment_id'])) {
            $appointment = Appointment::find($validated['appointment_id']);
            if ($appointment && (int) $appointment->client_id !== (int) $validated['client_id']) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'The selected appointment does not belong to the selected client.',
                ]);
            }
        }

        // Validate item-level discounts against line subtotals
        foreach ($validated['items'] as $index => $item) {
            $qty = (float) ($item['quantity'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $disc = (float) ($item['discount_amount'] ?? 0);
            $lineSubtotal = round($qty * $price, 2);

            if ($disc > $lineSubtotal) {
                throw ValidationException::withMessages([
                    "items.{$index}.discount_amount" => "Discount amount ({$disc}) cannot exceed line subtotal ({$lineSubtotal}).",
                ]);
            }
        }

        try {
            $quotation = $this->quotationCreationService->createQuotation([
                'client_id'      => (int) $validated['client_id'],
                'staff_id'       => (int) $validated['staff_id'],
                'created_by'     => $currentStaff?->id,
                'appointment_id' => !empty($validated['appointment_id']) ? (int) $validated['appointment_id'] : null,
                'issued_date'    => $validated['issued_date'],
                'valid_until'    => $validated['valid_until'],
                'notes'          => $validated['notes'] ?? null,
                'terms'          => $validated['terms'] ?? null,
                'status'         => 'draft',
                'items'          => $validated['items'],
            ]);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'items' => $e->getMessage(),
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'   => true,
                'message'   => 'Quotation created successfully.',
                'quotation' => $quotation->load(['items', 'client', 'staff']),
            ], 201);
        }

        if (\Illuminate\Support\Facades\Route::has('quotations.create')) {
            return redirect()->route('quotations.create')->with('success', "Quotation created successfully: {$quotation->quotation_number}");
        }

        if (\Illuminate\Support\Facades\Route::has('quotations.show')) {
            return redirect()->route('quotations.show', $quotation->id)->with('success', 'Quotation created successfully.');
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Quotation created successfully.',
            'quotation' => $quotation->load(['items', 'client', 'staff']),
        ], 201);
    }

    public function show(Quotation $quotation)
    {
        $quotation = $this->quotationQuery()->findOrFail($quotation->id);
        $this->authorizeQuotationAccess($quotation);

        if (request()->wantsJson()) {
            return response()->json([
                'success'   => true,
                'quotation' => $quotation,
            ]);
        }

        return view('quotations.show', $this->quotationViewData($quotation));
    }

    public function download(Quotation $quotation)
    {
        $quotation = $this->quotationQuery()->findOrFail($quotation->id);
        $this->authorizeQuotationAccess($quotation);

        $data = $this->quotationViewData($quotation, true);
        $filename = 'quotation-' . $quotation->quotation_number . '.pdf';

        try {
            return Pdf::loadView('quotations.pdf', $data)
                ->setPaper('a4', 'portrait')
                ->download($filename);
        } catch (\Throwable $exception) {
            Log::error('Quotation PDF generation failed', [
                'quotation_id'     => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'exception'        => $exception->getMessage(),
            ]);

            return $this->htmlQuotationFallback($quotation, $data);
        }
    }

    public function convert(Quotation $quotation)
    {
        $quotation = $this->quotationQuery()->findOrFail($quotation->id);
        $this->authorizeQuotationAccess($quotation);

        try {
            $invoice = $this->quotationConversionService->convertToInvoice($quotation);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quotation converted to invoice successfully.',
                    'invoice' => $invoice,
                ], 200);
            }

            return redirect()->route('invoices.show', $invoice->id)
                ->with('success', "Quotation {$quotation->quotation_number} converted to invoice {$invoice->invoice_number} successfully.");
        } catch (\InvalidArgumentException $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('quotations.show', $quotation->id)
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Quotation conversion failed unexpectedly', [
                'quotation_id'     => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'error'            => $e->getMessage(),
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred during quotation conversion.',
                ], 500);
            }

            return redirect()->route('quotations.show', $quotation->id)
                ->with('error', 'An error occurred during quotation conversion. Please try again.');
        }
    }

    private function quotationQuery()
    {
        return Quotation::with([
            'client',
            'staff.location',
            'createdBy',
            'appointment.service',
            'appointment.location',
            'items.service',
            'items.staff',
        ]);
    }

    private function quotationViewData(Quotation $quotation, bool $forPdf = false): array
    {
        $settings = BusinessSetting::pluck('value', 'key')->toArray();
        $currency = $settings['currency'] ?? '$';
        $settings['business_logo'] = $this->resolveQuotationLogo($settings['business_logo'] ?? ($settings['logo'] ?? null), $forPdf);

        return [
            'quotation'  => $quotation,
            'settings'   => $settings,
            'currency'   => $currency,
            'forPdf'     => $forPdf,
            'dateFormat' => $settings['date_format'] ?? 'd M Y',
            'timeFormat' => $settings['time_format'] ?? 'g:i A',
            'timezone'   => $settings['timezone'] ?? config('app.timezone'),
        ];
    }

    private function htmlQuotationFallback(Quotation $quotation, array $data)
    {
        return response()
            ->view('quotations.pdf', $data)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="quotation-' . $quotation->quotation_number . '.html"');
    }

    private function authorizeQuotationAccess(Quotation $quotation): void
    {
        $staff = Auth::guard('staff')->user();
        if (!$staff) {
            abort(403, 'Unauthorized action.');
        }

        if (in_array($staff->access_level, ['admin', 'business_owner'], true)) {
            return;
        }

        if (is_null($staff->location_id)) {
            return;
        }

        $quotationStaffLocation = $quotation->staff?->location_id;
        $appointmentLocation = $quotation->appointment?->location_id;

        $matchesLocation = ($quotationStaffLocation && (int) $quotationStaffLocation === (int) $staff->location_id)
            || ($appointmentLocation && (int) $appointmentLocation === (int) $staff->location_id)
            || ((int) $quotation->staff_id === (int) $staff->id)
            || ($quotation->created_by && (int) $quotation->created_by === (int) $staff->id);

        if (!$matchesLocation) {
            abort(403, 'Unauthorized access to quotation at another location.');
        }
    }

    private function resolveQuotationLogo(?string $logo, bool $forPdf = false): ?string
    {
        if (!$logo) {
            return null;
        }

        if (filter_var($logo, FILTER_VALIDATE_URL)) {
            return $forPdf ? null : $logo;
        }

        $relative = ltrim(str_replace('\\', '/', $logo), '/');

        if (is_file(public_path($relative))) {
            return $forPdf ? public_path($relative) : asset($relative);
        }

        if (is_file(public_path('storage/' . $relative))) {
            return $forPdf ? public_path('storage/' . $relative) : asset('storage/' . $relative);
        }

        if (is_file(storage_path('app/public/' . $relative))) {
            return $forPdf ? storage_path('app/public/' . $relative) : asset('storage/' . $relative);
        }

        return null;
    }

    private function authorizeQuotationCreation(?Staff $currentStaff, int $targetStaffId, ?int $appointmentId = null): void
    {
        if (!$currentStaff) {
            abort(401, 'Unauthenticated staff.');
        }

        if (in_array($currentStaff->access_level, ['admin', 'business_owner'], true)) {
            return;
        }

        if (is_null($currentStaff->location_id)) {
            return;
        }

        $targetStaff = Staff::find($targetStaffId);
        $targetLocation = $targetStaff?->location_id;

        $appointmentLocation = null;
        if ($appointmentId) {
            $appt = Appointment::find($appointmentId);
            $appointmentLocation = $appt?->location_id;
        }

        $matchesLocation = ($targetLocation && (int) $targetLocation === (int) $currentStaff->location_id)
            || ($appointmentLocation && (int) $appointmentLocation === (int) $currentStaff->location_id)
            || ((int) $targetStaffId === (int) $currentStaff->id);

        if (!$matchesLocation) {
            abort(403, 'Unauthorized access to quotation at another location.');
        }
    }
}
