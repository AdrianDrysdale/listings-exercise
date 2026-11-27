<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    heading: { type: String, required: true },
    subheading: { type: String, default: null },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
</script>

<template>
    <div class="mx-auto max-w-5xl px-4 py-10">
        <header class="mb-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    href="/"
                    class="text-sm font-medium text-slate-500 transition hover:text-slate-900"
                >
                    Street Listings
                </Link>

                <nav class="flex flex-wrap gap-4 text-sm text-slate-500">
                    <Link href="/" class="transition hover:text-slate-900">Listings</Link>
                    <Link href="/saved-searches" class="transition hover:text-slate-900">
                        Saved searches
                    </Link>
                    <Link href="/alerts" class="transition hover:text-slate-900">Alerts</Link>
                </nav>
            </div>

            <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ heading }}</h1>
            <p v-if="subheading" class="mt-1 text-slate-500">{{ subheading }}</p>
        </header>

        <p
            v-if="flashSuccess"
            class="mb-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
            role="status"
        >
            {{ flashSuccess }}
        </p>

        <main>
            <slot />
        </main>
    </div>
</template>
