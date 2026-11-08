<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-950 text-stone-100 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-2xl flex-col justify-center gap-8 px-6 py-16">
        <header class="space-y-3">
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-amber-400">AgenticGraph tutorial</p>
            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Ask Lantern</h1>
            <p class="max-w-prose text-sm leading-6 text-stone-400">
                Questions about pricing, refunds and opening hours. The answer streams in as the graph runs.
            </p>
        </header>

        <form
            id="ask-form"
            action="{{ route('ask') }}"
            method="POST"
            class="space-y-4 rounded-2xl border border-stone-800 bg-stone-900/60 p-5 shadow-2xl shadow-black/30"
        >
            @csrf

            <label for="question" class="block text-sm font-medium text-stone-300">Your question</label>

            <textarea
                id="question"
                name="question"
                rows="3"
                maxlength="500"
                required
                placeholder="How much is a flex desk?"
                class="w-full resize-none rounded-xl border border-stone-700 bg-stone-950 px-4 py-3 text-base text-stone-100 placeholder:text-stone-600 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/30"
            ></textarea>

            <div class="flex items-center justify-between gap-4">
                <span class="text-xs text-stone-500">Lantern staff answer from a fixed set of facts.</span>

                <button
                    id="ask-submit"
                    type="submit"
                    class="inline-flex items-center rounded-full bg-amber-400 px-5 py-2.5 text-sm font-semibold text-stone-950 transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Ask
                </button>
            </div>
        </form>

        <section class="min-h-40 space-y-3 rounded-2xl border border-stone-800 bg-stone-900/40 p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-medium uppercase tracking-[0.2em] text-stone-500">Answer</h2>
                <span id="status" class="text-xs text-stone-500">idle</span>
            </div>

            <p id="answer" aria-live="polite" class="whitespace-pre-wrap text-base leading-7 text-stone-100"></p>
            <p id="notice" role="alert" class="text-sm text-rose-400"></p>
        </section>
    </main>
</body>
</html>
