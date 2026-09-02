<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PatientController from '@/actions/App/Http/Controllers/PatientController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as patientsIndex, show as patientsShow } from '@/routes/patients';

type GenderOption = {
    value: string;
    label: string;
};

type MedicalItem = {
    id: number;
    label: string;
    is_critical: boolean;
};

type EmergencyContact = {
    name: string;
    relationship: string | null;
    phone: string;
};

type PatientDetail = {
    id: number;
    patient_number: string;
    full_name: string;
    first_name: string;
    last_name: string;
    date_of_birth: string;
    gender: string;
    phone: string;
    email: string | null;
    occupation: string | null;
    address: string | null;
    allergies: MedicalItem[];
    conditions: MedicalItem[];
    medications: MedicalItem[];
    emergency_contact: EmergencyContact | null;
};

defineProps<{
    patient: PatientDetail;
    genders: GenderOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Patients', href: patientsIndex() },
            { title: 'Edit patient' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${patient.full_name}`" />

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="`Edit ${patient.full_name}`"
            :description="patient.patient_number"
        />

        <Form
            v-bind="PatientController.update.form(patient.id)"
            v-slot="{ errors, processing }"
            class="max-w-2xl space-y-6"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="first_name">First name</Label>
                    <Input
                        id="first_name"
                        name="first_name"
                        :default-value="patient.first_name"
                        required
                    />
                    <InputError :message="errors.first_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="last_name">Last name</Label>
                    <Input
                        id="last_name"
                        name="last_name"
                        :default-value="patient.last_name"
                        required
                    />
                    <InputError :message="errors.last_name" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="date_of_birth">Date of birth</Label>
                    <Input
                        id="date_of_birth"
                        name="date_of_birth"
                        type="date"
                        :default-value="patient.date_of_birth"
                        required
                    />
                    <InputError :message="errors.date_of_birth" />
                </div>
                <div class="grid gap-2">
                    <Label for="gender">Gender</Label>
                    <select
                        id="gender"
                        name="gender"
                        required
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                    >
                        <option
                            v-for="gender in genders"
                            :key="gender.value"
                            :value="gender.value"
                            :selected="gender.value === patient.gender"
                        >
                            {{ gender.label }}
                        </option>
                    </select>
                    <InputError :message="errors.gender" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input id="phone" name="phone" :default-value="patient.phone" required />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        :default-value="patient.email ?? ''"
                    />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="occupation">Occupation</Label>
                    <Input
                        id="occupation"
                        name="occupation"
                        :default-value="patient.occupation ?? ''"
                    />
                    <InputError :message="errors.occupation" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="address">Address</Label>
                <Input
                    id="address"
                    name="address"
                    :default-value="patient.address ?? ''"
                />
                <InputError :message="errors.address" />
            </div>

            <div class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Medical history</h3>
                <div
                    v-for="(allergy, index) in patient.allergies"
                    :key="`allergy-${allergy.id}`"
                    class="grid gap-2"
                >
                    <Label :for="`allergy_label_${allergy.id}`">Allergy</Label>
                    <input
                        type="hidden"
                        :name="`allergies[${index}][id]`"
                        :value="allergy.id"
                    />
                    <Input
                        :id="`allergy_label_${allergy.id}`"
                        :name="`allergies[${index}][label]`"
                        :default-value="allergy.label"
                    />
                </div>
                <div class="grid gap-2">
                    <Label :for="allergy_new_label">
                        {{ patient.allergies.length === 0 ? 'Allergy' : 'Add allergy' }}
                    </Label>
                    <Input
                        id="allergy_new_label"
                        :name="`allergies[${patient.allergies.length}][label]`"
                    />
                </div>
                <div
                    v-for="(condition, index) in patient.conditions"
                    :key="`condition-${condition.id}`"
                    class="grid gap-2"
                >
                    <Label :for="`condition_label_${condition.id}`">Condition</Label>
                    <input
                        type="hidden"
                        :name="`conditions[${index}][id]`"
                        :value="condition.id"
                    />
                    <Input
                        :id="`condition_label_${condition.id}`"
                        :name="`conditions[${index}][label]`"
                        :default-value="condition.label"
                    />
                </div>
                <div class="grid gap-2">
                    <Label :for="condition_new_label">
                        {{ patient.conditions.length === 0 ? 'Condition' : 'Add condition' }}
                    </Label>
                    <Input
                        id="condition_new_label"
                        :name="`conditions[${patient.conditions.length}][label]`"
                    />
                </div>
                <div
                    v-for="(medication, index) in patient.medications"
                    :key="`medication-${medication.id}`"
                    class="grid gap-2"
                >
                    <Label :for="`medication_label_${medication.id}`">Medication</Label>
                    <input
                        type="hidden"
                        :name="`medications[${index}][id]`"
                        :value="medication.id"
                    />
                    <Input
                        :id="`medication_label_${medication.id}`"
                        :name="`medications[${index}][label]`"
                        :default-value="medication.label"
                    />
                </div>
                <div class="grid gap-2">
                    <Label :for="medication_new_label">
                        {{ patient.medications.length === 0 ? 'Medication' : 'Add medication' }}
                    </Label>
                    <Input
                        id="medication_new_label"
                        :name="`medications[${patient.medications.length}][label]`"
                    />
                </div>
            </div>

            <div class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Emergency contact</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="emergency_name">Name</Label>
                        <Input
                            id="emergency_name"
                            name="emergency_contact[name]"
                            :default-value="patient.emergency_contact?.name ?? ''"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="emergency_relationship">Relationship</Label>
                        <Input
                            id="emergency_relationship"
                            name="emergency_contact[relationship]"
                            :default-value="patient.emergency_contact?.relationship ?? ''"
                        />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="emergency_phone">Phone</Label>
                    <Input
                        id="emergency_phone"
                        name="emergency_contact[phone]"
                        :default-value="patient.emergency_contact?.phone ?? ''"
                    />
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing">Save changes</Button>
                <Button as-child variant="outline">
                    <Link :href="patientsShow(patient.id)">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
