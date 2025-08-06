<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use OpenAI\Laravel\Facades\OpenAI;

class TransactionAnalysisService
{
    protected string $apiUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('openai.api_base') ?? 'https://api.openai.com/v1/chat/completions';
        $this->apiKey = config('openai.api_key');
    }

    /**
     * Analyze transactions and generate insights using AI
     *
     * @param array $transactions
     * @param string $timePeriod
     * @param string $language
     * @return array
     */
    public function analyzeTransactions(array $transactions, string $timePeriod, string $language = 'en'): array
    {
        // Prepare the prompt with transaction data
        $prompt = $this->preparePrompt($transactions, $timePeriod, $language);

        // Call the AI API
        $response = $this->callAiApi($prompt);

        // Parse and return the insights
        return $this->parseInsights($response);
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
            'model'       => 'qwen/qwen3-235b-a22b-2507',
            'temperature' => 0.7,
            'max_tokens'  => 12000,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a strict financial statement parser. Return only what the user asks.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        $content = $response->choices[0]->message->content ?? '';



        return $response->json();
    }

    /**
     * Parse the AI response and extract insights
     *
     * @param array $response
     * @return array
     */
    protected function parseInsights(array $response): array
    {
        // Extract the content from the AI response
        $content = Arr::get($response, 'choices.0.message.content', '');

        // Parse the XML-like structure from the response
        $insights = [
            'spending_insights' => '',
            'savings_recommendations' => '',
            'budgeting_assistance' => '',
            'financial_health' => '',
        ];

        // Extract each section using regex
        if (preg_match('/<spending_insights>(.*?)<\/spending_insights>/s', $content, $matches)) {
            $insights['spending_insights'] = trim($matches[1]);
        }

        if (preg_match('/<savings_recommendations>(.*?)<\/savings_recommendations>/s', $content, $matches)) {
            $insights['savings_recommendations'] = trim($matches[1]);
        }

        if (preg_match('/<budgeting_assistance>(.*?)<\/budgeting_assistance>/s', $content, $matches)) {
            $insights['budgeting_assistance'] = trim($matches[1]);
        }

        if (preg_match('/<financial_health>(.*?)<\/financial_health>/s', $content, $matches)) {
            $insights['financial_health'] = trim($matches[1]);
        }

        return $insights;
    }
}
