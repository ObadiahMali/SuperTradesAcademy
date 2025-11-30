<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use Illuminate\Http\Request;

class IntakeController extends Controller
{
    public function index()
    {
        $intakes = Intake::withCount('students')->get();
        return view('secretary.intakes.index', compact('intakes'));
    }

    public function create()
    {
        return view('secretary.intakes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'boolean',
        ]);

        Intake::create($validated);

        return redirect()->route('secretary.intakes.index')
            ->with('success', 'Intake created successfully.');
    }

    public function show(Intake $intake)
    {
        return view('secretary.intakes.show', compact('intake'));
    }

    public function edit(Intake $intake)
    {
        return view('secretary.intakes.edit', compact('intake'));
    }

    public function update(Request $request, Intake $intake)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'active' => 'boolean',
        ]);

        $intake->update($validated);

        return redirect()->route('secretary.intakes.index')
            ->with('success', 'Intake updated successfully.');
    }

    public function destroy(Intake $intake)
    {
        $intake->delete();

        return redirect()->route('secretary.intakes.index')
            ->with('success', 'Intake deleted successfully.');
    }
}