<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Support\StaffCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $staffs = Staff::with(['location', 'categories'])
            ->withCount(['appointments', 'payrolls'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $categoryFilter = trim($request->category);
                $query->where(function ($q) use ($categoryFilter) {
                    $q->whereHas('categories', function ($cq) use ($categoryFilter) {
                        if (is_numeric($categoryFilter)) {
                            $cq->where('service_categories.id', $categoryFilter);
                        } else {
                            $cq->where('service_categories.name', 'like', "%{$categoryFilter}%");
                        }
                    })
                    ->orWhere('category', 'like', "%{$categoryFilter}%");
                });
            })
            ->when($request->filled('access_level'), function ($query) use ($request) {
                $query->where('access_level', $request->access_level);
            })
            ->when($request->filled('location_id'), function ($query) use ($request) {
                $query->where('location_id', $request->location_id);
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $locations = Location::where('is_active', true)->orderBy('name')->get();
        $serviceCategories = ServiceCategory::orderBy('name')->get();
        $categories = $serviceCategories->pluck('name');

        return view('staff.index', compact('staffs', 'locations', 'categories', 'serviceCategories'));
    }

    public function create()
    {
        $locations = Location::where('is_active', true)->orderBy('name')->get();
        $categories = ServiceCategory::orderBy('name')->get();
        $serviceCategories = $categories;

        return view('staff.create', compact('locations', 'categories', 'serviceCategories'));
    }

    public function store(Request $request)
    {
        if (!\App\Models\Subscription::checkLimit('staff')) {
            return back()->withInput()->with('error', 'Your current subscription plan staff limit has been reached. Please upgrade.');
        }

        $validated = $this->validateStaff($request, null, false);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            $validated['password'] = null;
        }

        foreach (['name', 'phone', 'bio', 'color', 'registration_number', 'designation', 'category', 'location_id'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }
        if (array_key_exists('salary', $validated) && ($validated['salary'] === '' || $validated['salary'] === null)) {
            $validated['salary'] = 0;
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $staff = Staff::create($validated);

        if ($request->has('categories')) {
            $categoryIds = array_filter((array) ($validated['categories'] ?? []));
            $staff->categories()->sync($categoryIds);
        } elseif ($request->filled('category')) {
            $matched = ServiceCategory::whereRaw('LOWER(TRIM(name)) = ?', [StaffCategoryService::normalize($request->category)])->first();
            if ($matched) {
                $staff->categories()->sync([$matched->id]);
            }
        }

        return redirect()->route('staff.index')->with('success', 'Staff created successfully.');
    }

    public function show(string $id)
    {
        $staff = Staff::with(['location', 'categories', 'payrolls' => fn ($query) => $query->latest('period_end')->limit(5)])
            ->withCount(['appointments', 'schedules', 'payrolls'])
            ->findOrFail($id);

        $lastPayroll = $staff->payrolls->first();
        $pendingPayroll = $staff->payrolls()->where('status', 'pending')->sum('total_payout');

        return view('staff.show', compact('staff', 'lastPayroll', 'pendingPayroll'));
    }

    public function edit(string $id)
    {
        $staff = Staff::with('categories')->findOrFail($id);
        $locations = Location::where('is_active', true)
            ->orWhere('id', $staff->location_id)
            ->orderBy('name')
            ->get();
        $categories = ServiceCategory::orderBy('name')->get();
        $serviceCategories = $categories;

        return view('staff.edit', compact('staff', 'locations', 'categories', 'serviceCategories'));
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

        foreach (['name', 'phone', 'bio', 'color', 'registration_number', 'designation', 'category', 'location_id'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }
        if (array_key_exists('salary', $validated) && ($validated['salary'] === '' || $validated['salary'] === null)) {
            $validated['salary'] = 0;
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;
        $staff->update($validated);

        if ($request->has('categories')) {
            $categoryIds = array_filter((array) ($validated['categories'] ?? []));
            $staff->categories()->sync($categoryIds);
        } elseif ($request->has('categories_submitted')) {
            $staff->categories()->sync([]);
        } elseif ($request->has('category')) {
            if ($request->filled('category')) {
                $matched = ServiceCategory::whereRaw('LOWER(TRIM(name)) = ?', [StaffCategoryService::normalize($request->category)])->first();
                if ($matched) {
                    $staff->categories()->sync([$matched->id]);
                } else {
                    $staff->categories()->sync([]);
                }
            } else {
                $staff->categories()->sync([]);
            }
        }

        return redirect()->route('staff.index')->with('success', 'Staff updated successfully.');
    }

    public function destroy(string $id)
    {
        $staff = Staff::withCount(['appointments', 'payrolls'])->findOrFail($id);

        if ($staff->isSuperAdmin()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Super Admin account cannot be deleted.',
                ], 422);
            }

            return redirect()
                ->route('staff.index')
                ->with('error', 'Super Admin account cannot be deleted.');
        }

        $currentStaffId = Auth::guard('staff')->id();
        if ($currentStaffId !== null && (int) $id === (int) $currentStaffId) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'You cannot delete your own account while you are logged in.',
                ], 422);
            }

            return redirect()
                ->route('staff.index')
                ->with('error', 'You cannot delete your own account while you are logged in.');
        }

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
            'name' => ['nullable', 'string', 'max:255'],
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
            'registration_number' => ['nullable', 'string', 'max:100'],
            'designation' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'distinct', Rule::exists('service_categories', 'id')],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'location_id' => [
                'nullable',
                Rule::exists('locations', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'is_active' => ['nullable', 'boolean'],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'string', 'min:8', 'max:255'],
        ];

        $validated = $request->validate($rules, [
            'email.required' => 'Email is required.',
            'email.email' => 'The email field must be a valid email address.',
            'email.unique' => 'This email is already used by another staff member.',
            'location_id.exists' => 'Please choose an active location for this staff member.',
            'color.regex' => 'Staff color must be a valid hex color like #4f46e5.',
            'categories.array' => 'Categories must be an array.',
            'categories.*.exists' => 'The selected category does not exist.',
            'categories.*.distinct' => 'Duplicate categories are not allowed.',
        ]);

        if (empty($validated['access_level'])) {
            $validated['access_level'] = $staff?->access_level ?? 'staff';
        }

        if (!empty($validated['phone'])) {
            $validated['phone'] = \App\Services\PhoneFormatter::format($validated['phone']);
        }

        return $validated;
    }
}
