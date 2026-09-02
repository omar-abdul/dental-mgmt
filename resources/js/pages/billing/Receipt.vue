<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { index as billingIndex } from '@/routes/billing';

type ReceiptDetail = {
    receipt_number: string;
    issued_at_formatted: string | null;
    payment_number: string;
    amount_cents: number;
    amount_formatted: string;
    method: string;
    method_label: string;
    received_by_name: string;
    reference_number: string | null;
    invoice_number: string;
    patient_name: string;
    patient_number: string;
    items: Array<{
        description: string;
        quantity: number;
        line_total_formatted: string;
    }>;
    invoice_total_formatted: string;
    mobile_money: {
        provider: string;
        payer_phone: string;
        transaction_id: string;
    } | null;
};

const props = defineProps<{
    receipt: ReceiptDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [],
    },
});
</script>

<template>
    <Head :title="`Receipt ${receipt.receipt_number}`" />

    <div class="mx-auto max-w-2xl space-y-6 print:max-w-none">
        <div class="flex items-center justify-between print:hidden">
            <Button as-child variant="outline">
                <Link :href="billingIndex()">Back to billing</Link>
            </Button>
            <Button @click="() => window.print()">Print receipt</Button>
        </div>

        <article class="space-y-6 rounded-md border p-8 print:border-0 print:p-0">
            <header class="space-y-1 text-center">
                <h1 class="text-2xl font-semibold">Payment Receipt</h1>
                <p class="text-muted-foreground text-sm">{{ receipt.receipt_number }}</p>
                <p v-if="receipt.issued_at_formatted" class="text-muted-foreground text-sm">
                    {{ receipt.issued_at_formatted }}
                </p>
            </header>

            <section class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Patient</span>
                    <span>{{ receipt.patient_name }} ({{ receipt.patient_number }})</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Invoice</span>
                    <span>{{ receipt.invoice_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Payment</span>
                    <span>{{ receipt.payment_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Method</span>
                    <span>{{ receipt.method_label }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Received by</span>
                    <span>{{ receipt.received_by_name }}</span>
                </div>
                <div v-if="receipt.reference_number" class="flex justify-between">
                    <span class="text-muted-foreground">Reference</span>
                    <span>{{ receipt.reference_number }}</span>
                </div>
                <div v-if="receipt.mobile_money" class="flex justify-between">
                    <span class="text-muted-foreground">Mobile money</span>
                    <span>
                        {{ receipt.mobile_money.provider }} · {{ receipt.mobile_money.transaction_id }}
                    </span>
                </div>
            </section>

            <section class="space-y-2">
                <h2 class="text-sm font-medium">Items</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="pb-2 font-medium">Description</th>
                            <th class="pb-2 font-medium">Qty</th>
                            <th class="pb-2 text-right font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in receipt.items" :key="index" class="border-b">
                            <td class="py-2">{{ item.description }}</td>
                            <td class="py-2">{{ item.quantity }}</td>
                            <td class="py-2 text-right">{{ item.line_total_formatted }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <footer class="space-y-2 border-t pt-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Invoice total</span>
                    <span>{{ receipt.invoice_total_formatted }}</span>
                </div>
                <div class="flex justify-between text-lg font-semibold">
                    <span>Amount paid</span>
                    <span>{{ receipt.amount_formatted }}</span>
                </div>
            </footer>
        </article>
    </div>
</template>
