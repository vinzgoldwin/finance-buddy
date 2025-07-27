<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class OpenAiTransactionParserService
{
    private const PROMPT_TEMPLATE = <<<'PROMPT'
            You are tasked with extracting transaction information from a PDF document, which could be a statement, invoice, or receipt. Your goal is to create a list of JSON objects representing the transactions found in the document. Here is the content extracted from the PDF:

            <pdf_content>
            {{PDF_CONTENT}}
            </pdf_content>

            Please follow these instructions to complete the task:

            1. Analyze the content to determine if it's a statement, invoice, or receipt.

            2. Extract the following information for each transaction:
               - date (date with time)
               - description
               - amount
               - currency
               - category (you will determine this based on the description)

            3. For statements:
               - Look for multiple transactions and process each one separately.
               - Pay attention to date formats, which may vary.
               - Ensure that credits and debits are correctly represented in the amount field.
               - Convert any negative amount to its positive numeric value (e.g., “-1875000.00” ⇒ “1875000.00”).

            4. For invoices and receipts:
               - These typically contain only one transaction.
               - The transaction date is usually the invoice or receipt date.
               - The amount is typically the total amount due or paid.

            5. When determining the category, use the following guidelines:
               - Food & Dining: Restaurants, groceries, food delivery
               - Shopping: Retail purchases, online shopping
               - Transportation: Gas, public transit, ride-sharing
               - Bills & Utilities: Phone, internet, electricity
               - Entertainment: Movies, concerts, streaming services
               - Travel: Hotels, flights, car rentals
               - Health & Fitness: Medical expenses, gym memberships
               - Housing: Rent, mortgage, property taxes
               - Insurance: Health, auto, life insurance premiums
               - Education: Tuition, school fees, educational materials
               - Savings & Investments: Stock purchases, mutual funds, transfers to savings
               - Income: Salary payments, refunds, reimbursements
               - Fees & Charges: Bank fees, late fees, interest charges
               - Gifts & Donations: Charitable donations, personal gifts
               - Subscriptions: Recurring digital or physical services (e.g., SaaS, streaming)
               - Transfers: Bank transfers, peer-to-peer payments, internal account moves
               - Other: Any transactions that don't fit the above categories

            6. If you encounter any ambiguous or missing information:
               - Use "Unknown" for missing dates or descriptions.
               - Use 0 for missing amounts.
               - Use "Unknown" for missing currencies.
               - Use "Other" for categories you can't confidently determine.

            7. Format your output as a list of JSON objects, with each object representing a single transaction. Enclose the entire list in <transactions> tags.

            Here's an example of how your output should be formatted:

            <transactions>
            [
              {
                "date": "2025-07-26",
                "description": "Grocery Store Purchase",
                "amount": 56.78,
                "currency": "USD",
                "category": "Food & Dining"
              },
              {
                "date": "2023-05-16",
                "description": "Gas Station",
                "amount": 45.00,
                "currency": "USD",
                "category": "Transportation"
              }
            ]
            </transactions>

            Please process the provided PDF content and generate the list of transactions in the specified format.
        PROMPT;

    /**
     * Parse transactions from the raw PDF text.
     *
     * @throws \JsonException
     */
    public function parse(string $pdfText): array
    {
        $prompt = str_replace('{{PDF_CONTENT}}', $pdfText, self::PROMPT_TEMPLATE);

        $response = OpenAI::chat()->create([
            'model'       => 'together_ai/Qwen/Qwen3-235B-A22B-Instruct-2507-tput',
            'temperature' => 0,
            'max_tokens'  => 2048,
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

        if (! preg_match('/<transactions>\s*(.*?)\s*<\/transactions>/s', $content, $m)) {
            return [];
        }

        return json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
