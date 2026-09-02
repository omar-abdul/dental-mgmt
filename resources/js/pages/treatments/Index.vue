<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create as treatmentsCreate, index as treatmentsIndex, show as treatmentsShow } from '@/routes/treatments';

type TreatmentListItem = {
    id: number;
    diagnosis: string;
    status: string;
    status_label: string;
    diagnosed_at: string;
    diagnosed_at_formatted: string;
    patient_name: string;
    patient_number: string;
    dentist_name: string;
};

type PaginatedTreatments = {
    data: TreatmentListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

defineProps<{
    treatments: PaginatedTreatments;
    search: string;
    canCreate: boolean;
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
            {
                title: 'Treatments',
                href: treatmentsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Treatments" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Treatments"
                description="Clinical treatment records and prescriptions"
            />

            <Button v-if="canCreate" as-child>
                <Link :href="treatmentsCreate()">Record treatment</Link>
            </Button>
        </div>

        <form :action="treatmentsIndex().url" method="get" class="flex gap-2">
            <Input
                name="search"
                :default-value="search"
                placeholder="Search by diagnosis, patient name, or number"
                class="max-w-md"
            />
            <Button type="submit" variant="secondary">Search</Button>
        </form>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Patient</th>
                        <th class="px-4 py-3 font-medium">Diagnosis</th>
                        <th class="px-4 py-3 font-medium">Dentist</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="treatments.data.length === 0">
                        <td colspan="5" class="text-muted-foreground px-4 py-8 text-center">
                            No treatments found.
                        </td>
                    </tr>
                    <tr v-for="treatment in treatments.data" :key="treatment.id">
                        <td class="px-4 py-3">{{ treatment.diagnosed_at_formatted }}</td>
                        <td class="px-4 py-3">
                            <div>{{ treatment.patient_name }}</div>
                            <div class="text-muted-foreground text-xs">{{ treatment.patient_number }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <Link
                                :href="treatmentsShow(treatment.id)"
                                class="text-primary hover:underline"
                            >
                                {{ treatment.diagnosis }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">{{ treatment.dentist_name }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="statusVariant(treatment.status)">
                                {{ treatment.status_label }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="treatments.links.length > 3" class="flex flex-wrap gap-2">
            <Button
                v-for="link in treatments.links"
                :key="link.label"
                as-child
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
                :disabled="!link.url"
            >
                <Link v-if="link.url" :href="link.url" v-html="link.label" />
                <span v-else v-html="link.label" />
            </Button>
        </nav>
    </div>
</template>
