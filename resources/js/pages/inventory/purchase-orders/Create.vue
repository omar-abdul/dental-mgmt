<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PurchaseOrderController from '@/actions/App/Http/Controllers/PurchaseOrderController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as inventoryIndex } from '@/routes/inventory';
import {
    index as purchaseOrdersIndex,
} from '@/routes/inventory/purchase-orders';

type Option = {
    id: number;
    label: string;
    unit?: string;
};

type LineItem = {
    inventory_item_id: string;
    quantity_ordered: string;
    unit_cost: string;
    batch_number: string;
    expiry_date: string;
};

defineProps<{
    suppliers: Option[];
    inventoryItems: Option[];
}>();

const lineItems = ref<LineItem[]>([
    {
        inventory_item_id: '',
        quantity_ordered: '',
        unit_cost: '',
        batch_number: '',
        expiry_date: '',
    },
]);

function addLineItem(): void {
    lineItems.value.push({
        inventory_item_id: '',
        quantity_ordered: '',
        unit_cost: '',
        batch_number: '',
        expiry_date: '',
    });
}

function removeLineItem(index: number): void {
    if (lineItems.value.length > 1) {
        lineItems.value.splice(index, 1);
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inventory', href: inventoryIndex() },
            { title: 'Purchase orders', href: purchaseOrdersIndex() },
            { title: 'Create', href: purchaseOrdersIndex() },
        ],
    },
});
</script>

<template>
    <Head title="New purchase order" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="New purchase order"
            description="Create a purchase order for supplier delivery"
        />

        <Form
            v-bind="PurchaseOrderController.store.form()"
            v-slot="{ errors, processing }"
            class="mx-auto max-w-3xl space-y-6"
            data-test="create-purchase-order-form"
        >
            <div class="grid gap-2">
                <Label for="supplier_id">Supplier</Label>
                <select
                    id="supplier_id"
                    name="supplier_id"
                    required
                    class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                    data-test="purchase-order-supplier-select"
                >
                    <option value="" disabled selected>Select supplier</option>
                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                        {{ supplier.label }}
                    </option>
                </select>
                <InputError :message="errors.supplier_id" />
            </div>

            <div class="grid gap-2">
                <Label for="notes">Notes (optional)</Label>
                <Input id="notes" name="notes" />
                <InputError :message="errors.notes" />
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium">Line items</h3>
                    <Button type="button" variant="outline" size="sm" @click="addLineItem">
                        Add line
                    </Button>
                </div>

                <div
                    v-for="(line, index) in lineItems"
                    :key="index"
                    class="border-border space-y-4 rounded-md border p-4"
                    data-test="purchase-order-line-item"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="`inventory_item_id_${index}`">Inventory item</Label>
                            <select
                                :id="`inventory_item_id_${index}`"
                                :name="`items[${index}][inventory_item_id]`"
                                v-model="line.inventory_item_id"
                                required
                                class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="" disabled>Select item</option>
                                <option
                                    v-for="item in inventoryItems"
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                            <InputError :message="errors[`items.${index}.inventory_item_id`]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`quantity_ordered_${index}`">Quantity</Label>
                            <Input
                                :id="`quantity_ordered_${index}`"
                                :name="`items[${index}][quantity_ordered]`"
                                v-model="line.quantity_ordered"
                                type="number"
                                min="1"
                                step="1"
                                required
                            />
                            <InputError :message="errors[`items.${index}.quantity_ordered`]" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label :for="`unit_cost_${index}`">Unit cost ($)</Label>
                            <Input
                                :id="`unit_cost_${index}`"
                                :name="`items[${index}][unit_cost]`"
                                v-model="line.unit_cost"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            />
                            <InputError :message="errors[`items.${index}.unit_cost`]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`batch_number_${index}`">Batch number</Label>
                            <Input
                                :id="`batch_number_${index}`"
                                :name="`items[${index}][batch_number]`"
                                v-model="line.batch_number"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`expiry_date_${index}`">Expiry date</Label>
                            <Input
                                :id="`expiry_date_${index}`"
                                :name="`items[${index}][expiry_date]`"
                                v-model="line.expiry_date"
                                type="date"
                            />
                            <InputError :message="errors[`items.${index}.expiry_date`]" />
                        </div>
                    </div>

                    <Button
                        v-if="lineItems.length > 1"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeLineItem(index)"
                    >
                        Remove line
                    </Button>
                </div>

                <InputError :message="errors.items" />
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing" data-test="submit-purchase-order-button">
                    Create purchase order
                </Button>
                <Button type="button" variant="outline" as-child>
                    <Link :href="purchaseOrdersIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
