<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the expenses with filters and totals.
     */
    public function index(Request $request)
    {
        $query = Expense::query()->latest();

        // Search q: title, description, vendor, category
        if ($q = $request->query('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('title', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%")
                   ->orWhere('vendor', 'like', "%{$q}%")
                   ->orWhere('category', 'like', "%{$q}%");
            });
        }

        // Type filter
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        // Date range filter (incurred_at preferred, fallback to created_at)
        $from = $request->query('from');
        $to   = $request->query('to');

        if ($from || $to) {
            $fromDate = $from ? Carbon::parse($from)->startOfDay()->toDateTimeString() : null;
            $toDate   = $to   ? Carbon::parse($to)->endOfDay()->toDateTimeString()   : null;

            $query->where(function ($qb) use ($fromDate, $toDate) {
                if ($fromDate && $toDate) {
                    $qb->whereBetween('incurred_at', [$fromDate, $toDate])
                       ->orWhereBetween('created_at', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $qb->where('incurred_at', '>=', $fromDate)
                       ->orWhere('created_at', '>=', $fromDate);
                } elseif ($toDate) {
                    $qb->where('incurred_at', '<=', $toDate)
                       ->orWhere('created_at', '<=', $toDate);
                }
            });
        }

        // Clone for totals (so pagination doesn't affect totals)
        $totalsQuery = (clone $query);

        // Paginate after filters
        $expenses = $query->paginate(15)->withQueryString();

        // Totals for filtered set (grouped by currency)
        $totalsByCurrency = $totalsQuery
            ->selectRaw('COALESCE(currency, "UGX") as currency, SUM(amount) as total')
            ->groupBy('currency')
            ->get()
            ->pluck('total', 'currency')
            ->map(fn($v) => (float) $v);

        // Page total (current page)
        $pageTotal = $expenses->sum('amount');

        return view('secretary.expenses.index', compact('expenses', 'totalsByCurrency', 'pageTotal'));
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create(Request $request)
    {
        // If duplicating an existing expense, load it
        $duplicate = null;
        if ($request->query('duplicate')) {
            $duplicate = Expense::find($request->query('duplicate'));
        }

        // Example categories; replace with DB query if needed
        $categories = ['Office', 'Supplies', 'Rent', 'Utilities'];

        return view('secretary.expenses.create', compact('duplicate', 'categories'));
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'vendor' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'type' => 'nullable|in:fixed,variable',
            'incurred_at' => 'nullable|date',
            'currency' => 'nullable|string|max:3',
            'amount' => 'required|numeric|min:0',
            'paid' => 'sometimes|boolean',
        ]);

        // Ensure boolean conversion and paid_at handling
        $data['paid'] = $request->boolean('paid', false);
        $data['paid_at'] = $data['paid'] ? now() : null;

        Expense::create($data);

        return redirect()->route('secretary.expenses.index')->with('success', 'Expense recorded.');
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense)
    {
        return view('secretary.expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(Expense $expense)
    {
        $categories = ['Office', 'Supplies', 'Rent', 'Utilities'];
        return view('secretary.expenses.edit', compact('expense', 'categories'));
    }

    /**
     * Update the specified expense in storage.
     */
 public function update(Request $request, Expense $expense)
{
    $data = $request->validate([
        'title'    => 'required|string|max:255',
        'amount'   => 'required|numeric|min:0',
        'currency' => 'required|string|in:UGX,USD',
        'spent_at' => 'nullable|date',
        'notes'    => 'nullable|string',
        'paid'     => 'required|in:0,1',
    ]);

    $expense->title = $data['title'];
    $expense->amount = $data['amount'];
    $expense->currency = $data['currency'];
    $expense->spent_at = $data['spent_at'] ? Carbon::parse($data['spent_at']) : null;
    $expense->notes = $data['notes'] ?? null;
    $expense->paid = (bool) $data['paid'];
    $expense->paid_at = $expense->paid ? now() : null;
    $expense->save();

    return redirect()->route('secretary.expenses.show', $expense)->with('success', 'Expense updated.');
}

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('secretary.expenses.index')->with('success', 'Expense deleted.');
    }

    /**
     * Toggle paid/unpaid state for an expense.
     */
    public function togglePaid(Expense $expense)
    {
        $expense->paid = ! $expense->paid;
        $expense->paid_at = $expense->paid ? now() : null;
        $expense->save();

        return back()->with('success', 'Expense status updated.');
    }
}