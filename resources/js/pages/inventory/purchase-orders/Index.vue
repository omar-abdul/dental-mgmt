<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as inventoryIndex } from '@/routes/inventory';
import {
    create as purchaseOrdersCreate,
    index as purchaseOrdersIndex,
    show as purchaseOrdersShow,
} from '@/routes/inventory/purchase-orders';

type PurchaseOrderListItem = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    supplier_name: string;
    ordered_at_formatted: string | null;
    received_at_formatted: string | null;
};

type PaginatedOrders = {
    data: PurchaseOrderListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

defineProps<{
    orders: PaginatedOrders;
    search: string;
    canCreate: boolean;
}>();

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'received') {
        return 'secondary';
    }

    if (status === 'cancelled') {
        return 'destructive';
    }

    return 'outline';
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inventory', href: inventoryIndex() },
            { title: 'Purchase orders', href: purchaseOrdersIndex() },
        ],
    },
});
</script>

<template>
    <Head title="Purchase orders" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Purchase orders"
                description="Track supplier purchase orders"
            />

            <Button v-if="canCreate" as-child>
                <Link
                    :href="purchaseOrdersCreate()"
                    data-test="create-purchase-order-link"
                >
                    New purchase order
                </Link>
            </Button>
        </div>

        <form :action="purchaseOrdersIndex().url" method="get" class="flex gap-2">
            <Input
                name="search"
                :default-value="search"
                placeholder="Search by PO number or supplier"
                class="max-w-md"
                data-test="purchase-order-search-input"
            />
            <Button type="submit" variant="secondary" data-test="purchase-order-search-button">
                Search
            </Button>
        </form>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">PO number</th>
                        <th class="px-4 py-3 font-medium">Supplier</th>
                        <th class="px-4 py-3 font-medium">Ordered</th>
                        <th class="px-4 py-3 font-medium">Received</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="orders.data.length === 0">
                        <td colspan="5" class="text-muted-foreground px-4 py-8 text-center">
                            No purchase orders found.
                        </td>
                    </tr>
                    <tr v-for="order in orders.data" :key="order.id">
                        <td class="px-4 py-3 font-medium">
                            <Link
                                :href="purchaseOrdersShow(order.id)"
                                class="text-primary hover:underline"
                                data-test="purchase-order-link"
                            >
                                {{ order.number }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">{{ order.supplier_name }}</td>
                        <td class="px-4 py-3">{{ order.ordered_at_formatted ?? '—' }}</td>
                        <td class="px-4 py-3">{{ order.received_at_formatted ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="statusVariant(order.status)">
                                {{ order.status_label }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
