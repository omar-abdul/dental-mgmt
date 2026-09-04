<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create as imagingCreate, index as imagingIndex, show as imagingShow } from '@/routes/imaging';

type ImagingOrderListItem = {
    id: number;
    number: string;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    patient_name: string;
    patient_number: string;
    dentist_name: string;
};

type PaginatedOrders = {
    data: ImagingOrderListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

defineProps<{
    orders: PaginatedOrders;
    search: string;
    canCreate: boolean;
}>();

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'completed') {
        return 'default';
    }

    if (status === 'cancelled') {
        return 'destructive';
    }

    if (status === 'scheduled') {
        return 'secondary';
    }

    return 'outline';
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Imaging',
                href: imagingIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Imaging orders" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Imaging orders"
                description="Track radiograph and imaging study orders"
            />

            <Button v-if="canCreate" as-child>
                <Link :href="imagingCreate()" data-test="create-imaging-order-link">New imaging order</Link>
            </Button>
        </div>

        <form :action="imagingIndex().url" method="get" class="flex gap-2">
            <Input
                name="search"
                :default-value="search"
                placeholder="Search by order number, notes, or patient"
                class="max-w-md"
                data-test="imaging-search-input"
            />
            <Button type="submit" variant="secondary" data-test="imaging-search-button">Search</Button>
        </form>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Order</th>
                        <th class="px-4 py-3 font-medium">Patient</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Dentist</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="orders.data.length === 0">
                        <td colspan="5" class="text-muted-foreground px-4 py-8 text-center">
                            No imaging orders found.
                        </td>
                    </tr>
                    <tr v-for="order in orders.data" :key="order.id">
                        <td class="px-4 py-3">
                            <Link
                                :href="imagingShow(order.id)"
                                class="text-primary hover:underline"
                                data-test="imaging-order-link"
                            >
                                {{ order.number }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ order.patient_name }}</div>
                            <div class="text-muted-foreground text-xs">{{ order.patient_number }}</div>
                        </td>
                        <td class="px-4 py-3">{{ order.type_label }}</td>
                        <td class="px-4 py-3">{{ order.dentist_name }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="statusVariant(order.status)">
                                {{ order.status_label }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="orders.links.length > 3" class="flex flex-wrap gap-2">
            <Button
                v-for="link in orders.links"
                :key="link.label"
                as-child
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
                :disabled="!link.url"
            >
                <Link v-if="link.url" :href="link.url" v-html="link.label" />
                <span v-else v-html="link.label" />
            </Button>
        </nav>
    </div>
</template>
