<?php

use App\Agents\FaqBot\AnswerNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Shahzad\AgenticGraph\Execution\ExecutionStatus;
use Shahzad\AgenticGraph\Facades\AgenticGraph;
use Shahzad\AgenticGraph\Jobs\ExecuteGraphJob;
use Shahzad\AgenticGraph\State\State;
use Shahzad\AgenticGraph\Testing\Llm;

uses(RefreshDatabase::class);

it('passes the question to the model as a variable', function () {
    $llm = Llm::fake(['A flex desk is 149 USD per month.']);

    $result = (new AnswerNode)->execute(new State([
        'question' => 'How much is a flex desk?',
    ]));

    expect($result->get('answer'))->toBe('A flex desk is 149 USD per month.');

    $llm->assertPrompted(fn (array $call) => $call['vars']['question'] === 'How much is a flex desk?');
    $llm->assertSentCount(1);
});

it('completes the graph with the answer on state', function () {
    Llm::fake(['Open Monday to Friday, 07:00 to 20:00.']);

    $handle = AgenticGraph::run('faq-bot', new State([
        'question' => 'When do you open?',
    ]));

    expect($handle->status())->toBe(ExecutionStatus::COMPLETED)
        ->and($handle->getFinalState()->get('answer'))->toBe('Open Monday to Friday, 07:00 to 20:00.');
});

it('starts a stream for a valid question', function () {
    Bus::fake();

    $this->post('/ask', ['question' => 'What are your opening hours?'])
        ->assertOk()
        ->assertJsonStructure(['execution_id', 'stream_url'])
        ->assertJsonPath('stream_url', fn (string $url) => str_contains($url, 'execution_id='));

    Bus::assertDispatched(ExecuteGraphJob::class);
});

it('rejects a question that is missing', function () {
    $this->postJson('/ask', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('question');
});

it('shows the question form', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Ask Lantern')
        ->assertSee('id="ask-form"', false);
});
