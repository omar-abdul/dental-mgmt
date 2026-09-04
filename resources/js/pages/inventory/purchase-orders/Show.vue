<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PurchaseOrderController from '@/actions/App/Http/Controllers/PurchaseOrderController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as inventoryIndex } from '@/routes/inventory';
import {
    index as purchaseOrdersIndex,
} from '@/routes/inventory/purchase-orders';

type PurchaseOrderItem = {
    id: number;
    inventory_item_name: string;
    quantity_ordered: number;
    quantity_received: number;
    unit_cost_formatted: string;
    batch_number: string | null;
    expiry_date_formatted: string | null;
};

type PurchaseOrderDetail = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    notes: string | null;
    ordered_at_formatted: string | null;
    received_at_formatted: string | null;
    supplier: {
        id: number;
        name: string;
    };
    items: PurchaseOrderItem[];
};

defineProps<{
    order: PurchaseOrderDetail;
    canReceive: boolean;
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
    <Head :title="`Purchase order — ${order.number}`" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                variant="small"
                :title="order.number"
                :description="order.supplier.name"
            />

            <Badge :variant="statusVariant(order.status)">
                {{ order.status_label }}
            </Badge>
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-muted-foreground text-sm">Ordered</dt>
                <dd class="font-medium">{{ order.ordered_at_formatted ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground text-sm">Received</dt>
                <dd class="font-medium">{{ order.received_at_formatted ?? '—' }}</dd>
            </div>
        </dl>

        <p v-if="order.notes" class="text-muted-foreground text-sm">
            {{ order.notes }}
        </p>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Item</th>
                        <th class="px-4 py-3 font-medium">Ordered</th>
                        <th class="px-4 py-3 font-medium">Received</th>
                        <th class="px-4 py-3 font-medium">Unit cost</th>
                        <th class="px-4 py-3 font-medium">Batch</th>
                        <th class="px-4 py-3 font-medium">Expiry</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-for="item in order.items" :key="item.id">
                        <td class="px-4 py-3 font-medium">{{ item.inventory_item_name }}</td>
                        <td class="px-4 py-3">{{ item.quantity_ordered }}</td>
                        <td class="px-4 py-3" data-test="po-item-quantity-received">
                            {{ item.quantity_received }}
                        </td>
                        <td class="px-4 py-3">{{ item.unit_cost_formatted }}</td>
                        <td class="px-4 py-3">{{ item.batch_number ?? '—' }}</td>
                        <td class="px-4 py-3">{{ item.expiry_date_formatted ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Form
            v-if="canReceive"
            v-bind="PurchaseOrderController.receive.form(order.id)"
            v-slot="{ processing }"
        >
            <Button type="submit" :disabled="processing" data-test="receive-purchase-order-button">
                Receive purchase order
            </Button>
        </Form>

        <Button variant="outline" as-child>
            <Link :href="purchaseOrdersIndex()">Back to purchase orders</Link>
        </Button>
    </div>
</template>
