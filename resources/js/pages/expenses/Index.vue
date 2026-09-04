<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ExpenseController from '@/actions/App/Http/Controllers/ExpenseController';
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
import { index as expensesIndex } from '@/routes/expenses';

type ExpenseListItem = {
    id: number;
    description: string;
    category: string;
    amount_cents: number;
    amount_formatted: string;
    expense_date: string;
    expense_date_formatted: string;
    recorded_by_name: string;
    notes: string | null;
};

type ClosingListItem = {
    id: number;
    closing_date: string;
    closing_date_formatted: string;
    system_cash_total_cents: number;
    system_cash_total_formatted: string;
    counted_cash_cents: number;
    counted_cash_formatted: string;
    difference_cents: number;
    difference_formatted: string;
    closed_by_name: string;
    closed_at_formatted: string;
    notes: string | null;
};

type ReconciliationListItem = {
    id: number;
    reconciliation_date: string;
    reconciliation_date_formatted: string;
    provider: string;
    transaction_count: number;
    system_total_cents: number;
    system_total_formatted: string;
    provider_total_cents: number;
    provider_total_formatted: string;
    difference_cents: number;
    difference_formatted: string;
    status: string;
    status_label: string;
    reconciled_by_name: string;
    reconciled_at_formatted: string;
    notes: string | null;
};

type PaginatedExpenses = {
    data: ExpenseListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Option = {
    value: string;
    label: string;
};

const props = defineProps<{
    expenses: PaginatedExpenses;
    canCreate: boolean;
    canCloseCash: boolean;
    canReconcileMobileMoney: boolean;
    todayClosingDate: string;
    todaySystemCashTotalCents: number;
    todaySystemCashTotalFormatted: string;
    todayClosing: ClosingListItem | null;
    recentClosings: ClosingListItem[];
    recentReconciliations: ReconciliationListItem[];
    mobileMoneyProviders: Option[];
    expenseCategories: Option[];
}>();

const showExpenseForm = ref(false);
const showClosingForm = ref(false);
const showReconciliationForm = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Expenses',
                href: expensesIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Expenses" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Expenses"
                description="Record clinic expenses, daily cash closings, and mobile money reconciliation"
            />

            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="canCreate"
                    variant="outline"
                    data-test="toggle-expense-form-button"
                    @click="showExpenseForm = !showExpenseForm"
                >
                    Record expense
                </Button>
                <Button
                    v-if="canCloseCash && !todayClosing"
                    variant="outline"
                    data-test="toggle-cash-close-form-button"
                    @click="showClosingForm = !showClosingForm"
                >
                    Cash close
                </Button>
                <Button
                    v-if="canReconcileMobileMoney"
                    variant="outline"
                    data-test="toggle-mm-recon-form-button"
                    @click="showReconciliationForm = !showReconciliationForm"
                >
                    MM reconciliation
                </Button>
            </div>
        </div>

        <Card v-if="showExpenseForm && canCreate">
            <CardHeader>
                <CardTitle>Record expense</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="ExpenseController.store.form()"
                    class="grid gap-4 sm:grid-cols-2"
                    data-test="expense-form"
                >
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            name="description"
                            required
                            data-test="expense-description-input"
                        />
                        <InputError name="description" />
                    </div>

                    <div class="space-y-2">
                        <Label for="category">Category</Label>
                        <select
                            id="category"
                            name="category"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            data-test="expense-category-select"
                        >
                            <option
                                v-for="category in expenseCategories"
                                :key="category.value"
                                :value="category.value"
                            >
                                {{ category.label }}
                            </option>
                        </select>
                        <InputError name="category" />
                    </div>

                    <div class="space-y-2">
                        <Label for="amount">Amount</Label>
                        <Input
                            id="amount"
                            name="amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            data-test="expense-amount-input"
                        />
                        <InputError name="amount" />
                    </div>

                    <div class="space-y-2">
                        <Label for="expense_date">Date</Label>
                        <Input
                            id="expense_date"
                            name="expense_date"
                            type="date"
                            :default-value="todayClosingDate"
                            required
                            data-test="expense-date-input"
                        />
                        <InputError name="expense_date" />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="notes">Notes</Label>
                        <Input id="notes" name="notes" data-test="expense-notes-input" />
                        <InputError name="notes" />
                    </div>

                    <div class="sm:col-span-2">
                        <Button type="submit" data-test="submit-expense-button">
                            Save expense
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card v-if="showClosingForm && canCloseCash && !todayClosing">
            <CardHeader>
                <CardTitle>Daily cash close</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <p class="text-muted-foreground text-sm">
                    System cash total for {{ todayClosingDate }}:
                    <span class="text-foreground font-medium">{{ todaySystemCashTotalFormatted }}</span>
                </p>

                <Form
                    v-bind="ExpenseController.storeDailyClosing.form()"
                    class="grid gap-4 sm:grid-cols-2"
                    data-test="cash-close-form"
                >
                    <input type="hidden" name="closing_date" :value="todayClosingDate" />

                    <div class="space-y-2">
                        <Label for="counted_cash">Counted cash</Label>
                        <Input
                            id="counted_cash"
                            name="counted_cash"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            data-test="cash-close-counted-input"
                        />
                        <InputError name="counted_cash" />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="closing_notes">Notes</Label>
                        <Input id="closing_notes" name="notes" data-test="cash-close-notes-input" />
                        <InputError name="notes" />
                    </div>

                    <div class="sm:col-span-2">
                        <Button type="submit" data-test="submit-cash-close-button">
                            Close cash for today
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card v-if="todayClosing">
            <CardHeader>
                <CardTitle>Today&apos;s cash close</CardTitle>
            </CardHeader>
            <CardContent class="text-sm">
                <p data-test="today-closing-summary">
                    Closed by {{ todayClosing.closed_by_name }} —
                    counted {{ todayClosing.counted_cash_formatted }},
                    system {{ todayClosing.system_cash_total_formatted }},
                    difference {{ todayClosing.difference_formatted }}
                </p>
            </CardContent>
        </Card>

        <Card v-if="showReconciliationForm && canReconcileMobileMoney">
            <CardHeader>
                <CardTitle>Mobile money reconciliation</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="ExpenseController.storeMobileMoneyReconciliation.form()"
                    class="grid gap-4 sm:grid-cols-2"
                    data-test="mm-recon-form"
                >
                    <div class="space-y-2">
                        <Label for="reconciliation_date">Date</Label>
                        <Input
                            id="reconciliation_date"
                            name="reconciliation_date"
                            type="date"
                            :default-value="todayClosingDate"
                            required
                            data-test="mm-recon-date-input"
                        />
                        <InputError name="reconciliation_date" />
                    </div>

                    <div class="space-y-2">
                        <Label for="provider">Provider</Label>
                        <select
                            id="provider"
                            name="provider"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            required
                            data-test="mm-recon-provider-select"
                        >
                            <option
                                v-for="provider in mobileMoneyProviders"
                                :key="provider.value"
                                :value="provider.value"
                            >
                                {{ provider.label }}
                            </option>
                        </select>
                        <InputError name="provider" />
                    </div>

                    <div class="space-y-2">
                        <Label for="provider_total">Provider total</Label>
                        <Input
                            id="provider_total"
                            name="provider_total"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            data-test="mm-recon-provider-total-input"
                        />
                        <InputError name="provider_total" />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="recon_notes">Notes</Label>
                        <Input id="recon_notes" name="notes" data-test="mm-recon-notes-input" />
                        <InputError name="notes" />
                    </div>

                    <div class="sm:col-span-2">
                        <Button type="submit" data-test="submit-mm-recon-button">
                            Save reconciliation
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Description</th>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Amount</th>
                        <th class="px-4 py-3 font-medium">Recorded by</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="expenses.data.length === 0">
                        <td colspan="5" class="text-muted-foreground px-4 py-8 text-center" data-test="expenses-empty-state">
                            No expenses recorded yet.
                        </td>
                    </tr>
                    <tr v-for="expense in expenses.data" :key="expense.id" data-test="expense-row">
                        <td class="px-4 py-3">{{ expense.expense_date_formatted }}</td>
                        <td class="px-4 py-3">{{ expense.description }}</td>
                        <td class="px-4 py-3">{{ expense.category }}</td>
                        <td class="px-4 py-3">{{ expense.amount_formatted }}</td>
                        <td class="px-4 py-3">{{ expense.recorded_by_name }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="recentClosings.length > 0" class="space-y-3">
            <h2 class="text-lg font-semibold">Recent cash closings</h2>
            <div class="divide-border overflow-hidden rounded-md border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">System</th>
                            <th class="px-4 py-3 font-medium">Counted</th>
                            <th class="px-4 py-3 font-medium">Difference</th>
                            <th class="px-4 py-3 font-medium">Closed by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-border divide-y">
                        <tr v-for="closing in recentClosings" :key="closing.id" data-test="cash-closing-row">
                            <td class="px-4 py-3">{{ closing.closing_date_formatted }}</td>
                            <td class="px-4 py-3">{{ closing.system_cash_total_formatted }}</td>
                            <td class="px-4 py-3">{{ closing.counted_cash_formatted }}</td>
                            <td class="px-4 py-3">{{ closing.difference_formatted }}</td>
                            <td class="px-4 py-3">{{ closing.closed_by_name }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="recentReconciliations.length > 0" class="space-y-3">
            <h2 class="text-lg font-semibold">Recent mobile money reconciliations</h2>
            <div class="divide-border overflow-hidden rounded-md border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Date</th>
                            <th class="px-4 py-3 font-medium">Provider</th>
                            <th class="px-4 py-3 font-medium">System</th>
                            <th class="px-4 py-3 font-medium">Provider total</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-border divide-y">
                        <tr
                            v-for="reconciliation in recentReconciliations"
                            :key="reconciliation.id"
                            data-test="mm-recon-row"
                        >
                            <td class="px-4 py-3">{{ reconciliation.reconciliation_date_formatted }}</td>
                            <td class="px-4 py-3">{{ reconciliation.provider }}</td>
                            <td class="px-4 py-3">{{ reconciliation.system_total_formatted }}</td>
                            <td class="px-4 py-3">{{ reconciliation.provider_total_formatted }}</td>
                            <td class="px-4 py-3">
                                <Badge variant="outline">{{ reconciliation.status_label }}</Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
