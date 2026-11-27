<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../components/AppLayout.vue';
import { criteriaLabel } from '../../format';

defineProps({
    savedSearches: { type: Array, required: true },
});

function destroy(id) {
    router.delete(`/saved-searches/${id}`);
}
</script>

<template>
    <Head title="Saved searches" />

    <AppLayout
        heading="Saved searches"
        subheading="We’ll alert you when a new live listing matches."
    >
        <ul v-if="savedSearches.length" class="space-y-3">
            <li
                v-for="search in savedSearches"
                :key="search.id"
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-5 py-4"
            >
                <p class="text-sm text-slate-700">{{ criteriaLabel(search) }}</p>

                <button
                    type="button"
                    class="text-sm text-slate-500 underline-offset-2 hover:text-slate-900 hover:underline"
                    @click="destroy(search.id)"
                >
                    Delete
                </button>
            </li>
        </ul>

        <p
            v-else
            class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-500"
        >
            No saved searches yet. Filter the listings page and click “Save search”.
        </p>
    </AppLayout>
</template>
