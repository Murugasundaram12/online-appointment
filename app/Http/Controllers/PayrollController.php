<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Staff;
use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    /**
     * Display list of payroll records
     */
    public function index(Request $request)
    {
        $query = Payroll::with('staff');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('id', preg_replace('/\D/', '', $search) ?: -1)
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('staff', function ($staffQuery) use ($search) {
                        $staffQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('access_level', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by staff
        if ($request->has('staff_id') && $request->staff_id) {
            $query->where('staff_id', $request->staff_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by period
        if ($request->has('period_start') && $request->period_start) {
            $query->where('period_start', '>=', $request->period_start);
        }

        if ($request->has('period_end') && $request->period_end) {
            $query->where('period_end', '<=', $request->period_end);
        }

        if ($request->filled('month')) {
            $query->whereMonth('period_start', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('period_start', $request->year);
        }

        // Sort by latest first
        $summaryQuery = clone $query;
        $payrolls = $query->orderBy('period_start', 'desc')->orderByDesc('id')->paginate(15)->withQueryString();
        $staff = Staff::where('is_active', true)->orderBy('name')->get(['id', 'name', 'access_level', 'salary']);
        $summary = $this->buildSummary($summaryQuery);

        return view('payroll.index', compact('payrolls', 'staff', 'summary'));
    }

    /**
     * Show form to create new payroll
     */
    public function create()
    {
        $staff = Staff::where('is_active', true)->orderBy('name')->get(['id', 'name', 'salary']);
        return view('payroll.create', compact('staff'));
    }

    /**
     * Store payroll record in database
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'salary_amount' => 'required|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'total_hours' => 'nullable|numeric|min:0',
            'payment_date' => 'required_if:status,completed,paid|nullable|date',
            'payment_type' => 'required|in:cash,check,transfer,mobile_money',
            'status' => 'required|in:pending,processing,completed,paid,cancelled',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $validated['status'] = $this->normalizeStatus($validated['status']);

            $payroll = DB::transaction(function () use ($validated) {
                $this->ensureNoDuplicatePayroll(
                    (int) $validated['staff_id'],
                    $validated['period_start'],
                    $validated['period_end']
                );

                $payroll = new Payroll($validated);
                $payroll->total_payout = $payroll->calculateTotalPayout();
                $payroll->save();

                return $payroll;
            });

            return redirect()->route('payroll.index')
                ->with('success', 'Payroll record created successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating payroll: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show single payroll record
     */
    public function show(string $id)
    {
        $payroll = Payroll::with('staff')->findOrFail($id);
        $settings = $this->businessSettings();
        return view('payroll.show', compact('payroll', 'settings'));
    }

    /**
     * Show form to edit payroll
     */
    public function edit(string $id)
    {
        $payroll = Payroll::findOrFail($id);
        $staff = Staff::where('is_active', true)->orderBy('name')->get(['id', 'name', 'salary']);
        return view('payroll.edit', compact('payroll', 'staff'));
    }

    /**
     * Update payroll record
     */
    public function update(Request $request, string $id)
    {
        $payroll = Payroll::findOrFail($id);

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'salary_amount' => 'required|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'total_hours' => 'nullable|numeric|min:0',
            'payment_date' => 'required_if:status,completed,paid|nullable|date',
            'payment_type' => 'required|in:cash,check,transfer,mobile_money',
            'status' => 'required|in:pending,processing,completed,paid,cancelled',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $validated['status'] = $this->normalizeStatus($validated['status']);

            DB::transaction(function () use ($payroll, $validated) {
                $this->ensureNoDuplicatePayroll(
                    (int) $payroll->staff_id,
                    $validated['period_start'],
                    $validated['period_end'],
                    $payroll->id
                );

                $payroll->update($validated);
                $payroll->total_payout = $payroll->calculateTotalPayout();
                $payroll->save();
            });

            return redirect()->route('payroll.index')
                ->with('success', 'Payroll record updated successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating payroll: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete payroll record
     */
    public function destroy(string $id)
    {
        try {
            $payroll = Payroll::findOrFail($id);
            if ($payroll->isPaid()) {
                return back()->with('error', 'Paid payroll records cannot be deleted. Cancel or adjust them through payroll controls instead.');
            }

            $payroll->delete();

            return redirect()->route('payroll.index')
                ->with('success', 'Payroll record deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting payroll: ' . $e->getMessage());
        }
    }

    /**
     * Generate payroll for multiple staff members for a period
     */
    public function generatePayroll(Request $request)
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'staff_ids' => 'nullable|array',
            'payment_date' => 'required|date'
        ]);

        try {
            $staff = $validated['staff_ids']
                ? Staff::whereIn('id', $validated['staff_ids'])->get()
                : Staff::where('is_active', true)->get();

            $created = 0;
            $errors = [];

            DB::transaction(function () use ($staff, $validated, &$created) {
            foreach ($staff as $member) {
                // Check if payroll already exists for this period
                $exists = Payroll::where('staff_id', $member->id)
                    ->where('period_start', $validated['period_start'])
                    ->where('period_end', $validated['period_end'])
                    ->exists();

                if (!$exists && $member->salary > 0) {
                    Payroll::create([
                        'staff_id' => $member->id,
                        'period_start' => $validated['period_start'],
                        'period_end' => $validated['period_end'],
                        'salary_amount' => $member->salary,
                        'commission_amount' => 0,
                        'bonus' => 0,
                        'deductions' => 0,
                        'total_hours' => 0,
                        'total_payout' => $member->salary,
                        'payment_date' => $validated['payment_date'],
                        'payment_type' => 'transfer',
                        'status' => 'pending',
                        'notes' => 'Auto-generated payroll'
                    ]);
                    $created++;
                }
            }
            });

            return response()->json([
                'success' => true,
                'message' => "Generated $created payroll records",
                'created' => $created
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating payroll: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payroll report for date range
     */
    public function report(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth());

        $payrolls = Payroll::with('staff')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->orderBy('period_start', 'desc')
            ->get();

        $summary = [
            'totalPayout' => $payrolls->sum('total_payout'),
            'totalSalary' => $payrolls->sum('salary_amount'),
            'totalCommission' => $payrolls->sum('commission_amount'),
            'totalBonus' => $payrolls->sum('bonus'),
            'totalDeductions' => $payrolls->sum('deductions'),
            'pendingCount' => $payrolls->where('status', 'pending')->count(),
            'paidCount' => $payrolls->whereIn('status', ['completed', 'paid'])->count(),
            'highest' => $payrolls->sortByDesc('total_payout')->first(),
            'lowest' => $payrolls->sortBy('total_payout')->first(),
        ];

        return view('payroll.report', compact('payrolls', 'summary', 'startDate', 'endDate'));
    }

    public function markPaid(string $id)
    {
        $payroll = Payroll::findOrFail($id);

        if ($payroll->isPaid()) {
            return back()->with('info', 'This payroll is already marked as paid.');
        }

        DB::transaction(function () use ($payroll) {
            $payroll->status = 'completed';
            $payroll->payment_date = $payroll->payment_date ?: now()->toDateString();
            $payroll->save();
        });

        Log::info('Payroll marked paid', [
            'payroll_id' => $payroll->id,
            'staff_id' => $payroll->staff_id,
        ]);

        return back()->with('success', 'Payroll marked as paid successfully.');
    }

    public function download(string $id)
    {
        $payroll = Payroll::with('staff')->findOrFail($id);
        $settings = $this->businessSettings();

        try {
            return Pdf::loadView('payroll.pdf', compact('payroll', 'settings'))
                ->setPaper('a4', 'portrait')
                ->download(strtolower($payroll->payroll_number) . '.pdf');
        } catch (\Throwable $e) {
            Log::error('Payroll PDF generation failed', [
                'payroll_id' => $payroll->id,
                'message' => $e->getMessage(),
            ]);

            return view('payroll.pdf', compact('payroll', 'settings'))
                ->with('pdfFallback', true);
        }
    }

    public function exportCsv(Request $request)
    {
        $payrolls = Payroll::with('staff')->orderByDesc('period_start')->get();

        return response()->streamDownload(function () use ($payrolls) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Payroll ID', 'Staff', 'Period Start', 'Period End', 'Salary', 'Commission', 'Bonus', 'Deductions', 'Total Payout', 'Payment Date', 'Status']);

            foreach ($payrolls as $payroll) {
                fputcsv($handle, [
                    $payroll->payroll_number,
                    optional($payroll->staff)->name,
                    optional($payroll->period_start)->toDateString(),
                    optional($payroll->period_end)->toDateString(),
                    $payroll->salary_amount,
                    $payroll->commission_amount,
                    $payroll->bonus,
                    $payroll->deductions,
                    $payroll->total_payout,
                    optional($payroll->payment_date)->toDateString(),
                    $payroll->display_status,
                ]);
            }

            fclose($handle);
        }, 'payroll-export.csv', ['Content-Type' => 'text/csv']);
    }

    private function ensureNoDuplicatePayroll(int $staffId, string $periodStart, string $periodEnd, ?int $exceptId = null): void
    {
        $exists = Payroll::where('staff_id', $staffId)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'period_start' => 'A payroll record already exists for this staff member and period.',
            ]);
        }
    }

    private function normalizeStatus(string $status): string
    {
        return $status === 'paid' ? 'completed' : $status;
    }

    private function buildSummary($query): array
    {
        $records = $query->get();
        $thisMonth = now();

        return [
            'total_payroll' => $records->count(),
            'pending_payroll' => $records->where('status', 'pending')->count(),
            'paid_payroll' => $records->whereIn('status', ['completed', 'paid'])->count(),
            'this_month_payroll' => $records->filter(fn ($payroll) => $payroll->period_start && $payroll->period_start->isSameMonth($thisMonth))->count(),
            'total_salary' => $records->sum('salary_amount'),
            'total_commission' => $records->sum('commission_amount'),
            'total_bonus' => $records->sum('bonus'),
            'total_deductions' => $records->sum('deductions'),
            'net_payroll' => $records->sum('total_payout'),
        ];
    }

    private function businessSettings(): array
    {
        return BusinessSetting::pluck('value', 'key')->all();
    }
}
