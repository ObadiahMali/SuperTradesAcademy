<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

class EmployeeController extends Controller
{
    /**
     * Create a new controller instance.
     * Attach middleware here.
     */
  

    /**
     * Display a listing of employees.
     */
    public function index()
    {
        $employees = Employee::latest()->paginate(15);
        return view('admin.employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        return view('admin.employees.create');
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'email'    => 'required|email|unique:employees,email',
        ]);

        Employee::create($data);

        return redirect()->route('admin.employees.index')
                         ->with('success', 'Employee created successfully.');
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee)
    {
        return view('admin.employees.edit', compact('employee'));
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'email'    => 'required|email|unique:employees,email,' . $employee->id,
        ]);

        $employee->update($data);

        return redirect()->route('admin.employees.index')
                         ->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('admin.employees.index')
                         ->with('success', 'Employee deleted successfully.');
    }
}