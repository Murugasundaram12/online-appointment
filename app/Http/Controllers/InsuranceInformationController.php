<?php

namespace App\Http\Controllers;

use App\Models\InsuranceInformation;
use Illuminate\Http\Request;

class InsuranceInformationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'policy_id' => 'nullable|string|max:255',
            'member_id_or_contract_number' => 'nullable|string|max:255',
        ], [
            'insurance_company_id.required' => 'Please select an insurance company.',
        ]);

        InsuranceInformation::create($validated);

        return back()->with('success', 'Insurance information added successfully.');
    }

    public function update(Request $request, InsuranceInformation $insuranceInformation)
    {
        $validated = $request->validate([
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'policy_id' => 'nullable|string|max:255',
            'member_id_or_contract_number' => 'nullable|string|max:255',
        ]);

        $insuranceInformation->update($validated);

        return back()->with('success', 'Insurance information updated successfully.');
    }

    public function destroy(InsuranceInformation $insuranceInformation)
    {
        $insuranceInformation->delete();

        return back()->with('success', 'Insurance information deleted successfully.');
    }
}
