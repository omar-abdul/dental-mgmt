<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import PatientController from '@/actions/App/Http/Controllers/PatientController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as patientsIndex } from '@/routes/patients';

type GenderOption = {
    value: string;
    label: string;
};

defineProps<{
    genders: GenderOption[];
}>();

const confirmDuplicate = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Patients', href: patientsIndex() },
            { title: 'Register', href: PatientController.create.url() },
        ],
    },
});
</script>

<template>
    <Head title="Register patient" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Register patient"
            description="Create a new patient record"
        />

        <Form
            v-bind="PatientController.store.form()"
            v-slot="{ errors, processing }"
            class="max-w-2xl space-y-6"
            @error="() => (confirmDuplicate = false)"
        >
            <input
                v-if="confirmDuplicate"
                type="hidden"
                name="confirm_duplicate"
                value="1"
            />

            <InputError :message="errors.duplicate" />

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="first_name">First name</Label>
                    <Input id="first_name" name="first_name" required />
                    <InputError :message="errors.first_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="last_name">Last name</Label>
                    <Input id="last_name" name="last_name" required />
                    <InputError :message="errors.last_name" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="date_of_birth">Date of birth</Label>
                    <Input id="date_of_birth" name="date_of_birth" type="date" required />
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
                        <option value="" disabled selected>Select gender</option>
                        <option v-for="gender in genders" :key="gender.value" :value="gender.value">
                            {{ gender.label }}
                        </option>
                    </select>
                    <InputError :message="errors.gender" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input id="phone" name="phone" required />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input id="email" name="email" type="email" />
                    <InputError :message="errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="occupation">Occupation</Label>
                    <Input id="occupation" name="occupation" />
                    <InputError :message="errors.occupation" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="address">Address</Label>
                <Input id="address" name="address" />
                <InputError :message="errors.address" />
            </div>

            <div class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Medical history</h3>
                <div class="grid gap-2">
                    <Label for="allergy_label">Allergy</Label>
                    <Input id="allergy_label" name="allergies[0][label]" />
                </div>
                <div class="grid gap-2">
                    <Label for="condition_label">Condition</Label>
                    <Input id="condition_label" name="conditions[0][label]" />
                </div>
                <div class="grid gap-2">
                    <Label for="medication_label">Medication</Label>
                    <Input id="medication_label" name="medications[0][label]" />
                </div>
            </div>

            <div class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Emergency contact</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="emergency_name">Name</Label>
                        <Input id="emergency_name" name="emergency_contact[name]" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="emergency_relationship">Relationship</Label>
                        <Input
                            id="emergency_relationship"
                            name="emergency_contact[relationship]"
                        />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="emergency_phone">Phone</Label>
                    <Input id="emergency_phone" name="emergency_contact[phone]" />
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing">
                    Save patient
                </Button>
                <Button
                    v-if="errors.duplicate"
                    type="submit"
                    variant="secondary"
                    :disabled="processing"
                    @click="confirmDuplicate = true"
                >
                    Create anyway
                </Button>
                <Button as-child variant="outline">
                    <Link :href="patientsIndex()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
