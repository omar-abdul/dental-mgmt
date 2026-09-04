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
    patientRegistration as patientRegistrationRoute,
} from '@/routes/reports';

type PatientRow = {
    id: number;
    patient_number: string;
    name: string;
    registered_at: string;
};

defineProps<{
    filters: {
        from: string;
        to: string;
    };
    canViewFinance: boolean;
    report: {
        total: number;
        rows: PatientRow[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: reportsIndex() },
            { title: 'Patient registration', href: patientRegistrationRoute() },
        ],
    },
});
</script>

<template>
    <Head title="Patient registration report" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Patient registration"
                description="New patients registered in the selected range"
            />
            <Link :href="reportsIndex({ query: filters })" class="text-primary text-sm hover:underline">
                Back to reports
            </Link>
        </div>

        <ReportDateRangeFilter
            :action="patientRegistrationRoute().url"
            :from="filters.from"
            :to="filters.to"
        />

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-sm font-medium">Total registrations</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-semibold" data-test="report-total">
                    {{ report.total }}
                </p>
            </CardContent>
        </Card>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Patient #</th>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Registered</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="report.rows.length === 0">
                        <td colspan="3" class="text-muted-foreground px-4 py-8 text-center">
                            No registrations in this range.
                        </td>
                    </tr>
                    <tr v-for="row in report.rows" :key="row.id">
                        <td class="px-4 py-3">{{ row.patient_number }}</td>
                        <td class="px-4 py-3">{{ row.name }}</td>
                        <td class="px-4 py-3">{{ new Date(row.registered_at).toLocaleString() }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
