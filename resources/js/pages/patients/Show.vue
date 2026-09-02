<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PatientController from '@/actions/App/Http/Controllers/PatientController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit as patientsEdit, index as patientsIndex, show as patientsShow } from '@/routes/patients';
import { show as treatmentsShow } from '@/routes/treatments';

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
    status: string;
    is_archived: boolean;
    allergies: MedicalItem[];
    conditions: MedicalItem[];
    medications: MedicalItem[];
    emergency_contact: EmergencyContact | null;
    treatments: Array<{
        id: number;
        diagnosis: string;
        status: string;
        status_label: string;
        diagnosed_at: string;
        diagnosed_at_formatted: string;
    }>;
};

defineProps<{
    patient: PatientDetail;
    canUpdate: boolean;
    canArchive: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Patients', href: patientsIndex() }],
    },
});
</script>

<template>
    <Head :title="patient.full_name" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                variant="small"
                :title="patient.full_name"
                :description="patient.patient_number"
            />

            <div class="flex gap-2">
                <Button v-if="canUpdate" as-child variant="outline">
                    <Link :href="patientsEdit(patient.id)">Edit</Link>
                </Button>
                <Form
                    v-if="canArchive"
                    v-bind="PatientController.archive.form(patient.id)"
                    v-slot="{ processing }"
                >
                    <Button type="submit" variant="destructive" :disabled="processing">
                        Archive
                    </Button>
                </Form>
            </div>
        </div>

        <p
            v-if="patient.is_archived"
            class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
        >
            This patient is archived and read-only.
        </p>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Identity</h3>
                <dl class="grid gap-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Date of birth</dt>
                        <dd>{{ patient.date_of_birth }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Gender</dt>
                        <dd class="capitalize">{{ patient.gender.replace('_', ' ') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Phone</dt>
                        <dd>{{ patient.phone }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Email</dt>
                        <dd>{{ patient.email ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Occupation</dt>
                        <dd>{{ patient.occupation ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Address</dt>
                        <dd>{{ patient.address ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Emergency contact</h3>
                <p v-if="!patient.emergency_contact" class="text-muted-foreground text-sm">
                    No emergency contact on file.
                </p>
                <dl v-else class="grid gap-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Name</dt>
                        <dd>{{ patient.emergency_contact.name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Relationship</dt>
                        <dd>{{ patient.emergency_contact.relationship ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Phone</dt>
                        <dd>{{ patient.emergency_contact.phone }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Allergies</h3>
                <ul v-if="patient.allergies.length" class="space-y-1 text-sm">
                    <li v-for="item in patient.allergies" :key="item.id">
                        {{ item.label }}
                        <span v-if="item.is_critical" class="text-destructive"> (critical)</span>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">None recorded.</p>
            </section>

            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Conditions</h3>
                <ul v-if="patient.conditions.length" class="space-y-1 text-sm">
                    <li v-for="item in patient.conditions" :key="item.id">
                        {{ item.label }}
                        <span v-if="item.is_critical" class="text-destructive"> (critical)</span>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">None recorded.</p>
            </section>

            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Medications</h3>
                <ul v-if="patient.medications.length" class="space-y-1 text-sm">
                    <li v-for="item in patient.medications" :key="item.id">
                        {{ item.label }}
                        <span v-if="item.is_critical" class="text-destructive"> (critical)</span>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">None recorded.</p>
            </section>
        </div>

        <section class="space-y-3 rounded-md border p-4">
            <h3 class="text-sm font-medium">Treatment history</h3>
            <p v-if="patient.treatments.length === 0" class="text-muted-foreground text-sm">
                No treatments recorded yet.
            </p>
            <ul v-else class="space-y-2 text-sm">
                <li
                    v-for="treatment in patient.treatments"
                    :key="treatment.id"
                    class="flex flex-col gap-1 rounded-md border p-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <Link
                            :href="treatmentsShow(treatment.id)"
                            class="text-primary font-medium hover:underline"
                        >
                            {{ treatment.diagnosis }}
                        </Link>
                        <div class="text-muted-foreground text-xs">
                            {{ treatment.diagnosed_at_formatted }} · {{ treatment.status_label }}
                        </div>
                    </div>
                </li>
            </ul>
        </section>

        <Button as-child variant="outline">
            <Link :href="patientsIndex()">Back to patients</Link>
        </Button>
    </div>
</template>
