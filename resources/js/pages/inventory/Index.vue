<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InventoryController from '@/actions/App/Http/Controllers/InventoryController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as inventoryIndex } from '@/routes/inventory';
import { index as purchaseOrdersIndex } from '@/routes/inventory/purchase-orders';
import { index as suppliersIndex } from '@/routes/inventory/suppliers';

type InventoryBatch = {
    id: number;
    batch_number: string;
    quantity: number;
    expiry_date: string;
    expiry_date_formatted: string;
    is_expired: boolean;
};

type InventoryListItem = {
    id: number;
    name: string;
    category: string;
    category_label: string;
    quantity: number;
    unit: string;
    reorder_level: number;
    unit_cost_cents: number;
    unit_cost_formatted: string;
    stock_status: 'out' | 'low' | 'in_stock';
    stock_status_label: string;
    batches?: InventoryBatch[];
};

type ExpiryAlert = {
    id: number;
    inventory_item_id: number;
    item_name: string;
    batch_number: string;
    quantity: number;
    expiry_date: string;
    expiry_date_formatted: string;
    is_expired: boolean;
};

type PaginatedItems = {
    data: InventoryListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Stats = {
    total_items: number;
    low_stock: number;
    out_of_stock: number;
    stock_value_cents: number;
    stock_value_formatted: string;
    expiring_soon: number;
};

type Option = {
    value: string;
    label: string;
};

const props = defineProps<{
    items: PaginatedItems;
    search: string;
    stats: Stats;
    expiryAlerts: ExpiryAlert[];
    categories: Option[];
    movementTypes: Option[];
    canCreate: boolean;
    canAdjust: boolean;
}>();

const showCreateDialog = ref(false);
const showAdjustDialog = ref(false);
const adjustingItem = ref<InventoryListItem | null>(null);
const selectedAdjustmentType = ref('adjustment_in');
const selectedBatchId = ref('');

const isConsumption = computed(
    () => selectedAdjustmentType.value === 'consumption',
);

const isAdjustmentIn = computed(
    () => selectedAdjustmentType.value === 'adjustment_in',
);

const availableBatches = computed(() => adjustingItem.value?.batches ?? []);

function openAdjustDialog(item: InventoryListItem): void {
    adjustingItem.value = item;
    selectedAdjustmentType.value = 'adjustment_in';
    selectedBatchId.value = '';
    showAdjustDialog.value = true;
}

function closeAdjustDialog(): void {
    showAdjustDialog.value = false;
    adjustingItem.value = null;
    selectedBatchId.value = '';
}

function stockBadgeVariant(
    status: InventoryListItem['stock_status'],
): 'default' | 'secondary' | 'destructive' | 'outline' {
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
            {
                title: 'Inventory',
                href: inventoryIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Inventory" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Inventory"
                description="Track clinic stock levels and adjustments"
            />

            <div class="flex flex-wrap gap-2">
                <Button v-if="canCreate" data-test="add-inventory-button" @click="showCreateDialog = true">
                    Add item
                </Button>
                <Button v-if="canCreate" variant="outline" as-child>
                    <Link :href="purchaseOrdersIndex()" data-test="purchase-orders-link">
                        Purchase orders
                    </Link>
                </Button>
                <Button v-if="canCreate" variant="outline" as-child>
                    <Link :href="suppliersIndex()" data-test="suppliers-link">
                        Suppliers
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">
                        Total items
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">{{ stats.total_items }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">
                        Low stock
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">{{ stats.low_stock }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">
                        Out of stock
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">{{ stats.out_of_stock }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-muted-foreground text-sm font-medium">
                        Stock value
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">
                        {{ stats.stock_value_formatted }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div
            v-if="expiryAlerts.length > 0"
            class="border-destructive/30 bg-destructive/5 space-y-3 rounded-md border p-4"
            data-test="expiry-alerts-panel"
        >
            <h3 class="text-sm font-medium">
                Expiry alerts
                <span class="text-muted-foreground font-normal">({{ stats.expiring_soon }})</span>
            </h3>
            <ul class="space-y-2 text-sm">
                <li
                    v-for="alert in expiryAlerts"
                    :key="alert.id"
                    class="flex flex-wrap items-center gap-2"
                    data-test="expiry-alert-item"
                >
                    <Badge :variant="alert.is_expired ? 'destructive' : 'outline'">
                        {{ alert.is_expired ? 'Expired' : 'Expiring soon' }}
                    </Badge>
                    <span class="font-medium">{{ alert.item_name }}</span>
                    <span class="text-muted-foreground">
                        Batch {{ alert.batch_number }} · {{ alert.quantity }} units ·
                        {{ alert.expiry_date_formatted }}
                    </span>
                </li>
            </ul>
        </div>

        <form :action="inventoryIndex().url" method="get" class="flex gap-2">
            <Input
                name="search"
                :default-value="search"
                placeholder="Search by name"
                class="max-w-md"
                data-test="inventory-search-input"
            />
            <Button type="submit" variant="secondary" data-test="inventory-search-button">Search</Button>
        </form>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Quantity</th>
                        <th class="px-4 py-3 font-medium">Unit cost</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th
                            v-if="canAdjust"
                            class="px-4 py-3 text-right font-medium"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="items.data.length === 0">
                        <td
                            :colspan="canAdjust ? 6 : 5"
                            class="text-muted-foreground px-4 py-8 text-center"
                        >
                            No inventory items found.
                        </td>
                    </tr>
                    <tr v-for="item in items.data" :key="item.id">
                        <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                        <td class="px-4 py-3">{{ item.category_label }}</td>
                        <td class="px-4 py-3">
                            {{ item.quantity }} {{ item.unit }}
                        </td>
                        <td class="px-4 py-3">{{ item.unit_cost_formatted }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="stockBadgeVariant(item.stock_status)">
                                {{ item.stock_status_label }}
                            </Badge>
                        </td>
                        <td v-if="canAdjust" class="px-4 py-3 text-right">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="openAdjustDialog(item)"
                                data-test="adjust-inventory-button"
                            >
                                Adjust
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="items.links.length > 3" class="flex flex-wrap gap-2">
            <template v-for="(link, index) in items.links" :key="index">
                <Button
                    v-if="link.url"
                    as-child
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                >
                    <Link :href="link.url" v-html="link.label" />
                </Button>
                <span
                    v-else
                    class="text-muted-foreground inline-flex items-center px-2 text-sm"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>

    <Dialog v-model:open="showCreateDialog">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Add inventory item</DialogTitle>
                <DialogDescription>
                    Register a new stock item with optional initial quantity.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="InventoryController.store.form()"
                v-slot="{ errors, processing }"
                class="space-y-4"
                @success="showCreateDialog = false"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" required />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="category">Category</Label>
                    <select
                        id="category"
                        name="category"
                        required
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="" disabled selected>Select category</option>
                        <option
                            v-for="category in categories"
                            :key="category.value"
                            :value="category.value"
                        >
                            {{ category.label }}
                        </option>
                    </select>
                    <InputError :message="errors.category" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="quantity">Initial quantity</Label>
                        <Input
                            id="quantity"
                            name="quantity"
                            type="number"
                            min="0"
                            step="1"
                            :default-value="0"
                            required
                        />
                        <InputError :message="errors.quantity" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="unit">Unit</Label>
                        <Input id="unit" name="unit" required placeholder="box" />
                        <InputError :message="errors.unit" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="reorder_level">Reorder level</Label>
                        <Input
                            id="reorder_level"
                            name="reorder_level"
                            type="number"
                            min="0"
                            step="1"
                            :default-value="0"
                            required
                        />
                        <InputError :message="errors.reorder_level" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="unit_cost">Unit cost ($)</Label>
                        <Input
                            id="unit_cost"
                            name="unit_cost"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                        />
                        <InputError :message="errors.unit_cost" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="expiry_date">Expiry date</Label>
                        <Input
                            id="expiry_date"
                            name="expiry_date"
                            type="date"
                            data-test="create-expiry-date-input"
                        />
                        <InputError :message="errors.expiry_date" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="batch_number">Batch number (optional)</Label>
                        <Input id="batch_number" name="batch_number" />
                        <InputError :message="errors.batch_number" />
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="showCreateDialog = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing" data-test="create-inventory-button">
                        Create item
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="showAdjustDialog"
        @update:open="
            (open) => {
                if (!open) {
                    closeAdjustDialog();
                }
            }
        "
    >
        <DialogContent v-if="adjustingItem" class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Adjust stock</DialogTitle>
                <DialogDescription>
                    Update quantity for {{ adjustingItem.name }} (current:
                    {{ adjustingItem.quantity }} {{ adjustingItem.unit }}).
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="
                    InventoryController.adjust.form(adjustingItem.id)
                "
                v-slot="{ errors, processing }"
                class="space-y-4"
                @success="closeAdjustDialog()"
            >
                <div class="grid gap-2">
                    <Label for="type">Adjustment type</Label>
                    <select
                        id="type"
                        name="type"
                        v-model="selectedAdjustmentType"
                        required
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                    >
                        <option
                            v-for="movementType in movementTypes"
                            :key="movementType.value"
                            :value="movementType.value"
                        >
                            {{ movementType.label }}
                        </option>
                    </select>
                    <InputError :message="errors.type" />
                </div>

                <div v-if="isConsumption" class="grid gap-2">
                    <Label for="inventory_batch_id">Batch</Label>
                    <select
                        id="inventory_batch_id"
                        name="inventory_batch_id"
                        v-model="selectedBatchId"
                        required
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                        data-test="consumption-batch-select"
                    >
                        <option value="" disabled selected>Select batch</option>
                        <option
                            v-for="batch in availableBatches"
                            :key="batch.id"
                            :value="batch.id"
                        >
                            {{ batch.batch_number }} — {{ batch.quantity }} left — expires
                            {{ batch.expiry_date_formatted }}
                            {{ batch.is_expired ? '(expired)' : '' }}
                        </option>
                    </select>
                    <InputError :message="errors.inventory_batch_id" />
                </div>

                <div v-if="isAdjustmentIn" class="grid gap-2">
                    <Label for="adjust_expiry_date">Expiry date</Label>
                    <Input
                        id="adjust_expiry_date"
                        name="expiry_date"
                        type="date"
                        required
                        data-test="adjust-expiry-date-input"
                    />
                    <InputError :message="errors.expiry_date" />
                </div>

                <div v-if="isAdjustmentIn" class="grid gap-2">
                    <Label for="adjust_batch_number">Batch number (optional)</Label>
                    <Input id="adjust_batch_number" name="batch_number" />
                    <InputError :message="errors.batch_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="adjust_quantity">Quantity</Label>
                    <Input
                        id="adjust_quantity"
                        name="quantity"
                        type="number"
                        min="1"
                        step="1"
                        required
                    />
                    <InputError :message="errors.quantity" />
                </div>

                <div class="grid gap-2">
                    <Label for="reason">Reason (optional)</Label>
                    <Input id="reason" name="reason" />
                    <InputError :message="errors.reason" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeAdjustDialog">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing" data-test="save-adjustment-button">
                        Save adjustment
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
