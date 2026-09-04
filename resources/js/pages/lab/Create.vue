<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import LabOrderController from '@/actions/App/Http/Controllers/LabOrderController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PatientPicker, { type PatientSearchResult } from '@/components/PatientPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create as labCreate, index as labIndex } from '@/routes/lab';

type Option = {
    id: number;
    label: string;
};

const props = defineProps<{
    dentists: Option[];
    treatments: Option[];
    encounters: Option[];
    defaultDentistId: number | null;
    selectedPatientId: number | null;
    selectedPatient: PatientSearchResult | null;
}>();

const selectedPatient = ref(props.selectedPatientId?.toString() ?? '');

watch(selectedPatient, (patientId) => {
    router.get(
        labCreate.url({ query: patientId ? { patient_id: patientId } : {} }),
        {},
        { preserveState: true, preserveScroll: true },
    );
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Lab', href: labIndex() },
            { title: 'New order', href: labCreate() },
        ],
    },
});
</script>

<template>
    <Head title="New lab order" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="New lab order"
            description="Send prosthetic or laboratory work to the lab"
        />

        <Form
            v-bind="LabOrderController.store.form()"
            v-slot="{ errors, processing }"
            class="max-w-2xl space-y-6"
        >
            <div class="space-y-2">
                <Label for="patient_id">Patient</Label>
                <PatientPicker
                    id="patient_id"
                    v-model="selectedPatient"
                    name="patient_id"
                    :selected="props.selectedPatient"
                    required
                    data-test="lab-order-patient-picker"
                />
                <InputError :message="errors.patient_id" />
            </div>

            <div class="space-y-2">
                <Label for="dentist_id">Dentist</Label>
                <select
                    id="dentist_id"
                    name="dentist_id"
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    :default-value="defaultDentistId ?? undefined"
                    required
                    data-test="lab-order-dentist-select"
                >
                    <option value="" disabled selected hidden>Select dentist</option>
                    <option
                        v-for="dentist in dentists"
                        :key="dentist.id"
                        :value="dentist.id"
                        :selected="defaultDentistId === dentist.id"
                    >
                        {{ dentist.label }}
                    </option>
                </select>
                <InputError :message="errors.dentist_id" />
            </div>

            <div v-if="treatments.length > 0" class="space-y-2">
                <Label for="treatment_id">Treatment (optional)</Label>
                <select
                    id="treatment_id"
                    name="treatment_id"
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    data-test="lab-order-treatment-select"
                >
                    <option value="">None</option>
                    <option v-for="treatment in treatments" :key="treatment.id" :value="treatment.id">
                        {{ treatment.label }}
                    </option>
                </select>
                <InputError :message="errors.treatment_id" />
            </div>

            <div v-if="encounters.length > 0" class="space-y-2">
                <Label for="encounter_id">Encounter (optional)</Label>
                <select
                    id="encounter_id"
                    name="encounter_id"
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    data-test="lab-order-encounter-select"
                >
                    <option value="">None</option>
                    <option v-for="encounter in encounters" :key="encounter.id" :value="encounter.id">
                        {{ encounter.label }}
                    </option>
                </select>
                <InputError :message="errors.encounter_id" />
            </div>

            <div class="space-y-2">
                <Label for="description">Description</Label>
                <Input
                    id="description"
                    name="description"
                    required
                    placeholder="e.g. Zirconia crown #36"
                    data-test="lab-order-description-input"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="space-y-2">
                <Label for="due_date">Due date (optional)</Label>
                <Input
                    id="due_date"
                    name="due_date"
                    type="date"
                    data-test="lab-order-due-date-input"
                />
                <InputError :message="errors.due_date" />
            </div>

            <div class="space-y-2">
                <Label for="notes">Notes (optional)</Label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                    class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    data-test="lab-order-notes-input"
                />
                <InputError :message="errors.notes" />
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing" data-test="create-lab-order-button">
                    Create lab order
                </Button>
                <Button as-child variant="outline">
                    <Link :href="labIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
