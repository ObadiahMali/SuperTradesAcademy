<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Support\Facades\Gate;

class PlanController extends Controller
{
 

    public function index()
    {
        Gate::authorize('manage-plans'); // policy/gate check
        $plans = Plan::orderBy('key')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function edit(Plan $plan)
    {
        Gate::authorize('manage-plans');
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        Gate::authorize('manage-plans');

        $data = $request->validate([
            'label' => ['required','string','max:255'],
            'price' => ['required','numeric','min:0'],
            'currency' => ['required','string','size:3'],
        ]);

        $plan->update($data);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }
}