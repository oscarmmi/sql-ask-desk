<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

type QueryResult = {
    id: number;
    question: string;
    sql_text: string | null;
    row_count: number;
    status: 'ok' | 'rejected' | 'failed';
    rows: Record<string, string | number | null>[];
};

type RecentQuery = Omit<QueryResult, 'rows'> & {
    created_at: string;
    preview_json: Record<string, string | number | null>[] | null;
};

const props = defineProps<{ recentQueries: RecentQuery[] }>();

const examples = [
    'Show me the top products by price',
    'Which products are running low on stock?',
    'List the latest orders and their status',
    'Show products in the Audio category',
];

const question = ref('');
const result = ref<QueryResult | null>(null);
const recentQueries = ref([...props.recentQueries]);
const loading = ref(false);
const error = ref('');
const copied = ref(false);

const columns = computed(() => Object.keys(result.value?.rows[0] ?? {}));

function csrfToken(): string {
    const token = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='));
    return token ? decodeURIComponent(token.slice('XSRF-TOKEN='.length)) : '';
}

async function ask(): Promise<void> {
    if (!question.value.trim() || loading.value) return;

    loading.value = true;
    error.value = '';
    result.value = null;

    try {
        const response = await fetch('/ask', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ question: question.value.trim() }),
        });
        const payload = await response.json();

        if (payload.query) {
            result.value = payload.query as QueryResult;
            recentQueries.value = [
                {
                    ...payload.query,
                    preview_json: payload.query.rows ?? [],
                    created_at: new Date().toISOString(),
                },
                ...recentQueries.value.filter(
                    (item) => item.id !== payload.query.id,
                ),
            ].slice(0, 5);
        }

        if (!response.ok) {
            error.value =
                payload.message ?? 'Something went wrong. Please try again.';
        }
    } catch {
        error.value =
            'Could not reach the SQL desk. Check your connection and try again.';
    } finally {
        loading.value = false;
    }
}

function useExample(example: string): void {
    question.value = example;
}

async function copySql(): Promise<void> {
    if (!result.value?.sql_text) return;
    await navigator.clipboard.writeText(result.value.sql_text);
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 1600);
}

function restoreQuery(query: RecentQuery): void {
    question.value = query.question;
    result.value = { ...query, rows: query.preview_json ?? [] };
    error.value =
        query.status === 'rejected'
            ? 'This SQL was rejected by the read-only guard.'
            : query.status === 'failed'
              ? 'This request could not be completed.'
              : '';
}
</script>

<template>
    <Head title="SQL Ask Desk">
        <meta
            name="description"
            content="Ask a sample SQLite database questions in plain English. Inspect every generated query and its guarded results."
        />
    </Head>

    <div class="min-h-screen bg-[#f7f8fa] text-slate-900 lg:flex">
        <aside
            class="flex w-full shrink-0 flex-col border-b border-slate-200 bg-white lg:min-h-screen lg:w-[258px] lg:border-r lg:border-b-0"
        >
            <a
                href="/"
                class="flex h-[76px] items-center gap-3 border-b border-slate-100 px-6"
            >
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm shadow-emerald-900/15"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true"
                    >
                        <ellipse cx="12" cy="5" rx="8" ry="3" />
                        <path
                            d="M4 5v7c0 1.66 3.58 3 8 3s8-1.34 8-3V5M4 12v7c0 1.66 3.58 3 8 3 2.13 0 4.06-.34 5.5-.9"
                        />
                        <path d="m16 18 2 2 4-4" />
                    </svg>
                </span>
                <span class="text-[15px] font-semibold tracking-[-0.03em]"
                    >SQL Ask Desk</span
                >
            </a>

            <div class="hidden flex-1 px-4 py-7 lg:block">
                <p
                    class="px-3 text-[10px] font-bold tracking-[0.16em] text-slate-400 uppercase"
                >
                    Workspace
                </p>
                <div
                    class="mt-3 flex items-center gap-3 rounded-xl bg-emerald-50 px-3 py-3 text-sm font-medium text-emerald-800"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-emerald-700 shadow-sm"
                    >
                        <svg
                            viewBox="0 0 20 20"
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.6"
                            aria-hidden="true"
                        >
                            <path d="M3 4.5h14v11H3zM3 8h14M7 4.5v11" />
                        </svg>
                    </span>
                    Ask your data
                    <span
                        class="ml-auto h-1.5 w-1.5 rounded-full bg-emerald-500"
                    ></span>
                </div>

                <div class="mt-9 px-3">
                    <p
                        class="text-[10px] font-bold tracking-[0.16em] text-slate-400 uppercase"
                    >
                        Connected database
                    </p>
                    <div class="mt-4 flex items-center gap-3">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600"
                        >
                            <svg
                                viewBox="0 0 20 20"
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                                aria-hidden="true"
                            >
                                <ellipse cx="10" cy="4" rx="6.5" ry="2.5" />
                                <path
                                    d="M3.5 4v5.5C3.5 10.9 6.4 12 10 12s6.5-1.1 6.5-2.5V4m-13 5.5V15c0 1.4 2.9 2.5 6.5 2.5s6.5-1.1 6.5-2.5V9.5"
                                />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-semibold">
                                Northwind Gadgets
                            </p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                SQLite · sample data
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-9 px-3">
                    <div class="flex items-center justify-between">
                        <p
                            class="text-[10px] font-bold tracking-[0.16em] text-slate-400 uppercase"
                        >
                            Recent questions
                        </p>
                        <span class="text-[10px] font-medium text-slate-400">{{
                            recentQueries.length
                        }}</span>
                    </div>
                    <div v-if="recentQueries.length" class="mt-3 space-y-1">
                        <button
                            v-for="item in recentQueries"
                            :key="item.id"
                            type="button"
                            class="group flex w-full items-start gap-2.5 rounded-lg px-2.5 py-2 text-left text-xs leading-5 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
                            @click="restoreQuery(item)"
                        >
                            <svg
                                class="mt-1 h-3.5 w-3.5 shrink-0 text-slate-400 group-hover:text-emerald-600"
                                viewBox="0 0 16 16"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.4"
                                aria-hidden="true"
                            >
                                <path d="M8 3v5l3 2" />
                                <circle cx="8" cy="8" r="6" />
                            </svg>
                            <span class="line-clamp-2">{{
                                item.question
                            }}</span>
                        </button>
                    </div>
                    <p
                        v-else
                        class="mt-3 px-2.5 text-xs leading-5 text-slate-400"
                    >
                        Your recent questions will show up here.
                    </p>
                </div>
            </div>

            <div class="hidden border-t border-slate-100 p-5 lg:block">
                <div
                    class="flex items-center gap-2 text-xs font-medium text-slate-500"
                >
                    <span class="relative flex h-2 w-2"
                        ><span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-30"
                        ></span
                        ><span
                            class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                        ></span
                    ></span>
                    Read-only demo environment
                </div>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <header
                class="flex h-[76px] items-center justify-between border-b border-slate-200/80 bg-white/80 px-5 sm:px-8 lg:px-10"
            >
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span class="hidden sm:inline">Workspace</span
                    ><span class="hidden sm:inline">/</span
                    ><span class="font-medium text-slate-700"
                        >Ask your data</span
                    >
                </div>
                <div
                    class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-600"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                    ></span>
                    Demo database
                </div>
            </header>

            <div
                class="mx-auto max-w-[1100px] px-5 py-9 sm:px-8 sm:py-12 lg:px-10 lg:py-14"
            >
                <div class="mx-auto max-w-[760px]">
                    <div class="mb-8 text-center">
                        <div
                            class="mx-auto mb-4 flex h-11 w-11 items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50 text-emerald-700"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                aria-hidden="true"
                            >
                                <path
                                    d="M4 5h16M4 10h10M4 15h7M16 14v6m-3-3h6"
                                />
                            </svg>
                        </div>
                        <p
                            class="text-[11px] font-bold tracking-[0.18em] text-emerald-700 uppercase"
                        >
                            Your data, in plain English
                        </p>
                        <h1
                            class="mt-3 text-3xl font-semibold tracking-[-0.045em] text-slate-950 sm:text-[38px]"
                        >
                            Ask your database anything.
                        </h1>
                        <p
                            class="mx-auto mt-3 max-w-[520px] text-sm leading-6 text-slate-500"
                        >
                            Explore Northwind Gadgets with a question. Review
                            the SQL, then see exactly what it returns.
                        </p>
                    </div>

                    <form
                        class="rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_10px_35px_-25px_rgba(15,23,42,0.32)] transition focus-within:border-emerald-300 focus-within:shadow-[0_12px_40px_-24px_rgba(5,150,105,0.32)]"
                        @submit.prevent="ask"
                    >
                        <label for="question" class="sr-only"
                            >Ask a question about the sample database</label
                        >
                        <textarea
                            id="question"
                            v-model="question"
                            rows="3"
                            maxlength="500"
                            class="block w-full resize-none border-0 bg-transparent px-4 pt-3 text-[15px] leading-6 text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0"
                            placeholder="e.g. Which products are running low on stock?"
                            @keydown.meta.enter.prevent="ask"
                            @keydown.ctrl.enter.prevent="ask"
                        ></textarea>
                        <div
                            class="flex items-center justify-between gap-3 border-t border-slate-100 px-2 pt-2"
                        >
                            <span class="pl-2 text-[11px] text-slate-400"
                                >{{ question.length }}/500
                                <span class="hidden sm:inline"
                                    >· Press Ctrl + Enter to run</span
                                ></span
                            >
                            <button
                                type="submit"
                                :disabled="loading || !question.trim()"
                                class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-600 px-4 text-[13px] font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-100 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    v-if="loading"
                                    class="h-4 w-4 animate-spin"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="9"
                                        stroke="currentColor"
                                        stroke-width="3"
                                    />
                                    <path
                                        class="opacity-75"
                                        d="M21 12a9 9 0 0 0-9-9"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="h-4 w-4"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    aria-hidden="true"
                                >
                                    <path d="M3 10h13m-5-5 5 5-5 5" />
                                </svg>
                                {{ loading ? 'Thinking…' : 'Ask question' }}
                            </button>
                        </div>
                    </form>

                    <div
                        class="mt-4 flex flex-wrap items-center justify-center gap-2"
                    >
                        <span
                            class="mr-1 text-[11px] font-medium text-slate-400"
                            >Try asking</span
                        >
                        <button
                            v-for="example in examples"
                            :key="example"
                            type="button"
                            class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800"
                            @click="useExample(example)"
                        >
                            {{ example }}
                        </button>
                    </div>

                    <div
                        v-if="error"
                        class="mt-8 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-800"
                        role="alert"
                    >
                        <svg
                            class="mt-0.5 h-4 w-4 shrink-0"
                            viewBox="0 0 20 20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.6"
                            aria-hidden="true"
                        >
                            <circle cx="10" cy="10" r="7" />
                            <path d="M10 6v4m0 3h.01" />
                        </svg>
                        <span>{{ error }}</span>
                    </div>

                    <section
                        v-if="result"
                        class="mt-9 space-y-4"
                        aria-live="polite"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-2 w-2 rounded-full"
                                    :class="
                                        result.status === 'ok'
                                            ? 'bg-emerald-500'
                                            : result.status === 'rejected'
                                              ? 'bg-amber-500'
                                              : 'bg-rose-500'
                                    "
                                ></span>
                                <h2
                                    class="text-sm font-semibold text-slate-800"
                                >
                                    {{
                                        result.status === 'ok'
                                            ? 'Query results'
                                            : result.status === 'rejected'
                                              ? 'Query blocked'
                                              : 'Query failed'
                                    }}
                                </h2>
                            </div>
                            <span
                                v-if="result.status === 'ok'"
                                class="text-xs text-slate-500"
                                >{{ result.row_count }}
                                {{ result.row_count === 1 ? 'row' : 'rows' }}
                                returned</span
                            >
                        </div>

                        <div
                            class="overflow-hidden rounded-2xl border border-slate-800 bg-[#111827] shadow-sm"
                        >
                            <div
                                class="flex items-center justify-between border-b border-white/10 px-4 py-3"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span class="flex gap-1"
                                        ><i
                                            class="h-2 w-2 rounded-full bg-rose-400/80"
                                        ></i
                                        ><i
                                            class="h-2 w-2 rounded-full bg-amber-300/80"
                                        ></i
                                        ><i
                                            class="h-2 w-2 rounded-full bg-emerald-400/80"
                                        ></i
                                    ></span>
                                    <span
                                        class="text-[10px] font-semibold tracking-[0.12em] text-slate-400 uppercase"
                                        >Generated SQL</span
                                    >
                                </div>
                                <button
                                    v-if="result.sql_text"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[11px] text-slate-400 transition hover:bg-white/10 hover:text-white"
                                    @click="copySql"
                                >
                                    <svg
                                        v-if="!copied"
                                        class="h-3.5 w-3.5"
                                        viewBox="0 0 16 16"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.3"
                                        aria-hidden="true"
                                    >
                                        <rect
                                            x="5"
                                            y="5"
                                            width="8"
                                            height="9"
                                            rx="1.5"
                                        />
                                        <path
                                            d="M11 5V3.5A1.5 1.5 0 0 0 9.5 2h-6A1.5 1.5 0 0 0 2 3.5v6A1.5 1.5 0 0 0 3.5 11H5"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-3.5 w-3.5 text-emerald-400"
                                        viewBox="0 0 16 16"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        aria-hidden="true"
                                    >
                                        <path d="m3 8 3 3 7-7" />
                                    </svg>
                                    {{ copied ? 'Copied' : 'Copy SQL' }}
                                </button>
                            </div>
                            <pre
                                class="overflow-x-auto px-5 py-4 text-[13px] leading-6 text-emerald-200"
                            ><code>{{ result.sql_text || '-- No SQL was generated' }}</code></pre>
                            <div
                                class="flex items-center gap-2 border-t border-white/10 px-5 py-2.5 text-[10px] text-slate-500"
                            >
                                <svg
                                    class="h-3.5 w-3.5 text-emerald-400"
                                    viewBox="0 0 16 16"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M8 1.5 13 3.5v4c0 3.3-2.3 5.6-5 7-2.7-1.4-5-3.7-5-7v-4l5-2Z"
                                    />
                                    <path d="m5.5 7.7 1.6 1.6 3.5-3.5" />
                                </svg>
                                Read-only guard · only sample tables · max 100
                                rows
                            </div>
                        </div>

                        <div
                            v-if="result.rows.length"
                            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                        >
                            <div
                                class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5"
                            >
                                <div>
                                    <h3
                                        class="text-[13px] font-semibold text-slate-800"
                                    >
                                        Preview
                                    </h3>
                                    <p
                                        class="mt-0.5 text-[11px] text-slate-400"
                                    >
                                        Northwind Gadgets sample data
                                    </p>
                                </div>
                                <span
                                    class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[10px] text-slate-500"
                                    >{{ result.row_count }} rows</span
                                >
                            </div>
                            <div class="overflow-x-auto">
                                <table
                                    class="w-full border-collapse text-left text-xs"
                                >
                                    <thead>
                                        <tr
                                            class="bg-slate-50/80 text-[10px] font-semibold tracking-wide text-slate-500 uppercase"
                                        >
                                            <th
                                                v-for="column in columns"
                                                :key="column"
                                                class="px-5 py-3 whitespace-nowrap"
                                            >
                                                {{
                                                    column.replaceAll('_', ' ')
                                                }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(row, index) in result.rows"
                                            :key="index"
                                            class="border-t border-slate-100 text-slate-700 transition hover:bg-slate-50/60"
                                        >
                                            <td
                                                v-for="column in columns"
                                                :key="column"
                                                class="px-5 py-3 font-mono text-[11px] whitespace-nowrap"
                                            >
                                                {{ row[column] ?? '—' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div
                            v-else-if="result.status === 'ok'"
                            class="rounded-xl border border-slate-200 bg-white px-5 py-8 text-center text-sm text-slate-500"
                        >
                            The query ran successfully but returned no rows.
                        </div>
                    </section>

                    <div
                        class="mt-12 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 border-t border-slate-200/80 pt-5 text-[10px] text-slate-400"
                    >
                        <span class="flex items-center gap-1.5"
                            ><span
                                class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                            ></span>
                            Fictional seed data</span
                        >
                        <span>SQLite only</span>
                        <span>Read-only by design</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
