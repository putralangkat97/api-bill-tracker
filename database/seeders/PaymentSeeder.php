<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        // If user doesn't exist, don't do anything.
        if (!$user) {
            return;
        }

        // Clear existing payments for this user to avoid duplicates
        Payment::where('user_id', $user->id)->delete();

        $bills = $user->bills;

        // --- 1. Seed Past Payments ---
        // Create a full payment history for the last 3 months.
        for ($i = 1; $i <= 3; $i++) {
            $billingCycle = Carbon::now()->subMonths($i)->startOfMonth();
            foreach ($bills as $bill) {
                Payment::create([
                    'bill_id' => $bill->id,
                    'user_id' => $user->id,
                    'billing_cycle' => $billingCycle,
                    'paid_at' => $billingCycle->addDays($bill->due_day - 1), // Simulate paying on the due date
                ]);
            }
        }

        // --- 2. Seed Current Month Payments (Partial) ---
        // This makes the dashboard look realistic with some paid and some unpaid bills.
        $currentBillingCycle = Carbon::now()->startOfMonth();

        // Get a few random bills to mark as "paid" for the current month.
        // Let's pay about half of them.
        $billsToPay = $bills->random(ceil($bills->count() / 2));

        foreach ($billsToPay as $bill) {
            Payment::create([
                'bill_id' => $bill->id,
                'user_id' => $user->id,
                'billing_cycle' => $currentBillingCycle,
                'paid_at' => now(),
            ]);
        }
    }
}
