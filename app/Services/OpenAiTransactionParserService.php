<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class OpenAiTransactionParserService
{
    private const PROMPT_TEMPLATE = <<<'PROMPT'
            You are an AI assistant tasked with extracting transaction information from a PDF document, which could be a statement, invoice, or receipt. Your goal is to create a comprehensive list of JSON objects representing all the transactions found in the document. Here is the content extracted from the PDF:

            <pdf_content>
            {{PDF_CONTENT}}
            </pdf_content>

            Please follow these instructions to complete the task:

            1. Determine if the document is a statement, invoice, or receipt based on its content and structure.

            2. Extract the following information for each transaction:
               - date (date with time if available, otherwise just the date)
               - description
               - amount
               - currency
               - category (you will determine this based on the description)

            3. Pay special attention to statements:
               - Statements typically contain multiple transactions. It's crucial that you identify and process EVERY single transaction listed in the statement.
               - Carefully examine the entire document for any transaction-like entries, including those that might be formatted differently or appear in unexpected sections.
               - Be aware that date formats may vary within the same document. Adapt your parsing accordingly.
               - Ensure that credits and debits are correctly represented in the amount field.
               - For any amount that appears as a negative value, convert it to its positive numeric equivalent (e.g., "-1875000.00" should become "1875000.00").

            4. For invoices and receipts:
               - These typically contain only one main transaction, but be alert for any additional charges or fees listed separately.
               - Use the invoice or receipt date as the transaction date if no specific transaction date is provided.
               - The transaction amount is usually the total amount due or paid, but verify if there are any itemized charges that should be treated as separate transactions.

            5. When determining the category, use the following guidelines:
               - Income: Salary payments, refunds, reimbursements
               - Housing & Utilities: Rent, mortgage, property taxes, phone, internet, electricity
               - Food & Groceries: Restaurants, groceries, food delivery
               - Transport & Travel: Gas, public transit, ride‑sharing, flights, hotels, car rentals
               - Health & Insurance: Medical expenses, gym memberships, all insurance premiums
               - Shopping & Lifestyle: Retail purchases, online shopping, entertainment, subscriptions, gifts & donations
               - Savings & Investing: Transfers to savings, stock purchases, mutual funds, retirement contributions
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
                "category": "Food & Groceries"
              },
              {
                "date": "2023-05-16",
                "description": "Gas Station",
                "amount": 45.00,
                "currency": "USD",
                "category": "Transport & Travel"
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

        $freeModels = [
            'qwen/qwen3-235b-a22b-2507:free',
            'qwen/qwen3-235b-a22b:free',
            'moonshotai/kimi-k2:free',
            'z-ai/glm-4.5-air:free',
            'deepseek/deepseek-r1-0528-qwen3-8b:free',
            'deepseek/deepseek-r1-0528:free'
        ];

        $response = OpenAI::chat()->create([
            'model'       => 'qwen/qwen3-235b-a22b-2507',
            'temperature' => 0,
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

        if (! preg_match('/<transactions>\s*(.*?)\s*<\/transactions>/s', $content, $m)) {
            return [];
        }

        return json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
