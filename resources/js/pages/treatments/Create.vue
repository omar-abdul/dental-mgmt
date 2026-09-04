<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import TreatmentController from '@/actions/App/Http/Controllers/TreatmentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PatientPicker, { type PatientSearchResult } from '@/components/PatientPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create as treatmentsCreate, index as treatmentsIndex } from '@/routes/treatments';

type Option = {
    id: number;
    label: string;
};

type FeeItemOption = Option & {
    price_cents: number;
};

type StatusOption = {
    value: string;
    label: string;
};

type CriticalAlert = {
    label: string;
};

type CriticalAlerts = {
    allergies: CriticalAlert[];
    conditions: CriticalAlert[];
    medications: CriticalAlert[];
};

const props = defineProps<{
    dentists: Option[];
    feeItems: FeeItemOption[];
    appointments: Option[];
    statuses: StatusOption[];
    defaultDentistId: number | null;
    selectedPatientId: number | null;
    selectedPatient: PatientSearchResult | null;
    criticalAlerts: CriticalAlerts;
}>();

const selectedPatient = ref(props.selectedPatientId?.toString() ?? '');
const procedureCount = ref(1);
const prescriptionItemCount = ref(1);

watch(selectedPatient, (patientId) => {
    router.get(
        treatmentsCreate.url({ query: patientId ? { patient_id: patientId } : {} }),
        {},
        { preserveState: true, preserveScroll: true },
    );
});

const hasCriticalAlerts = props.criticalAlerts.allergies.length > 0
    || props.criticalAlerts.conditions.length > 0
    || props.criticalAlerts.medications.length > 0;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Treatments', href: treatmentsIndex() },
            { title: 'Record', href: treatmentsCreate() },
        ],
    },
});
</script>

<template>
    <Head title="Record treatment" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Record treatment"
            description="Document diagnosis, procedures, and prescription"
        />

        <div
            v-if="hasCriticalAlerts"
            class="border-destructive/50 bg-destructive/5 space-y-2 rounded-md border p-4"
        >
            <h3 class="text-destructive text-sm font-medium">Critical alerts</h3>
            <div v-if="criticalAlerts.allergies.length > 0" class="text-sm">
                <span class="font-medium">Allergies:</span>
                {{ criticalAlerts.allergies.map((item) => item.label).join(', ') }}
            </div>
            <div v-if="criticalAlerts.conditions.length > 0" class="text-sm">
                <span class="font-medium">Conditions:</span>
                {{ criticalAlerts.conditions.map((item) => item.label).join(', ') }}
            </div>
            <div v-if="criticalAlerts.medications.length > 0" class="text-sm">
                <span class="font-medium">Medications:</span>
                {{ criticalAlerts.medications.map((item) => item.label).join(', ') }}
            </div>
        </div>

        <Form
            v-bind="TreatmentController.store.form()"
            v-slot="{ errors, processing }"
            class="max-w-3xl space-y-8"
        >
            <section class="space-y-4">
                <h3 class="text-sm font-medium">Patient &amp; provider</h3>

                <div class="grid gap-4 sm:grid-cols-2">
                    <PatientPicker
                        id="patient_id"
                        v-model="selectedPatient"
                        :selected="props.selectedPatient"
                        required
                        :error="errors.patient_id"
                    />

                    <div class="grid gap-2">
                        <Label for="dentist_id">Dentist</Label>
                        <select
                            id="dentist_id"
                            name="dentist_id"
                            required
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="">Select dentist</option>
                            <option
                                v-for="dentist in dentists"
                                :key="dentist.id"
                                :value="dentist.id"
                                :selected="dentist.id === defaultDentistId"
                            >
                                {{ dentist.label }}
                            </option>
                        </select>
                        <InputError :message="errors.dentist_id" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="appointment_id">Linked appointment (optional)</Label>
                        <select
                            id="appointment_id"
                            name="appointment_id"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="">None</option>
                            <option
                                v-for="appointment in appointments"
                                :key="appointment.id"
                                :value="appointment.id"
                            >
                                {{ appointment.label }}
                            </option>
                        </select>
                        <InputError :message="errors.appointment_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            name="status"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option
                                v-for="status in statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </option>
                        </select>
                        <InputError :message="errors.status" />
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-sm font-medium">Diagnosis</h3>

                <div class="grid gap-2">
                    <Label for="diagnosis">Diagnosis</Label>
                    <textarea
                        id="diagnosis"
                        name="diagnosis"
                        required
                        rows="3"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    />
                    <InputError :message="errors.diagnosis" />
                </div>

                <div class="grid gap-2">
                    <Label for="notes">Notes (optional)</Label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="2"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    />
                    <InputError :message="errors.notes" />
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium">Procedures</h3>
                    <Button type="button" variant="outline" size="sm" @click="procedureCount++">
                        Add procedure
                    </Button>
                </div>

                <div
                    v-for="index in procedureCount"
                    :key="`procedure-${index}`"
                    class="grid gap-4 rounded-md border p-4 sm:grid-cols-3"
                >
                    <div class="grid gap-2 sm:col-span-1">
                        <Label :for="`fee_item_id_${index}`">Fee item</Label>
                        <select
                            :id="`fee_item_id_${index}`"
                            :name="`procedures[${index - 1}][fee_item_id]`"
                            required
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="">Select fee item</option>
                            <option
                                v-for="feeItem in feeItems"
                                :key="feeItem.id"
                                :value="feeItem.id"
                            >
                                {{ feeItem.label }}
                            </option>
                        </select>
                        <InputError :message="errors[`procedures.${index - 1}.fee_item_id`]" />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`quantity_${index}`">Quantity</Label>
                        <Input
                            :id="`quantity_${index}`"
                            :name="`procedures[${index - 1}][quantity]`"
                            type="number"
                            min="1"
                            :default-value="1"
                            required
                        />
                        <InputError :message="errors[`procedures.${index - 1}.quantity`]" />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`tooth_fdi_${index}`">Tooth FDI (optional)</Label>
                        <Input
                            :id="`tooth_fdi_${index}`"
                            :name="`procedures[${index - 1}][tooth_fdi]`"
                            maxlength="10"
                        />
                        <InputError :message="errors[`procedures.${index - 1}.tooth_fdi`]" />
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium">Prescription items</h3>
                    <Button type="button" variant="outline" size="sm" @click="prescriptionItemCount++">
                        Add item
                    </Button>
                </div>

                <div
                    v-for="index in prescriptionItemCount"
                    :key="`rx-${index}`"
                    class="grid gap-4 rounded-md border p-4"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :for="`medication_${index}`">Medication</Label>
                            <Input
                                :id="`medication_${index}`"
                                :name="`prescription_items[${index - 1}][medication]`"
                                required
                            />
                            <InputError :message="errors[`prescription_items.${index - 1}.medication`]" />
                        </div>

                        <div class="grid gap-2">
                            <Label :for="`dosage_${index}`">Dosage</Label>
                            <Input
                                :id="`dosage_${index}`"
                                :name="`prescription_items[${index - 1}][dosage]`"
                                required
                            />
                            <InputError :message="errors[`prescription_items.${index - 1}.dosage`]" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`instructions_${index}`">Instructions</Label>
                        <textarea
                            :id="`instructions_${index}`"
                            :name="`prescription_items[${index - 1}][instructions]`"
                            required
                            rows="2"
                            class="border-input bg-background ring-offset-background focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        />
                        <InputError :message="errors[`prescription_items.${index - 1}.instructions`]" />
                    </div>
                </div>
            </section>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing" data-test="save-treatment-button">Save treatment</Button>
                <Button as-child variant="outline">
                    <Link :href="treatmentsIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
