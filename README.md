# Laravel AI agent tutorial: a streaming FAQ bot

A small Laravel app with one question box. A visitor asks about Lantern, a coworking studio, and the answer streams in word by word. The product facts live in the system prompt, so there is no database table for them, no vector store and no retrieval.

This is project 1 of the AgenticGraph tutorials. It is a working app you can run, not a slide deck.

## What you will learn

- A graph with one node: `GraphBuilder`, `addNode`, `setEntryPoint`, `addExitPoint` ([Graphs](https://agenticgraph.dev/docs/graph)).
- A `PrismNode` calling the model through `$this->llm()`, with `system()`, `prompt()` and `vars()` ([Nodes](https://agenticgraph.dev/docs/nodes)).
- Starting a run with `AgenticGraph::stream()` and showing it with `@agenticgraph/stream-client` through Vite ([Streaming](https://agenticgraph.dev/docs/streaming)).

## Prerequisites

- PHP 8.3 or newer, with Composer.
- Node and npm.
- SQLite.
- Ollama, with the model pulled once:

```bash
ollama pull lfm3
```

Ollama must be running on `http://localhost:11434` (the server root, no `/v1`).

Any provider works. Set `AGENTICGRAPH_PRISM_MODEL` to `provider:model` and set that provider's key. The tutorials use a local Ollama model so you need no key and spend nothing.

## Setup

```bash
git clone <this-repository> agenticgraph-tutorial-faq-bot
cd agenticgraph-tutorial-faq-bot

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate

npm install
npm run build
```

The `touch` comes before `migrate`. With SQLite, the file has to exist first.

## Run it

Use two terminals.

Terminal 1, the web server:

```bash
php artisan serve
```

Terminal 2, the queue worker:

```bash
php artisan queue:work --queue=agenticgraph
```

Open http://localhost:8000 and ask:

- "How much is a flex desk?"
- "Can I get a refund after a week?"
- "When do you open on Saturday?"

The answer should appear in pieces. If it appears all at once, see the troubleshooting section.

The worker is required. `AgenticGraph::stream()` is an async run: the web request starts it, the worker executes the graph, and the browser reads the stream from the store.

## How it works

The graph is one node, and that node is both the entry and the exit:

```text
answer (PrismNode) -> end
```

The files:

| File | What it does |
|---|---|
| `app/Agents/FaqBot/AnswerNode.php` | The system prompt with the Lantern facts, and one model call. |
| `app/Agents/FaqBot/FaqBotGraph.php` | Builds and annotates the graph. |
| `app/Http/Controllers/AskController.php` | Validates the question, starts the stream and returns the stream URL. |
| `resources/views/chat.blade.php` | The page, the form and the answer area. |
| `resources/js/app.js` | Connects to the stream and appends `text_delta` events. |
| `config/agenticgraph.php` | Registers `faq-bot` in `graphs`. |

The visitor's question goes into the model through `vars()`:

```php
$answer = $this->llm()
    ->system(self::SYSTEM)
    ->prompt("Answer this visitor question:\n{question}")
    ->vars(['question' => (string) $state->get('question', '')])
    ->text();

return $state->set('answer', $answer);
```

It never goes into the system text or the prompt template. That keeps the question data, not instructions, and the test asserts it.

The start endpoint returns the stream URL:

```php
$handle = AgenticGraph::stream('faq-bot', new State([
    'question' => $validated['question'],
]));

$streamUrl = $handle->stream()->streamUrl;

if ($streamUrl !== null) {
    $streamUrl .= (str_contains($streamUrl, '?') ? '&' : '?')
        .'execution_id='.urlencode($handle->id());
}

return response()->json([
    'execution_id' => $handle->id(),
    'stream_url' => $streamUrl,
]);
```

The `execution_id` at the end of the URL lets a client read the final state if it ever has to reconnect from a cursor the store no longer has.

The page imports the client through Vite, posts the form, connects to the stream URL and appends each `text_delta`:

```js
import { AgenticStream, SSETransport } from '@agenticgraph/stream-client';

const stream = await AgenticStream.connect({
    transport: new SSETransport({ url: start.stream_url }),
});

stream.on('text_delta', (event) => {
    answer.textContent += event.payload.delta;
});
```

Do not use the script-tag build or copy the package into `public/`. The npm package through Vite is the supported path.

## Test it

```bash
php artisan test
```

The suite uses `Llm::fake()` and needs no model, no API key and no Ollama. It checks that:

- The question reaches the model as a variable.
- The graph completes with the answer on state.
- The start endpoint returns a stream URL and dispatches `ExecuteGraphJob`.
- A missing question gets a 422.
- The page renders the form.

## See it in a trace

Tracing is on in `.env.example` with the log exporter. Run one question, then look at the log:

```bash
grep agenticgraph.trace storage/logs/laravel.log
```

The trace contains the `graph` span, the `answer` node span and the `llm` span. It also carries the annotations:

- The graph label, `FAQ bot`.
- The node label, `Answer the question`.
- The outcome headline, `Question answered`.

The headline is a fixed summary. It never contains the visitor's question or the model's answer.

To use a different exporter, set `AGENTICGRAPH_TRACING_EXPORTER` to `file`, `otlp`, `pulse` or `null`. The default `auto` sends traces to your Laravel log when no AgenticPulse key is set.

## Troubleshooting

| Symptom | Fix |
|---|---|
| The page connects but no text arrives | Start the worker: `php artisan queue:work --queue=agenticgraph`. Also check `AGENTICGRAPH_QUEUE_CONNECTION` and `STREAM_STORE_DRIVER`. |
| `MissingModelException` | Set `AGENTICGRAPH_PRISM_MODEL` in `.env`. There is no default. |
| `no such table: agenticgraph_executions` | Run `php artisan migrate`, and create the SQLite file first. |
| The answer is empty | The model may be spending the token budget on thinking. Raise `$maxTokens` in `AnswerNode`, or set `protected ?string $reasoning = 'off';`. |
| A connection error in the browser | The transport retries by itself. If it gives up, submit the question again. |
| The stream URL is public | The package's SSE route has no auth by default. For a real app, set `AGENTICGRAPH_STREAMING_MIDDLEWARE` or sign the URL. |

## Versions

- PHP 8.3+
- Laravel 13
- `agenticgraph/agenticgraph` 0.2.3
- `@agenticgraph/stream-client` 0.1.2

## Next steps

Read the related docs:

- [Graphs](https://agenticgraph.dev/docs/graph)
- [Nodes](https://agenticgraph.dev/docs/nodes)
- [Streaming](https://agenticgraph.dev/docs/streaming)
- [Tracing](https://agenticgraph.dev/docs/tracing)

Then continue with project 2, which turns a messy email into typed data with structured output.
