import { AgenticStream, SSETransport } from '@agenticgraph/stream-client';

const form = document.getElementById('ask-form');

if (form) {
    const answer = document.getElementById('answer');
    const status = document.getElementById('status');
    const notice = document.getElementById('notice');
    const submit = document.getElementById('ask-submit');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        answer.textContent = '';
        notice.textContent = '';
        status.textContent = 'thinking';
        submit.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                notice.textContent = 'Enter a question of up to 500 characters.';
                status.textContent = 'idle';
                submit.disabled = false;

                return;
            }

            const start = await response.json();

            const stream = await AgenticStream.connect({
                transport: new SSETransport({ url: start.stream_url }),
            });

            stream.on('text_delta', (event) => {
                answer.textContent += event.payload.delta;
            });

            stream.onStatusChange((next) => {
                status.textContent = next;

                if (next === 'complete' || next === 'error') {
                    submit.disabled = false;
                }

                if (next === 'error') {
                    notice.textContent = stream.error?.message ?? 'The answer could not be completed.';
                }
            });
        } catch (error) {
            notice.textContent = 'The answer could not be started. Try again.';
            status.textContent = 'idle';
            submit.disabled = false;
        }
    });
}
