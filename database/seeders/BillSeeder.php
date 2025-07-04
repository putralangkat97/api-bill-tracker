<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        // Clear existing bills for this user to avoid duplicates on re-seeding
        $user->bills()->delete();

        // Define a list of standard bills
        $billsData = [
            ['name' => 'Rent House', 'amount' => 1250.00, 'due_day' => 1],
            ['name' => 'Google One Subscription', 'amount' => 2.99, 'due_day' => 3],
            ['name' => 'My Server Bill', 'amount' => 20.00, 'due_day' => 15],
            ['name' => 'Wi-Fi Bill', 'amount' => 59.99, 'due_day' => 26],
            ['name' => 'Electricity', 'amount' => 75.50, 'due_day' => 28],
            ['name' => 'Phone Balance', 'amount' => 30.00, 'due_day' => 28],
        ];

        // Create a bill for each item in the array
        foreach ($billsData as $bill) {
            Bill::create([
                'user_id' => $user->id,
                'name' => $bill['name'],
                'amount' => $bill['amount'],
                'due_day' => $bill['due_day'],
            ]);
        }
    }
}
