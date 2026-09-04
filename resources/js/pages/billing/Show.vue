<script setup lang="ts">
import { computed, ref } from 'vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import BillingController from '@/actions/App/Http/Controllers/BillingController';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as billingIndex } from '@/routes/billing';
import { show as billingReceiptShow } from '@/routes/billing/receipts';

type InvoiceItem = {
    id: number;
    description: string;
    quantity: number;
    unit_price_cents: number;
    unit_price_formatted: string;
    tax_cents: number;
    tax_formatted: string;
    line_total_cents: number;
    line_total_formatted: string;
};

type Payment = {
    id: number;
    payment_number: string;
    amount_cents: number;
    amount_formatted: string;
    method: string;
    method_label: string;
    status: string;
    status_label: string;
    paid_at_formatted: string | null;
    received_by_name: string;
    reference_number: string | null;
    receipt_id: number | null;
    receipt_number: string | null;
    mobile_money: {
        provider: string;
        payer_phone: string;
        transaction_id: string;
        verification_status: string;
    } | null;
};

type InvoiceDetail = {
    id: number;
    invoice_number: string;
    status: string;
    status_label: string;
    issued_at_formatted: string;
    issuer_name: string;
    patient: {
        id: number;
        full_name: string;
        patient_number: string;
    };
    treatment_id: number | null;
    subtotal_cents: number;
    subtotal_formatted: string;
    discount_cents: number;
    discount_formatted: string;
    tax_cents: number;
    tax_formatted: string;
    total_cents: number;
    total_formatted: string;
    amount_paid_cents: number;
    amount_paid_formatted: string;
    balance_cents: number;
    balance_formatted: string;
    items: InvoiceItem[];
    payments: Payment[];
};

type Option = {
    value: string;
    label: string;
};

const props = defineProps<{
    invoice: InvoiceDetail;
    canPay: boolean;
    canRefund: boolean;
    paymentMethods: Option[];
    mobileMoneyProviders: Option[];
    verificationStatuses: Option[];
}>();

const selectedMethod = ref('cash');
const showPayForm = ref(false);
const showRefundForm = ref(false);
const refundPaymentNumber = ref('');

const isMobileMoney = computed(() =>
    ['zaad', 'sahal', 'edahab', 'mycash'].includes(selectedMethod.value),
);

const requiresReference = computed(() =>
    ['card', 'bank_transfer', 'insurance', 'zaad', 'sahal', 'edahab', 'mycash'].includes(selectedMethod.value),
);

const balanceDollars = computed(() => (props.invoice.balance_cents / 100).toFixed(2));

const completedPayments = computed(() =>
    props.invoice.payments.filter((payment) => payment.status === 'completed'),
);

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
            { title: 'Billing', href: billingIndex() },
            { title: 'Invoice', href: billingIndex() },
        ],
    },
});
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                variant="small"
                :title="invoice.invoice_number"
                :description="`${invoice.patient.full_name} (${invoice.patient.patient_number})`"
            />

            <Badge :variant="statusVariant(invoice.status)">
                {{ invoice.status_label }}
            </Badge>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Invoice details</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Issued</dt>
                        <dd>{{ invoice.issued_at_formatted }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Issued by</dt>
                        <dd>{{ invoice.issuer_name }}</dd>
                    </div>
                </dl>
            </section>

            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Totals</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Subtotal</dt>
                        <dd>{{ invoice.subtotal_formatted }}</dd>
                    </div>
                    <div v-if="invoice.discount_cents > 0" class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Discount</dt>
                        <dd>-{{ invoice.discount_formatted }}</dd>
                    </div>
                    <div v-if="invoice.tax_cents > 0" class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Tax</dt>
                        <dd>{{ invoice.tax_formatted }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 font-medium">
                        <dt>Total</dt>
                        <dd>{{ invoice.total_formatted }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Paid</dt>
                        <dd>{{ invoice.amount_paid_formatted }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 font-medium">
                        <dt>Balance</dt>
                        <dd>{{ invoice.balance_formatted }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section class="space-y-3 rounded-md border p-4">
            <h3 class="text-sm font-medium">Fee lines</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-muted-foreground text-left">
                        <tr>
                            <th class="pb-2 font-medium">Description</th>
                            <th class="pb-2 font-medium">Qty</th>
                            <th class="pb-2 font-medium">Unit price</th>
                            <th class="pb-2 font-medium">Tax</th>
                            <th class="pb-2 text-right font-medium">Line total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-border divide-y">
                        <tr v-for="item in invoice.items" :key="item.id">
                            <td class="py-2">{{ item.description }}</td>
                            <td class="py-2">{{ item.quantity }}</td>
                            <td class="py-2">{{ item.unit_price_formatted }}</td>
                            <td class="py-2">{{ item.tax_formatted }}</td>
                            <td class="py-2 text-right">{{ item.line_total_formatted }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-3 rounded-md border p-4">
            <h3 class="text-sm font-medium">Payments</h3>
            <div v-if="invoice.payments.length === 0" class="text-muted-foreground text-sm">
                No payments recorded yet.
            </div>
            <ul v-else class="space-y-3 text-sm">
                <li
                    v-for="payment in invoice.payments"
                    :key="payment.id"
                    class="flex flex-col gap-1 rounded-md border p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <div class="font-medium">{{ payment.payment_number }}</div>
                        <div class="text-muted-foreground">
                            {{ payment.method_label }} · {{ payment.amount_formatted }}
                            · {{ payment.status_label }}
                        </div>
                        <div v-if="payment.mobile_money" class="text-muted-foreground text-xs">
                            {{ payment.mobile_money.provider }} · {{ payment.mobile_money.transaction_id }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            v-if="payment.receipt_id"
                            as-child
                            size="sm"
                            variant="outline"
                        >
                            <Link :href="billingReceiptShow(payment.receipt_id!)">
                                Receipt {{ payment.receipt_number }}
                            </Link>
                        </Button>
                    </div>
                </li>
            </ul>
        </section>

        <div v-if="canPay && invoice.balance_cents > 0" class="flex gap-2">
            <Button @click="showPayForm = !showPayForm" data-test="record-payment-button">
                {{ showPayForm ? 'Hide payment form' : 'Record payment' }}
            </Button>
        </div>

        <Card v-if="showPayForm && canPay && invoice.balance_cents > 0">
            <CardHeader>
                <CardTitle>Record payment</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="BillingController.pay.form(invoice.id)"
                    class="space-y-4"
                    #default="{ errors, processing }"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="amount">Amount ($)</Label>
                            <Input
                                id="amount"
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :default-value="balanceDollars"
                                required
                            />
                            <InputError :message="errors.amount" />
                        </div>

                        <div class="space-y-2">
                            <Label for="method">Method</Label>
                            <select
                                id="method"
                                name="method"
                                v-model="selectedMethod"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                required
                            >
                                <option
                                    v-for="method in paymentMethods"
                                    :key="method.value"
                                    :value="method.value"
                                >
                                    {{ method.label }}
                                </option>
                            </select>
                            <InputError :message="errors.method" />
                        </div>
                    </div>

                    <div v-if="requiresReference" class="space-y-2">
                        <Label for="reference_number">Reference number</Label>
                        <Input id="reference_number" name="reference_number" />
                        <InputError :message="errors.reference_number" />
                    </div>

                    <template v-if="isMobileMoney">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="payer_phone">Payer phone</Label>
                                <Input id="payer_phone" name="payer_phone" />
                                <InputError :message="errors.payer_phone" />
                            </div>

                            <div class="space-y-2">
                                <Label for="transaction_id">Transaction ID</Label>
                                <Input id="transaction_id" name="transaction_id" />
                                <InputError :message="errors.transaction_id" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="provider">Provider</Label>
                                <select
                                    id="provider"
                                    name="provider"
                                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    <option value="">Select provider</option>
                                    <option
                                        v-for="provider in mobileMoneyProviders"
                                        :key="provider.value"
                                        :value="provider.value"
                                    >
                                        {{ provider.label }}
                                    </option>
                                </select>
                                <InputError :message="errors.provider" />
                            </div>

                            <div class="space-y-2">
                                <Label for="verification_status">Verification status</Label>
                                <select
                                    id="verification_status"
                                    name="verification_status"
                                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    <option value="">Select status</option>
                                    <option
                                        v-for="status in verificationStatuses"
                                        :key="status.value"
                                        :value="status.value"
                                    >
                                        {{ status.label }}
                                    </option>
                                </select>
                                <InputError :message="errors.verification_status" />
                            </div>
                        </div>
                    </template>

                    <Button type="submit" :disabled="processing" data-test="submit-payment-button">
                        Submit payment
                    </Button>
                </Form>
            </CardContent>
        </Card>

        <div v-if="canRefund && completedPayments.length > 0" class="flex gap-2">
            <Button variant="outline" @click="showRefundForm = !showRefundForm">
                {{ showRefundForm ? 'Hide refund form' : 'Process refund' }}
            </Button>
        </div>

        <Card v-if="showRefundForm && canRefund">
            <CardHeader>
                <CardTitle>Process refund</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="BillingController.refund.form(invoice.id)"
                    class="space-y-4"
                    #default="{ errors, processing }"
                >
                    <div class="space-y-2">
                        <Label for="original_payment_number">Original payment</Label>
                        <select
                            id="original_payment_number"
                            name="original_payment_number"
                            v-model="refundPaymentNumber"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            required
                        >
                            <option value="">Select payment</option>
                            <option
                                v-for="payment in completedPayments"
                                :key="payment.id"
                                :value="payment.payment_number"
                            >
                                {{ payment.payment_number }} — {{ payment.amount_formatted }}
                            </option>
                        </select>
                        <InputError :message="errors.original_payment_number" />
                    </div>

                    <div class="space-y-2">
                        <Label for="refund_amount">Refund amount ($)</Label>
                        <Input
                            id="refund_amount"
                            name="amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                        />
                        <InputError :message="errors.amount" />
                    </div>

                    <Button type="submit" variant="destructive" :disabled="processing">
                        Process refund
                    </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
