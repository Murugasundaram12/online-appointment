<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsuranceCompanyController extends Controller
{
    public function index(Request $request)
    {
        $insuranceCompanies = InsuranceCompany::withCount('insuranceInformations')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return view('insurance_companies.index', compact('insuranceCompanies'));
    }

    public function create()
    {
        return view('insurance_companies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:insurance_companies,name',
        ], [
            'name.unique' => 'An insurance company with this name already exists.',
        ]);

        InsuranceCompany::create($validated);

        return redirect()->route('insurance-companies.index')->with('success', 'Insurance Company created successfully.');
    }

    public function edit(InsuranceCompany $insuranceCompany)
    {
        return view('insurance_companies.edit', compact('insuranceCompany'));
    }

    public function update(Request $request, InsuranceCompany $insuranceCompany)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('insurance_companies', 'name')->ignore($insuranceCompany->id)],
        ], [
            'name.unique' => 'An insurance company with this name already exists.',
        ]);

        $insuranceCompany->update($validated);

        return redirect()->route('insurance-companies.index')->with('success', 'Insurance Company updated successfully.');
    }

    public function destroy(InsuranceCompany $insuranceCompany)
    {
        if ($insuranceCompany->insuranceInformations()->exists()) {
            return back()->with('error', 'Cannot delete "' . $insuranceCompany->name . '" because insurance records are associated with it.');
        }

        $insuranceCompany->delete();

        return redirect()->route('insurance-companies.index')->with('success', 'Insurance Company deleted successfully.');
    }
}
