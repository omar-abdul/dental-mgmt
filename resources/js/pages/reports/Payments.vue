<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ReportDateRangeFilter from '@/components/reports/ReportDateRangeFilter.vue';
import Heading from '@/components/Heading.vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    index as reportsIndex,
    payments as paymentsRoute,
} from '@/routes/reports';

type MethodRow = {
    method: string;
    method_label: string;
    count: number;
    total_cents: number;
    total_formatted: string;
};

type PaymentRow = {
    id: number;
    payment_number: string;
    paid_at: string;
    patient_name: string;
    method: string;
    method_label: string;
    amount_cents: number;
    amount_formatted: string;
};

defineProps<{
    filters: {
        from: string;
        to: string;
    };
    canViewFinance: boolean;
    report: {
        payment_count: number;
        total_cents: number;
        total_formatted: string;
        by_method: MethodRow[];
        rows: PaymentRow[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: reportsIndex() },
            { title: 'Payments', href: paymentsRoute() },
        ],
    },
});
</script>

<template>
    <Head title="Payments report" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Payments"
                description="Completed payments with method breakdown"
            />
            <Link :href="reportsIndex({ query: filters })" class="text-primary text-sm hover:underline">
                Back to reports
            </Link>
        </div>

        <ReportDateRangeFilter
            :action="paymentsRoute().url"
            :from="filters.from"
            :to="filters.to"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Payment count</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold" data-test="report-payment-count">
                        {{ report.payment_count }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Total collected</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold" data-test="report-total">
                        {{ report.total_formatted }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">By payment method</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="report.by_method.length === 0"
                    class="text-muted-foreground text-sm"
                >
                    No completed payments in this range.
                </div>
                <div v-else class="divide-border overflow-hidden rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">Method</th>
                                <th class="px-4 py-3 font-medium">Count</th>
                                <th class="px-4 py-3 font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-border divide-y">
                            <tr v-for="row in report.by_method" :key="row.method">
                                <td class="px-4 py-3">{{ row.method_label }}</td>
                                <td class="px-4 py-3">{{ row.count }}</td>
                                <td class="px-4 py-3">{{ row.total_formatted }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Payment</th>
                        <th class="px-4 py-3 font-medium">Paid</th>
                        <th class="px-4 py-3 font-medium">Patient</th>
                        <th class="px-4 py-3 font-medium">Method</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="report.rows.length === 0">
                        <td colspan="5" class="text-muted-foreground px-4 py-8 text-center">
                            No completed payments in this range.
                        </td>
                    </tr>
                    <tr v-for="row in report.rows" :key="row.id">
                        <td class="px-4 py-3">{{ row.payment_number }}</td>
                        <td class="px-4 py-3">{{ new Date(row.paid_at).toLocaleString() }}</td>
                        <td class="px-4 py-3">{{ row.patient_name }}</td>
                        <td class="px-4 py-3">{{ row.method_label }}</td>
                        <td class="px-4 py-3">{{ row.amount_formatted }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
