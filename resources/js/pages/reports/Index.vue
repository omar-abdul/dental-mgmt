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
    dailyAppointments,
    index as reportsIndex,
    inventoryStock,
    lowStock,
    outstandingBalances,
    patientRegistration,
    payments,
    treatmentStatistics,
} from '@/routes/reports';

type ReportCard = {
    key: string;
    title: string;
    description: string;
    finance: boolean;
};

type Summary = {
    appointments: number;
    registrations: number;
    payments_cents: number | null;
    payments_formatted: string | null;
    outstanding_cents: number | null;
    outstanding_formatted: string | null;
};

const props = defineProps<{
    filters: {
        from: string;
        to: string;
    };
    canViewFinance: boolean;
    summary: Summary;
    reports: ReportCard[];
}>();

function reportHref(key: string): string {
    return matchReportRoute(key).url;
}

function matchReportRoute(key: string) {
    switch (key) {
        case 'daily-appointments':
            return dailyAppointments({ query: props.filters });
        case 'patient-registration':
            return patientRegistration({ query: props.filters });
        case 'outstanding-balances':
            return outstandingBalances({ query: props.filters });
        case 'payments':
            return payments({ query: props.filters });
        case 'inventory-stock':
            return inventoryStock({ query: props.filters });
        case 'low-stock':
            return lowStock({ query: props.filters });
        case 'treatment-statistics':
            return treatmentStatistics({ query: props.filters });
        default:
            return reportsIndex({ query: props.filters });
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Reports',
                href: reportsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Reports" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Reports"
            description="Wave 1 operational and finance reports"
        />

        <ReportDateRangeFilter
            :action="reportsIndex().url"
            :from="filters.from"
            :to="filters.to"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">
                        Appointments in range
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold" data-test="summary-appointments">
                        {{ summary.appointments }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">
                        New registrations
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold" data-test="summary-registrations">
                        {{ summary.registrations }}
                    </p>
                </CardContent>
            </Card>

            <Card v-if="canViewFinance">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">
                        Completed payments
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold" data-test="summary-payments-total">
                        {{ summary.payments_formatted }}
                    </p>
                </CardContent>
            </Card>

            <Card v-if="canViewFinance">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">
                        Outstanding balances
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold" data-test="summary-outstanding-total">
                        {{ summary.outstanding_formatted }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="report in reports" :key="report.key">
                <CardHeader>
                    <CardTitle class="text-base">{{ report.title }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-muted-foreground text-sm">
                        {{ report.description }}
                    </p>
                    <Link
                        :href="reportHref(report.key)"
                        class="text-primary text-sm font-medium hover:underline"
                        :data-test="`report-link-${report.key}`"
                    >
                        Open report
                    </Link>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
