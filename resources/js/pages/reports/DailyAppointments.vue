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
    dailyAppointments as dailyAppointmentsRoute,
    index as reportsIndex,
} from '@/routes/reports';

type StatusRow = {
    status: string;
    status_label: string;
    count: number;
};

type AppointmentRow = {
    id: number;
    number: string;
    starts_at: string;
    patient_name: string;
    dentist_name: string;
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
        total: number;
        by_status: StatusRow[];
        rows: AppointmentRow[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: reportsIndex() },
            { title: 'Daily appointments', href: dailyAppointmentsRoute() },
        ],
    },
});
</script>

<template>
    <Head title="Daily appointments report" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Daily appointments"
                description="Appointment volume and status breakdown"
            />
            <Link :href="reportsIndex({ query: filters })" class="text-primary text-sm hover:underline">
                Back to reports
            </Link>
        </div>

        <ReportDateRangeFilter
            :action="dailyAppointmentsRoute().url"
            :from="filters.from"
            :to="filters.to"
        />

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-sm font-medium">Total appointments</CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-2xl font-semibold" data-test="report-total">
                    {{ report.total }}
                </p>
            </CardContent>
        </Card>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">By status</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="report.by_status.length === 0"
                        class="text-muted-foreground text-sm"
                    >
                        No appointments in this range.
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

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Recent appointments</CardTitle>
                </CardHeader>
                <CardContent class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-muted-foreground text-left">
                            <tr>
                                <th class="pb-2 pr-4 font-medium">Time</th>
                                <th class="pb-2 pr-4 font-medium">Patient</th>
                                <th class="pb-2 pr-4 font-medium">Dentist</th>
                                <th class="pb-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="report.rows.length === 0">
                                <td colspan="4" class="text-muted-foreground py-4 text-center">
                                    No appointments in this range.
                                </td>
                            </tr>
                            <tr
                                v-for="row in report.rows"
                                :key="row.id"
                                class="border-t"
                            >
                                <td class="py-2 pr-4">{{ new Date(row.starts_at).toLocaleString() }}</td>
                                <td class="py-2 pr-4">{{ row.patient_name }}</td>
                                <td class="py-2 pr-4">{{ row.dentist_name }}</td>
                                <td class="py-2">{{ row.status_label }}</td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
