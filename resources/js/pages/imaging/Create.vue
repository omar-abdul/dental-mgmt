<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ImagingOrderController from '@/actions/App/Http/Controllers/ImagingOrderController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PatientPicker, { type PatientSearchResult } from '@/components/PatientPicker.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { create as imagingCreate, index as imagingIndex } from '@/routes/imaging';

type Option = {
    id: number;
    label: string;
};

type ValueOption = {
    value: string;
    label: string;
};

const props = defineProps<{
    types: ValueOption[];
    statuses: ValueOption[];
    dentists: Option[];
    encounters: Option[];
    defaultDentistId: number | null;
    selectedPatientId: number | null;
    selectedPatient: PatientSearchResult | null;
}>();

const selectedPatient = ref(props.selectedPatientId?.toString() ?? '');

watch(selectedPatient, (patientId) => {
    router.get(
        imagingCreate.url({ query: patientId ? { patient_id: patientId } : {} }),
        {},
        { preserveState: true, preserveScroll: true },
    );
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Imaging', href: imagingIndex() },
            { title: 'New order', href: imagingCreate() },
        ],
    },
});
</script>

<template>
    <Head title="New imaging order" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="New imaging order"
            description="Request a radiograph or imaging study for a patient"
        />

        <Form
            v-bind="ImagingOrderController.store.form()"
            v-slot="{ errors, processing }"
            class="max-w-2xl space-y-6"
        >
            <div class="space-y-2">
                <PatientPicker
                    id="patient_id"
                    v-model="selectedPatient"
                    name="patient_id"
                    :selected="props.selectedPatient"
                    required
                    data-test="imaging-order-patient-picker"
                />
                <InputError :message="errors.patient_id" />
            </div>

            <div class="space-y-2">
                <Label for="dentist_id">Dentist</Label>
                <select
                    id="dentist_id"
                    name="dentist_id"
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    required
                    data-test="imaging-order-dentist-select"
                >
                    <option value="">Select dentist</option>
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

            <div v-if="encounters.length > 0" class="space-y-2">
                <Label for="encounter_id">Encounter (optional)</Label>
                <select
                    id="encounter_id"
                    name="encounter_id"
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    data-test="imaging-order-encounter-select"
                >
                    <option value="">None</option>
                    <option v-for="encounter in encounters" :key="encounter.id" :value="encounter.id">
                        {{ encounter.label }}
                    </option>
                </select>
                <InputError :message="errors.encounter_id" />
            </div>

            <div class="space-y-2">
                <Label for="type">Imaging type</Label>
                <select
                    id="type"
                    name="type"
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    required
                    data-test="imaging-order-type-select"
                >
                    <option value="">Select type</option>
                    <option v-for="typeOption in types" :key="typeOption.value" :value="typeOption.value">
                        {{ typeOption.label }}
                    </option>
                </select>
                <InputError :message="errors.type" />
            </div>

            <div class="space-y-2">
                <Label for="status">Status</Label>
                <select
                    id="status"
                    name="status"
                    class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    data-test="imaging-order-status-select"
                >
                    <option v-for="statusOption in statuses" :key="statusOption.value" :value="statusOption.value">
                        {{ statusOption.label }}
                    </option>
                </select>
                <InputError :message="errors.status" />
            </div>

            <div class="space-y-2">
                <Label for="notes">Notes (optional)</Label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                    class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    data-test="imaging-order-notes-input"
                />
                <InputError :message="errors.notes" />
            </div>

            <div class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Result metadata (optional)</h3>
                <div class="space-y-2">
                    <Label for="result_findings">Findings</Label>
                    <textarea
                        id="result_findings"
                        name="result_findings"
                        rows="2"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[60px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        data-test="imaging-order-result-findings-input"
                    />
                    <InputError :message="errors.result_findings" />
                </div>
                <div class="space-y-2">
                    <Label for="result_impression">Impression</Label>
                    <textarea
                        id="result_impression"
                        name="result_impression"
                        rows="2"
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[60px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        data-test="imaging-order-result-impression-input"
                    />
                    <InputError :message="errors.result_impression" />
                </div>
            </div>

            <div class="space-y-2">
                <Label for="file">Attachment (optional)</Label>
                <input
                    id="file"
                    name="file"
                    type="file"
                    accept=".jpg,.jpeg,.png,.pdf"
                    class="border-input bg-background file:text-foreground flex h-10 w-full rounded-md border px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium"
                    data-test="imaging-order-file-input"
                />
                <InputError :message="errors.file" />
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing" data-test="create-imaging-order-button">
                    Create imaging order
                </Button>
                <Button as-child variant="outline">
                    <Link :href="imagingIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
