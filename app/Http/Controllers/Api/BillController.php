<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BillController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $currentCycleStart = $now->copy()->startOfMonth();

        $bills = $user->bills()
            ->with(['payments' => function ($query) use ($currentCycleStart) {
                $query->where('billing_cycle', $currentCycleStart);
            }])
            ->orderBy('due_day', 'asc')
            ->get();

        // Partition the collection into paid and unpaid bills for the current month.
        // This is far more efficient than filtering on the frontend.
        [$paidBills, $unpaidBills] = $bills->partition(function ($bill) {
            return $bill->payments->isNotEmpty();
        });

        // Calculate summary data
        $nextBillDue = null;
        if ($unpaidBills->isNotEmpty()) {
            $nextBill = $unpaidBills->first();
            $dueDate = $now->copy()->setDay($nextBill->due_day);
            // Use diffForHumans for a friendly string like "in 2 days" or "2 days ago"
            $nextBillDue = [
                'name' => $nextBill->name,
                'due_in' => $dueDate->diffForHumans(null, true),
                'is_overdue' => $dueDate->isPast() && !$dueDate->isToday(),
            ];
        }

        $summary = [
            'total_due' => round($unpaidBills->sum('amount'), 2),
            'bills_left_count' => $unpaidBills->count(),
            'next_bill_due' => $nextBillDue,
        ];

        return response()->json([
            'summary' => $summary,
            'unpaid_bills' => BillResource::collection($unpaidBills),
            'paid_bills' => BillResource::collection($paidBills),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'due_day' => 'required|integer|min:1|max:31',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $bill = Auth::user()->bills()->create($validator->validated());

        // Return a fresh resource, eager loading payments (even though there are none yet)
        $bill->load('payments');
        return new BillResource($bill);
    }

    public function markAsPaid(Request $request, Bill $bill)
    {
        if ($request->user()->id !== $bill->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $currentCycle = Carbon::now()->startOfMonth();

        $payment = $bill->payments()->firstOrCreate(
            ['billing_cycle' => $currentCycle],
            ['user_id' => $request->user()->id, 'paid_at' => now()]
        );

        if (!$payment->wasRecentlyCreated) {
            return response()->json(['message' => 'This bill has already been paid for the current cycle.'], 409);
        }

        $bill->load('payments');
        return new BillResource($bill);
    }

    public function destroy(Request $request, Bill $bill)
    {
        if ($request->user()->id !== $bill->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $bill->delete();
        return response()->json(null, 204);
    }
}
