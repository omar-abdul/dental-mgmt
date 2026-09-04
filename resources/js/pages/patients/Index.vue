<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create as patientsCreate, index as patientsIndex, show as patientsShow } from '@/routes/patients';

type PatientListItem = {
    id: number;
    patient_number: string;
    full_name: string;
    phone: string;
    email: string | null;
    status: string;
    is_archived: boolean;
};

type PaginatedPatients = {
    data: PatientListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

defineProps<{
    patients: PaginatedPatients;
    search: string;
    canCreate: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Patients',
                href: patientsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Patients" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Patients"
                description="Search and manage clinic patients"
            />

            <Button v-if="canCreate" as-child>
                <Link :href="patientsCreate()" data-test="register-patient-link">Register patient</Link>
            </Button>
        </div>

        <form :action="patientsIndex().url" method="get" class="flex gap-2">
            <Input
                name="search"
                :default-value="search"
                placeholder="Search by name, number, phone, or email"
                class="max-w-md"
                data-test="patient-search-input"
            />
            <Button type="submit" variant="secondary" data-test="patient-search-button">Search</Button>
        </form>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Number</th>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Phone</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="patients.data.length === 0">
                        <td colspan="4" class="text-muted-foreground px-4 py-8 text-center">
                            No patients found.
                        </td>
                    </tr>
                    <tr v-for="patient in patients.data" :key="patient.id">
                        <td class="px-4 py-3">
                            <Link
                                :href="patientsShow(patient.id)"
                                class="text-primary font-medium hover:underline"
                            >
                                {{ patient.patient_number }}
                            </Link>
                            <span
                                v-if="patient.is_archived"
                                class="bg-muted text-muted-foreground ml-2 inline-flex rounded px-1.5 py-0.5 text-xs font-medium"
                            >
                                Archived
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ patient.full_name }}</td>
                        <td class="px-4 py-3">{{ patient.phone }}</td>
                        <td class="px-4 py-3">{{ patient.email ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="patients.links.length > 3" class="flex flex-wrap gap-2">
            <template v-for="(link, index) in patients.links" :key="index">
                <Button
                    v-if="link.url"
                    as-child
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                >
                    <Link :href="link.url" v-html="link.label" />
                </Button>
                <span
                    v-else
                    class="text-muted-foreground inline-flex items-center px-2 text-sm"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
