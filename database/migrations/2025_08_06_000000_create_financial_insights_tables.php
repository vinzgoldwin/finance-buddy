<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['user_id', 'period_start', 'period_end']);
        });

        Schema::create('spending_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_insight_id')->constrained('financial_insights')->cascadeOnDelete();
            $table->text('content'); // structured text (e.g., markdown/HTML-less, sentences)
            $table->timestamps();
        });

        Schema::create('savings_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_insight_id')->constrained('financial_insights')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('budgeting_assistances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_insight_id')->constrained('financial_insights')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('financial_healths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_insight_id')->constrained('financial_insights')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('period_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_insight_id')->constrained('financial_insights')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->bigInteger('total_income')->default(0);
            $table->bigInteger('total_expense')->default(0);
            $table->bigInteger('net_balance')->default(0);
            $table->decimal('savings_rate_pct', 8, 3)->nullable();
            // top categories stored in a join table for structure
            $table->date('largest_tx_date')->nullable();
            $table->string('largest_tx_description')->nullable();
            $table->bigInteger('largest_tx_amount')->nullable();
            $table->timestamps();
        });

        Schema::create('period_summary_top_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_summary_id')->constrained('period_summaries')->cascadeOnDelete();
            $table->string('category');
            $table->bigInteger('amount');
            $table->unsignedInteger('rank')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_summary_top_categories');
        Schema::dropIfExists('period_summaries');
        Schema::dropIfExists('financial_healths');
        Schema::dropIfExists('budgeting_assistances');
        Schema::dropIfExists('savings_recommendations');
        Schema::dropIfExists('spending_insights');
        Schema::dropIfExists('financial_insights');
    }
};
