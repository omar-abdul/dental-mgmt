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
    inventoryStock as inventoryStockRoute,
} from '@/routes/reports';

type ItemRow = {
    id: number;
    name: string;
    category: string;
    category_label: string;
    quantity: number;
    unit: string;
    reorder_level: number;
    stock_status: string;
    stock_status_label: string;
};

defineProps<{
    filters: {
        from: string;
        to: string;
    };
    canViewFinance: boolean;
    report: {
        total_items: number;
        stock_value_cents: number;
        stock_value_formatted: string;
        rows: ItemRow[];
    };
}>();

function stockBadgeVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'out') {
        return 'destructive';
    }

    if (status === 'low') {
        return 'outline';
    }

    return 'secondary';
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: reportsIndex() },
            { title: 'Inventory stock', href: inventoryStockRoute() },
        ],
    },
});
</script>

<template>
    <Head title="Inventory stock report" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Inventory stock"
                description="Current stock levels and valuation snapshot"
            />
            <Link :href="reportsIndex({ query: filters })" class="text-primary text-sm hover:underline">
                Back to reports
            </Link>
        </div>

        <ReportDateRangeFilter
            :action="inventoryStockRoute().url"
            :from="filters.from"
            :to="filters.to"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Total items</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold" data-test="report-total">
                        {{ report.total_items }}
                    </p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Stock value</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">
                        {{ report.stock_value_formatted }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Item</th>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Quantity</th>
                        <th class="px-4 py-3 font-medium">Reorder</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="report.rows.length === 0">
                        <td colspan="5" class="text-muted-foreground px-4 py-8 text-center">
                            No inventory items found.
                        </td>
                    </tr>
                    <tr v-for="row in report.rows" :key="row.id">
                        <td class="px-4 py-3">{{ row.name }}</td>
                        <td class="px-4 py-3">{{ row.category_label }}</td>
                        <td class="px-4 py-3">{{ row.quantity }} {{ row.unit }}</td>
                        <td class="px-4 py-3">{{ row.reorder_level }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="stockBadgeVariant(row.stock_status)">
                                {{ row.stock_status_label }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
