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
    lowStock as lowStockRoute,
} from '@/routes/reports';

type ItemRow = {
    id: number;
    name: string;
    category: string;
    category_label: string;
    quantity: number;
    unit: string;
    reorder_level: number;
};

defineProps<{
    filters: {
        from: string;
        to: string;
    };
    canViewFinance: boolean;
    report: {
        total: number;
        rows: ItemRow[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: reportsIndex() },
            { title: 'Low stock', href: lowStockRoute() },
        ],
    },
});
</script>

<template>
    <Head title="Low stock report" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Low stock"
                description="Items at or below reorder level"
            />
            <Link :href="reportsIndex({ query: filters })" class="text-primary text-sm hover:underline">
                Back to reports
            </Link>
        </div>

        <ReportDateRangeFilter
            :action="lowStockRoute().url"
            :from="filters.from"
            :to="filters.to"
        />

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-sm font-medium">Low-stock items</CardTitle>
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
                        <th class="px-4 py-3 font-medium">Item</th>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Quantity</th>
                        <th class="px-4 py-3 font-medium">Reorder level</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="report.rows.length === 0">
                        <td colspan="4" class="text-muted-foreground px-4 py-8 text-center">
                            No low-stock items right now.
                        </td>
                    </tr>
                    <tr v-for="row in report.rows" :key="row.id">
                        <td class="px-4 py-3">{{ row.name }}</td>
                        <td class="px-4 py-3">{{ row.category_label }}</td>
                        <td class="px-4 py-3">{{ row.quantity }} {{ row.unit }}</td>
                        <td class="px-4 py-3">{{ row.reorder_level }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
