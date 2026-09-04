<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import LabOrderController from '@/actions/App/Http/Controllers/LabOrderController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as labIndex } from '@/routes/lab';
import { show as encountersShow } from '@/routes/encounters';

type NextStatus = {
    value: string;
    label: string;
};

type LabOrderDetail = {
    id: number;
    number: string;
    description: string;
    notes: string | null;
    status: string;
    status_label: string;
    due_date: string | null;
    due_date_formatted: string | null;
    created_at_formatted: string | null;
    patient: {
        id: number;
        full_name: string;
        patient_number: string;
    };
    dentist_name: string;
    treatment: {
        id: number;
        diagnosis: string;
    } | null;
    encounter: {
        id: number;
        number: string;
    } | null;
    next_statuses: NextStatus[];
};

defineProps<{
    order: LabOrderDetail;
    canTransition: boolean;
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
            { title: 'Lab', href: labIndex() },
        ],
    },
});
</script>

<template>
    <Head :title="`Lab order — ${order.number}`" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                variant="small"
                :title="order.number"
                :description="`${order.patient.full_name} (${order.patient.patient_number})`"
            />

            <Badge :variant="statusVariant(order.status)" data-test="lab-order-status-badge">
                {{ order.status_label }}
            </Badge>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Order details</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Description</dt>
                        <dd>{{ order.description }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Dentist</dt>
                        <dd>{{ order.dentist_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Created</dt>
                        <dd>{{ order.created_at_formatted }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Due</dt>
                        <dd>{{ order.due_date_formatted ?? '—' }}</dd>
                    </div>
                    <div v-if="order.treatment" class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Treatment</dt>
                        <dd>{{ order.treatment.diagnosis }}</dd>
                    </div>
                    <div v-if="order.encounter" class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Encounter</dt>
                        <dd>
                            <Link
                                :href="encountersShow(order.encounter.id)"
                                class="text-primary hover:underline"
                            >
                                {{ order.encounter.number }}
                            </Link>
                        </dd>
                    </div>
                    <div v-if="order.notes" class="space-y-1">
                        <dt class="text-muted-foreground">Notes</dt>
                        <dd>{{ order.notes }}</dd>
                    </div>
                </dl>
            </section>

            <section v-if="canTransition && order.next_statuses.length > 0" class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Advance status</h3>
                <p class="text-muted-foreground text-sm">
                    Move this order to the next workflow step.
                </p>
                <div class="flex flex-wrap gap-2">
                    <Form
                        v-for="nextStatus in order.next_statuses"
                        :key="nextStatus.value"
                        v-bind="LabOrderController.transition.form(order.id)"
                        v-slot="{ processing }"
                        class="contents"
                    >
                        <input type="hidden" name="status" :value="nextStatus.value" />
                        <Button
                            type="submit"
                            :disabled="processing"
                            :variant="nextStatus.value === 'cancelled' ? 'destructive' : 'default'"
                            :data-test="`lab-transition-${nextStatus.value}-button`"
                        >
                            {{ nextStatus.label }}
                        </Button>
                    </Form>
                </div>
            </section>
        </div>

        <Button as-child variant="outline">
            <Link :href="labIndex()" data-test="back-to-lab-link">Back to lab orders</Link>
        </Button>
    </div>
</template>
