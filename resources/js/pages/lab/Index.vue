<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create as labCreate, index as labIndex, show as labShow } from '@/routes/lab';

type LabOrderListItem = {
    id: number;
    number: string;
    description: string;
    status: string;
    status_label: string;
    due_date: string | null;
    due_date_formatted: string | null;
    patient_name: string;
    patient_number: string;
    dentist_name: string;
};

type PaginatedOrders = {
    data: LabOrderListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

defineProps<{
    orders: PaginatedOrders;
    search: string;
    canCreate: boolean;
}>();

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'fitted' || status === 'returned') {
        return 'secondary';
    }

    if (status === 'cancelled') {
        return 'destructive';
    }

    if (status === 'ready') {
        return 'default';
    }

    return 'outline';
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Lab',
                href: labIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Lab orders" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Lab orders"
                description="Track prosthetic and laboratory work"
            />

            <Button v-if="canCreate" as-child>
                <Link :href="labCreate()" data-test="create-lab-order-link">New lab order</Link>
            </Button>
        </div>

        <form :action="labIndex().url" method="get" class="flex gap-2">
            <Input
                name="search"
                :default-value="search"
                placeholder="Search by order number, description, or patient"
                class="max-w-md"
                data-test="lab-search-input"
            />
            <Button type="submit" variant="secondary" data-test="lab-search-button">Search</Button>
        </form>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Order</th>
                        <th class="px-4 py-3 font-medium">Patient</th>
                        <th class="px-4 py-3 font-medium">Description</th>
                        <th class="px-4 py-3 font-medium">Dentist</th>
                        <th class="px-4 py-3 font-medium">Due</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="orders.data.length === 0">
                        <td colspan="6" class="text-muted-foreground px-4 py-8 text-center">
                            No lab orders found.
                        </td>
                    </tr>
                    <tr v-for="order in orders.data" :key="order.id">
                        <td class="px-4 py-3">
                            <Link
                                :href="labShow(order.id)"
                                class="text-primary hover:underline"
                                data-test="lab-order-link"
                            >
                                {{ order.number }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ order.patient_name }}</div>
                            <div class="text-muted-foreground text-xs">{{ order.patient_number }}</div>
                        </td>
                        <td class="px-4 py-3">{{ order.description }}</td>
                        <td class="px-4 py-3">{{ order.dentist_name }}</td>
                        <td class="px-4 py-3">{{ order.due_date_formatted ?? '—' }}</td>
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
