<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        $staff = Staff::with('location')
            ->withCount(['appointments', 'payrolls'])
            ->latest()
            ->paginate(10);
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('staff.index', compact('staff', 'locations'));
    }

    public function create()
    {
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('staff.create', compact('locations'));
    }

    public function store(Request $request)
    {
        if (!\App\Models\Subscription::checkLimit('staff')) {
            return back()->withInput()->with('error', 'Your current subscription plan staff limit has been reached. Please upgrade.');
        }

        $validated = $this->validateStaff($request, null, true);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        Staff::create($validated);

        return redirect()->route('staff.index')->with('success', 'Staff created successfully.');
    }

    public function show(string $id)
    {
        $staff = Staff::with(['location', 'payrolls' => fn ($query) => $query->latest('period_end')->limit(5)])
            ->withCount(['appointments', 'schedules', 'payrolls'])
            ->findOrFail($id);

        $lastPayroll = $staff->payrolls->first();
        $pendingPayroll = $staff->payrolls()->where('status', 'pending')->sum('total_payout');

        return view('staff.show', compact('staff', 'lastPayroll', 'pendingPayroll'));
    }

    public function edit(string $id)
    {
        $staff = Staff::findOrFail($id);
        $locations = Location::where('is_active', true)
            ->orWhere('id', $staff->location_id)
            ->orderBy('name')
            ->get();

        return view('staff.edit', compact('staff', 'locations'));
    }

    public function update(Request $request, string $id)
    {
        $staff = Staff::findOrFail($id);
        $validated = $this->validateStaff($request, $staff, false);

        if (array_key_exists('password', $validated)) {
            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;
        $staff->update($validated);

        return redirect()->route('staff.index')->with('success', 'Staff updated successfully.');
    }

    public function destroy(string $id)
    {
        $staff = Staff::withCount(['appointments', 'payrolls'])->findOrFail($id);

        if ($staff->appointments_count > 0 || $staff->payrolls_count > 0) {
            return redirect()
                ->route('staff.index')
                ->with('error', 'This staff member has appointments or payroll records and cannot be deleted. Deactivate the account instead.');
        }

        $staff->delete();

        return redirect()->route('staff.index')->with('success', 'Staff deleted successfully.');
    }

    private function validateStaff(Request $request, ?Staff $staff, bool $passwordRequired): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('staff', 'email')->ignore($staff?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'access_level' => ['nullable', Rule::in(['admin', 'staff', 'business_owner', 'receptionist', 'practitioner'])],
            'category' => ['nullable', 'string', 'max:100'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'is_active' => ['nullable', 'boolean'],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'string', 'min:8', 'max:255'],
        ];

        $validated = $request->validate($rules, [
            'location_id.exists' => 'Please choose an active location for this staff member.',
            'color.regex' => 'Staff color must be a valid hex color like #4f46e5.',
        ]);

        if (array_key_exists('access_level', $validated) && $validated['access_level'] === null) {
            $validated['access_level'] = 'staff';
        }

        return $validated;
    }
}
