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
    treatmentStatistics as treatmentStatisticsRoute,
} from '@/routes/reports';

type StatusRow = {
    status: string;
    status_label: string;
    count: number;
};

type TreatmentRow = {
    id: number;
    patient_name: string;
    dentist_name: string;
    diagnosed_at: string;
    diagnosis: string;
    status: string;
    status_label: string;
    procedure_count: number;
};

defineProps<{
    filters: {
        from: string;
        to: string;
    };
    canViewFinance: boolean;
    report: {
        total: number;
        by_status: StatusRow[];
        procedure_count: number;
        procedure_fees_cents: number;
        procedure_fees_formatted: string;
        rows: TreatmentRow[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: reportsIndex() },
            { title: 'Treatment statistics', href: treatmentStatisticsRoute() },
        ],
    },
});
</script>

<template>
    <Head title="Treatment statistics report" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Treatment statistics"
                description="Treatment counts and procedure activity"
            />
            <Link :href="reportsIndex({ query: filters })" class="text-primary text-sm hover:underline">
                Back to reports
            </Link>
        </div>

        <ReportDateRangeFilter
            :action="treatmentStatisticsRoute().url"
            :from="filters.from"
            :to="filters.to"
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Treatments</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold" data-test="report-total">
                        {{ report.total }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Procedures</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">
                        {{ report.procedure_count }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Procedure fees</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">
                        {{ report.procedure_fees_formatted }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">By status</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="report.by_status.length === 0"
                    class="text-muted-foreground text-sm"
                >
                    No treatments in this range.
                </div>
                <ul v-else class="space-y-2 text-sm">
                    <li
                        v-for="row in report.by_status"
                        :key="row.status"
                        class="flex items-center justify-between"
                    >
                        <span>{{ row.status_label }}</span>
                        <Badge variant="secondary">{{ row.count }}</Badge>
                    </li>
                </ul>
            </CardContent>
        </Card>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Patient</th>
                        <th class="px-4 py-3 font-medium">Dentist</th>
                        <th class="px-4 py-3 font-medium">Diagnosis</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Procedures</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="report.rows.length === 0">
                        <td colspan="6" class="text-muted-foreground px-4 py-8 text-center">
                            No treatments in this range.
                        </td>
                    </tr>
                    <tr v-for="row in report.rows" :key="row.id">
                        <td class="px-4 py-3">{{ new Date(row.diagnosed_at).toLocaleDateString() }}</td>
                        <td class="px-4 py-3">{{ row.patient_name }}</td>
                        <td class="px-4 py-3">{{ row.dentist_name }}</td>
                        <td class="px-4 py-3">{{ row.diagnosis }}</td>
                        <td class="px-4 py-3">{{ row.status_label }}</td>
                        <td class="px-4 py-3">{{ row.procedure_count }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
