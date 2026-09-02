<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import TreatmentController from '@/actions/App/Http/Controllers/TreatmentController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as treatmentsIndex } from '@/routes/treatments';

type Procedure = {
    id: number;
    fee_name: string;
    tooth_fdi: string | null;
    quantity: number;
    fee_cents: number;
    fee_formatted: string;
};

type PrescriptionItem = {
    medication: string;
    dosage: string;
    instructions: string;
};

type TreatmentDetail = {
    id: number;
    diagnosis: string;
    status: string;
    status_label: string;
    diagnosed_at: string;
    diagnosed_at_formatted: string;
    notes: string | null;
    patient: {
        id: number;
        full_name: string;
        patient_number: string;
    };
    dentist_name: string;
    appointment: {
        id: number;
        number: string;
    } | null;
    procedures: Procedure[];
    prescription: {
        number: string;
        prescriber_name: string;
        prescribed_at_formatted: string;
        items: PrescriptionItem[];
    } | null;
};

defineProps<{
    treatment: TreatmentDetail;
    canComplete: boolean;
}>();

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'completed') {
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
            { title: 'Treatments', href: treatmentsIndex() },
        ],
    },
});
</script>

<template>
    <Head :title="`Treatment — ${treatment.patient.full_name}`" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                variant="small"
                :title="treatment.diagnosis"
                :description="`${treatment.patient.full_name} (${treatment.patient.patient_number})`"
            />

            <Badge :variant="statusVariant(treatment.status)">
                {{ treatment.status_label }}
            </Badge>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Overview</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Diagnosed</dt>
                        <dd>{{ treatment.diagnosed_at_formatted }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Dentist</dt>
                        <dd>{{ treatment.dentist_name }}</dd>
                    </div>
                    <div v-if="treatment.appointment" class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Appointment</dt>
                        <dd>{{ treatment.appointment.number }}</dd>
                    </div>
                    <div v-if="treatment.notes" class="space-y-1">
                        <dt class="text-muted-foreground">Notes</dt>
                        <dd>{{ treatment.notes }}</dd>
                    </div>
                </dl>
            </section>

            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Procedures</h3>
                <ul v-if="treatment.procedures.length > 0" class="space-y-2 text-sm">
                    <li
                        v-for="procedure in treatment.procedures"
                        :key="procedure.id"
                        class="flex items-start justify-between gap-4"
                    >
                        <div>
                            <div>{{ procedure.fee_name }}</div>
                            <div v-if="procedure.tooth_fdi" class="text-muted-foreground text-xs">
                                Tooth {{ procedure.tooth_fdi }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div>× {{ procedure.quantity }}</div>
                            <div class="text-muted-foreground">{{ procedure.fee_formatted }}</div>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">No procedures recorded.</p>
            </section>
        </div>

        <section v-if="treatment.prescription" class="space-y-3 rounded-md border p-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-sm font-medium">Prescription</h3>
                <p class="text-muted-foreground text-xs">
                    {{ treatment.prescription.number }} · {{ treatment.prescription.prescriber_name }} ·
                    {{ treatment.prescription.prescribed_at_formatted }}
                </p>
            </div>

            <ul class="space-y-3 text-sm">
                <li
                    v-for="(item, index) in treatment.prescription.items"
                    :key="index"
                    class="rounded-md border p-3"
                >
                    <div class="font-medium">{{ item.medication }} — {{ item.dosage }}</div>
                    <div class="text-muted-foreground mt-1">{{ item.instructions }}</div>
                </li>
            </ul>
        </section>

        <div class="flex gap-3">
            <Form
                v-if="canComplete"
                v-bind="TreatmentController.complete.form(treatment.id)"
                v-slot="{ processing }"
            >
                <Button type="submit" :disabled="processing">Mark completed</Button>
            </Form>

            <Button as-child variant="outline">
                <Link :href="treatmentsIndex()">Back to treatments</Link>
            </Button>
        </div>
    </div>
</template>
