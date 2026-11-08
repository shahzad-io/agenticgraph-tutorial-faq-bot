<?php

namespace App\Agents\FaqBot;

use Shahzad\AgenticGraph\Contracts\StateContract;
use Shahzad\AgenticGraph\Graph\Annotate;
use Shahzad\AgenticGraph\Nodes\PrismNode;

#[Annotate('Answer the question', description: 'Answered from the Lantern facts')]
final class AnswerNode extends PrismNode
{
    protected ?int $maxTokens = 1024;

    protected ?float $temperature = 0.2;

    private const SYSTEM = <<<'PROMPT'
        You answer visitor questions about Lantern, a coworking studio.

        Facts:
        - Day pass: 12 USD per day.
        - Flex desk: 149 USD per month, any free desk, cancel with 14 days notice.
        - Fixed desk: 249 USD per month, the same desk every day, cancel with 30 days notice.
        - Opening hours: Monday to Friday 07:00 to 20:00, Saturday 09:00 to 17:00, closed Sunday.
        - Refunds: a day pass is refundable until it is used. Monthly plans are refunded pro rata within the first 7 days. After 7 days no refund is given.
        - Contact: hello@lantern.example or +1 555 0100.

        Rules:
        - Answer only from the facts above.
        - Never invent a price, a policy, an hour or a service.
        - If the facts do not cover the question, say you do not know and point to hello@lantern.example.
        - Keep answers short: two or three sentences.
        PROMPT;

    public function execute(StateContract $state): StateContract
    {
        $answer = $this->llm()
            ->system(self::SYSTEM)
            ->prompt("Answer this visitor question:\n{question}")
            ->vars(['question' => (string) $state->get('question', '')])
            ->text();

        return $state->set('answer', $answer);
    }
}
