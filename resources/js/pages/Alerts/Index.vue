<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../components/AppLayout.vue';
import { criteriaLabel, formatDate, formatPrice } from '../../format';

defineProps({
    alerts: { type: Array, required: true },
});
</script>

<template>
    <Head title="Alerts" />

    <AppLayout heading="Alerts" subheading="New listings that match your saved searches.">
        <ul v-if="alerts.length" class="space-y-3">
            <li
                v-for="alert in alerts"
                :key="alert.id"
                class="rounded-xl border border-slate-200 bg-white px-5 py-4"
            >
                <Link
                    :href="`/listings/${alert.listing.id}`"
                    class="block transition hover:opacity-80"
                >
                    <p class="font-medium text-slate-900">
                        {{ alert.listing.address_line_1 }}, {{ alert.listing.city }}
                    </p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ formatPrice(alert.listing.price) }}
                        <span v-if="alert.listing.property_type_label">
                            · {{ alert.listing.property_type_label }}
                        </span>
                        <span v-if="alert.listing.bedrooms != null">
                            · {{ alert.listing.bedrooms }} beds
                        </span>
                    </p>
                    <p class="mt-2 text-sm text-slate-500">
                        Matched: {{ criteriaLabel(alert.saved_search) }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Alerted {{ formatDate(alert.created_at) ?? '—' }}
                    </p>
                </Link>
            </li>
        </ul>

        <p
            v-else
            class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-500"
        >
            No alerts yet. Save a search and we’ll notify you when something new goes live.
        </p>
    </AppLayout>
</template>
