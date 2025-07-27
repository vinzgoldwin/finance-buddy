<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Food & Dining', 'description' => 'Restaurants, groceries, food delivery'],
            ['name' => 'Shopping', 'description' => 'Retail purchases, online shopping'],
            ['name' => 'Transportation', 'description' => 'Gas, public transit, ride-sharing'],
            ['name' => 'Bills & Utilities', 'description' => 'Phone, internet, electricity'],
            ['name' => 'Entertainment', 'description' => 'Movies, concerts, streaming services'],
            ['name' => 'Travel', 'description' => 'Hotels, flights, car rentals'],
            ['name' => 'Health & Fitness', 'description' => 'Medical expenses, gym memberships'],
            ['name' => 'Housing', 'description' => 'Rent, mortgage, property taxes'],
            ['name' => 'Insurance', 'description' => 'Health, auto, life insurance premiums'],
            ['name' => 'Education', 'description' => 'Tuition, school fees, educational materials'],
            ['name' => 'Savings & Investments', 'description' => 'Stock purchases, mutual funds, transfers to savings'],
            ['name' => 'Income', 'description' => 'Salary payments, refunds, reimbursements'],
            ['name' => 'Fees & Charges', 'description' => 'Bank fees, late fees, interest charges'],
            ['name' => 'Gifts & Donations', 'description' => 'Charitable donations, personal gifts'],
            ['name' => 'Subscriptions', 'description' => 'Recurring digital or physical services (e.g., SaaS, streaming)'],
            ['name' => 'Transfers', 'description' => 'Bank transfers, peer-to-peer payments, internal account moves'],
            ['name' => 'Other', 'description' => "Any transactions that don't fit the above categories"],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
