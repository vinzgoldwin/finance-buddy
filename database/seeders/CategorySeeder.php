<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Income',               'description' => 'Salary payments, refunds, reimbursements'],
            ['name' => 'Housing & Utilities',  'description' => 'Rent, mortgage, property taxes, phone, internet, electricity'],
            ['name' => 'Food & Groceries',     'description' => 'Restaurants, groceries, food delivery'],
            ['name' => 'Transport & Travel',   'description' => 'Gas, public transit, ride‑sharing, flights, hotels, car rentals'],
            ['name' => 'Health & Insurance',   'description' => 'Medical expenses, gym memberships, all insurance premiums'],
            ['name' => 'Shopping & Lifestyle', 'description' => 'Retail purchases, online shopping, entertainment, subscriptions, gifts & donations'],
            ['name' => 'Savings & Investing',  'description' => 'Transfers to savings, stock purchases, mutual funds, retirement contributions'],
            ['name' => 'Other',                'description' => "Any transactions that don't fit the above categories"],
        ];


        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
