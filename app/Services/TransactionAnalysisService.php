<?php

namespace App\Services;

use App\Models\FinancialInsight;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use OpenAI\Laravel\Facades\OpenAI;

class TransactionAnalysisService
{
    /**
     * Analyze transactions and generate insights using AI
     *
     * @param array $transactions
     * @param string $timePeriod
     * @param string $language
     * @return array
     */
    public function analyzeTransactions(array $transactions, string $timePeriod, string $language = 'en', ?int $userId = null, ?string $periodStart = null, ?string $periodEnd = null): array
    {
        // Prepare the prompt with transaction data
        $prompt = $this->preparePrompt($transactions, $timePeriod, $language);

        // Call the AI API
        $response = $this->callAiApi($prompt);

        // Parse
        $parsed = $this->parseAndPersistInsights($response, $userId, $periodStart, $periodEnd);

        return $parsed;
    }

    /**
     * Prepare the prompt for the AI API
     *
     * @param array $transactions
     * @param string $timePeriod
     * @param string $language
     * @return string
     */
    protected function preparePrompt(array $transactions, string $timePeriod, string $language): string
    {
        // Format transactions as XML
        $transactionsXml = "<transactions>\n";
        foreach ($transactions as $transaction) {
            $transactionsXml .= "  <transaction>\n";
            $transactionsXml .= "    <date>{$transaction['date']}</date>\n";
            $transactionsXml .= "    <description>{$transaction['description']}</description>\n";
            $transactionsXml .= "    <amount>{$transaction['amount']}</amount>\n";
            $transactionsXml .= "    <currency>{$transaction['currency']}</currency>\n";
            $transactionsXml .= "    <category>{$transaction['category']}</category>\n";
            $transactionsXml .= "  </transaction>\n";
        }
        $transactionsXml .= "</transactions>";

        // Load the prompt template
        $promptTemplate = <<<PROMPT
            You are a personal finance assistant tasked with providing insights based on a user's financial transactions. You will be given a list of transactions, a time period, and a language preference. Your goal is to analyze this data and provide valuable insights in the specified language using an informal tone.

            First, process the input data:

            $transactionsXml

            Time period: $timePeriod
            Language: $language

            Set the language for your response based on the {$language} parameter. If {$language} is "id", respond in Indonesian. If it's "en", respond in English. Use an informal, conversational tone in your response.

            Analyze the transactions and generate insights in the following areas:

            1. Spending Insights
               - Identify any unusual spending patterns or outliers
               - Suggest categories where the user could potentially reduce spending
               - Analyze how spending habits have changed over the given time period

            2. Savings Recommendations
               - Calculate the user's savings rate and compare it to recommended benchmarks
               - Suggest automated savings amounts based on the observed spending patterns
               - Provide advice on building or maintaining an emergency fund

            3. Budgeting Assistance
               - Compare actual spending to typical budget allocations
               - Recommend budget allocations based on the historical data
               - Identify any upcoming large expenses and suggest planning strategies

            4. Financial Health Scoring
               - Provide an overall assessment of the user's financial situation
               - Offer actionable steps to improve financial health
               - Identify potential financial risks based on the observed patterns

            5. Period Summary
                Produce a concise JSON object with the fields below—**no commentary, strictly JSON**—so it can be fed to the model next time instead of raw transactions:
                   ```json
                   {
                     "period_start": "<YYYY-MM-DD>",
                     "period_end": "<YYYY-MM-DD>",
                     "total_income": <number>,
                     "total_expense": <number>,
                     "net_balance": <number>,
                     "savings_rate_pct": <number>,          // positive = saved, negative = overspent
                     "top_expense_categories": [
                       { "category": "<name>", "amount": <number> },
                       ...
                     ],
                     "largest_single_transaction": {
                       "date": "<YYYY-MM-DD>",
                       "description": "<trimmed>",
                       "amount": <number>
                     }
                   }
                    ```

            For each insight, provide a brief explanation of your reasoning. Be specific and use actual numbers from the transaction data where relevant.

            Format your response using the following structure:

            <insights>
                <spending_insights>
                (Include your analysis and recommendations here)
                </spending_insights>

                <savings_recommendations>
                (Include your analysis and recommendations here)
                </savings_recommendations>

                <budgeting_assistance>
                (Include your analysis and recommendations here)
                </budgeting_assistance>

                <financial_health>
                (Include your analysis and recommendations here)
                </financial_health>
            </insights>

             <period_summary>
             (paste the JSON from Task 5 here)
             </period_summary>

            Remember to maintain an informal, friendly tone throughout your response, as if you're chatting with a friend about their finances.
        PROMPT;

        return $promptTemplate;
    }

    /**
     * Call the AI API with the prepared prompt
     *
     * @param string $prompt
     * @return array
     */
    protected function callAiApi(string $prompt): array
    {
        $response = OpenAI::chat()->create([
            'model'       => 'openai/gpt-oss-120b',
            'temperature' => 0.1,
            'max_tokens'  => 2500,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a personal finance assistant tasked with providing insights based on a user\'s financial transactions',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'reasoning' => [
                'exclude' => true,
            ],
        ]);

        return $response->toArray();
    }

/**
     * Parse the AI response and persist insights into DB
     *
     * @param array $response
     * @param int|null $userId
     * @param string|null $periodStart
     * @param string|null $periodEnd
     * @return array
     */
    protected function parseAndPersistInsights(array $response, ?int $userId, ?string $periodStart, ?string $periodEnd): array
    {
        $content = Arr::get($response, 'choices.0.message.content', '');

        $extract = function (string $tag) use ($content): ?string {
            if (preg_match('/<' . $tag . '>(.*?)<\/' . $tag . '>/s', $content, $m)) {
                return trim($m[1]);
            }
            return null;
        };

        $spending = $extract('spending_insights');
        $savings = $extract('savings_recommendations');
        $budgeting = $extract('budgeting_assistance');
        $health = $extract('financial_health');

        $periodSummaryJson = $extract('period_summary');
        $periodData = [];
        if ($periodSummaryJson) {
            $periodSummaryJson = trim($periodSummaryJson);
            // In case there is extra markup/noise, attempt to find the JSON object
            if (preg_match('/\{[\s\S]*\}/', $periodSummaryJson, $mJson)) {
                $periodSummaryJson = $mJson[0];
            }
            $periodData = json_decode($periodSummaryJson, true) ?: [];
        }

        $userId = $userId ?? auth()->id();
        $periodStart = $periodStart ?? ($periodData['period_start'] ?? null);
        $periodEnd = $periodEnd ?? ($periodData['period_end'] ?? null);

        // persist
        return $this->persistInsights([
            'user_id' => $userId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ], [
            'spending_insights' => $spending,
            'savings_recommendations' => $savings,
            'budgeting_assistance' => $budgeting,
            'financial_health' => $health,
            'period_summary' => $periodData,
        ]);
    }

    /**
     * Persist insights and return structured payload
     */
    protected function persistInsights(array $meta, array $data): array
    {
        \DB::beginTransaction();
        try {
            $version = FinancialInsight::where('user_id', $meta['user_id'])
                ->where('period_start', $meta['period_start'])
                ->where('period_end', $meta['period_end'])
                ->max('version');
            $version = $version ? ($version + 1) : 1;

            $fi = FinancialInsight::create([
                'user_id' => $meta['user_id'],
                'period_start' => $meta['period_start'],
                'period_end' => $meta['period_end'],
                'version' => $version,
            ]);

            if (!empty($data['spending_insights'])) {
                $fi->spendingInsights()->create(['content' => $data['spending_insights']]);
            }
            if (!empty($data['savings_recommendations'])) {
                $fi->savingsRecommendations()->create(['content' => $data['savings_recommendations']]);
            }
            if (!empty($data['budgeting_assistance'])) {
                $fi->budgetingAssistances()->create(['content' => $data['budgeting_assistance']]);
            }
            if (!empty($data['financial_health'])) {
                $fi->financialHealths()->create(['content' => $data['financial_health']]);
            }

            if (!empty($data['period_summary'])) {
                $psData = $data['period_summary'];
                $ps = $fi->periodSummary()->create([
                    'period_start' => $psData['period_start'] ?? $meta['period_start'],
                    'period_end' => $psData['period_end'] ?? $meta['period_end'],
                    'total_income' => $psData['total_income'] ?? 0,
                    'total_expense' => $psData['total_expense'] ?? 0,
                    'net_balance' => $psData['net_balance'] ?? 0,
                    'savings_rate_pct' => $psData['savings_rate_pct'] ?? null,
                    'largest_tx_date' => $psData['largest_single_transaction']['date'] ?? null,
                    'largest_tx_description' => $psData['largest_single_transaction']['description'] ?? null,
                    'largest_tx_amount' => $psData['largest_single_transaction']['amount'] ?? null,
                ]);

                if (!empty($psData['top_expense_categories']) && is_array($psData['top_expense_categories'])) {
                    foreach ($psData['top_expense_categories'] as $idx => $row) {
                        $ps->topCategories()->create([
                            'category' => $row['category'] ?? 'Unknown',
                            'amount' => $row['amount'] ?? 0,
                            'rank' => $idx + 1,
                        ]);
                    }
                }
            }

            \DB::commit();

            return [
                'id' => $fi->id,
                'version' => $fi->version,
                'period_start' => $fi->period_start->format('Y-m-d'),
                'period_end' => $fi->period_end->format('Y-m-d'),
            ];
        } catch (\Throwable $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}
