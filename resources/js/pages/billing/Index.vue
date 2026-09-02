<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as billingIndex, show as billingShow } from '@/routes/billing';

type InvoiceListItem = {
    id: number;
    invoice_number: string;
    status: string;
    status_label: string;
    issued_at_formatted: string;
    patient_name: string;
    patient_number: string;
    total_cents: number;
    total_formatted: string;
    balance_cents: number;
    balance_formatted: string;
};

type PaginatedInvoices = {
    data: InvoiceListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

defineProps<{
    invoices: PaginatedInvoices;
    search: string;
}>();

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'paid') {
        return 'secondary';
    }

    if (status === 'partially_paid') {
        return 'outline';
    }

    if (status === 'cancelled' || status === 'refunded') {
        return 'destructive';
    }

    return 'default';
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Billing',
                href: billingIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Billing" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Billing"
            description="Invoices, payments, and receipts"
        />

        <form :action="billingIndex().url" method="get" class="flex gap-2">
            <Input
                name="search"
                :default-value="search"
                placeholder="Search by invoice number or patient"
                class="max-w-md"
            />
            <Button type="submit" variant="secondary">Search</Button>
        </form>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Invoice</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Patient</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Balance</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="6" class="text-muted-foreground px-4 py-8 text-center">
                            No invoices found.
                        </td>
                    </tr>
                    <tr v-for="invoice in invoices.data" :key="invoice.id">
                        <td class="px-4 py-3">
                            <Link
                                :href="billingShow(invoice.id)"
                                class="text-primary hover:underline"
                            >
                                {{ invoice.invoice_number }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">{{ invoice.issued_at_formatted }}</td>
                        <td class="px-4 py-3">
                            <div>{{ invoice.patient_name }}</div>
                            <div class="text-muted-foreground text-xs">{{ invoice.patient_number }}</div>
                        </td>
                        <td class="px-4 py-3">{{ invoice.total_formatted }}</td>
                        <td class="px-4 py-3">{{ invoice.balance_formatted }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="statusVariant(invoice.status)">
                                {{ invoice.status_label }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="invoices.links.length > 3" class="flex flex-wrap gap-2">
            <Button
                v-for="link in invoices.links"
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
