<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ReportDateRangeFilter from '@/components/reports/ReportDateRangeFilter.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    index as reportsIndex,
    outstandingBalances as outstandingBalancesRoute,
} from '@/routes/reports';

type InvoiceRow = {
    id: number;
    invoice_number: string;
    patient_name: string;
    issued_at: string;
    balance_cents: number;
    balance_formatted: string;
    status: string;
    status_label: string;
};

defineProps<{
    filters: {
        from: string;
        to: string;
    };
    canViewFinance: boolean;
    report: {
        invoice_count: number;
        total_balance_cents: number;
        total_balance_formatted: string;
        rows: InvoiceRow[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: reportsIndex() },
            { title: 'Outstanding balances', href: outstandingBalancesRoute() },
        ],
    },
});
</script>

<template>
    <Head title="Outstanding balances report" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Outstanding balances"
                description="Open invoice balances across the clinic"
            />
            <Link :href="reportsIndex({ query: filters })" class="text-primary text-sm hover:underline">
                Back to reports
            </Link>
        </div>

        <ReportDateRangeFilter
            :action="outstandingBalancesRoute().url"
            :from="filters.from"
            :to="filters.to"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Open invoices</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold" data-test="report-invoice-count">
                        {{ report.invoice_count }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Total outstanding</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold" data-test="report-total">
                        {{ report.total_balance_formatted }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Invoice</th>
                        <th class="px-4 py-3 font-medium">Patient</th>
                        <th class="px-4 py-3 font-medium">Issued</th>
                        <th class="px-4 py-3 font-medium">Balance</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="report.rows.length === 0">
                        <td colspan="5" class="text-muted-foreground px-4 py-8 text-center">
                            No outstanding invoices.
                        </td>
                    </tr>
                    <tr v-for="row in report.rows" :key="row.id">
                        <td class="px-4 py-3">{{ row.invoice_number }}</td>
                        <td class="px-4 py-3">{{ row.patient_name }}</td>
                        <td class="px-4 py-3">{{ new Date(row.issued_at).toLocaleDateString() }}</td>
                        <td class="px-4 py-3">{{ row.balance_formatted }}</td>
                        <td class="px-4 py-3">
                            <Badge variant="outline">{{ row.status_label }}</Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
