<?php

namespace App\Agents\FaqBot;

use Shahzad\AgenticGraph\Graph\Graph;
use Shahzad\AgenticGraph\Graph\GraphBuilder;

final class FaqBotGraph
{
    public static function build(): Graph
    {
        return GraphBuilder::create('faq-bot')
            ->label('FAQ bot')
            ->description('Answers questions about Lantern from its product facts')
            ->addNode('answer', AnswerNode::class)
            ->setEntryPoint('answer')
            ->addExitPoint('answer')
            ->outcome('Question answered')
            ->build();
    }
}
