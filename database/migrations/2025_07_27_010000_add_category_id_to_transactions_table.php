<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('currency')->constrained()->nullOnDelete();
        });

        $transactions = DB::table('transactions')->get(['id', 'category']);
        foreach ($transactions as $transaction) {
            if ($transaction->category === null) {
                continue;
            }

            $category = DB::table('categories')->where('name', $transaction->category)->first();
            if ($category) {
                DB::table('transactions')->where('id', $transaction->id)->update([
                    'category_id' => $category->id,
                ]);
            }
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('category')->nullable()->after('currency');
        });

        $transactions = DB::table('transactions')->get(['id', 'category_id']);
        foreach ($transactions as $transaction) {
            if ($transaction->category_id === null) {
                continue;
            }

            $category = DB::table('categories')->where('id', $transaction->category_id)->first();
            if ($category) {
                DB::table('transactions')->where('id', $transaction->id)->update([
                    'category' => $category->name,
                ]);
            }
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
