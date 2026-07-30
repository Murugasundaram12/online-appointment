<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Staff;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * Display list of payroll records
     */
    public function index(Request $request)
    {
        $query = Payroll::with('staff');

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

        // Sort by latest first
        $payrolls = $query->orderBy('period_start', 'desc')->paginate(15);
        $staff = Staff::where('is_active', true)->get(['id', 'name']);

        return view('payroll.index', compact('payrolls', 'staff'));
    }

    /**
     * Show form to create new payroll
     */
    public function create()
    {
        $staff = Staff::where('is_active', true)->get(['id', 'name']);
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
            'payment_date' => 'required_if:status,completed|nullable|date',
            'payment_type' => 'required|in:cash,check,transfer,mobile_money',
            'status' => 'required|in:pending,processing,completed,cancelled',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $payroll = new Payroll($validated);
            $payroll->total_payout = $payroll->calculateTotalPayout();
            $payroll->save();

            return redirect()->route('payroll.index')
                ->with('success', 'Payroll record created successfully!');
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
        return view('payroll.show', compact('payroll'));
    }

    /**
     * Show form to edit payroll
     */
    public function edit(string $id)
    {
        $payroll = Payroll::findOrFail($id);
        $staff = Staff::where('is_active', true)->get(['id', 'name']);
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
            'payment_date' => 'required_if:status,completed|nullable|date',
            'payment_type' => 'required|in:cash,check,transfer,mobile_money',
            'status' => 'required|in:pending,processing,completed,cancelled',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $payroll->update($validated);
            $payroll->total_payout = $payroll->calculateTotalPayout();
            $payroll->save();

            return redirect()->route('payroll.index')
                ->with('success', 'Payroll record updated successfully!');
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

        $totalPayout = $payrolls->sum('total_payout');
        $totalCommission = $payrolls->sum('commission_amount');
        $pendingCount = $payrolls->where('status', 'pending')->count();

        return view('payroll.report', compact('payrolls', 'totalPayout', 'totalCommission', 'pendingCount', 'startDate', 'endDate'));
    }
}
