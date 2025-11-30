<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * ReportController constructor.
     * Apply middleware inside the constructor using $this->middleware(...)
     */
    public function __construct()
    {
        // Require authentication for all report routes
        $this->middleware('auth');

        // If you have role-based middleware, enable it here:
        // $this->middleware('role:admin');
    }

    /**
     * Display the reports index page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Prepare any data required by the reports index view.
        // Example placeholders (uncomment and adapt if you have models):
        // $reports = \App\Models\Report::latest()->paginate(20);
        // return view('admin.reports.index', compact('reports'));

        return view('admin.reports.index');
    }

    /**
     * Show a single report (example).
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // Example: load a report by id and return a view
        // $report = \App\Models\Report::findOrFail($id);
        // return view('admin.reports.show', compact('report'));

        abort(404);
    }

    /**
     * Export reports (CSV, PDF, etc) - placeholder.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        // Implement export logic if needed.
        abort(501);
    }
}