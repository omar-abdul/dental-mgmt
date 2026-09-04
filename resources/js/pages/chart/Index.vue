<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index as chartIndex } from '@/routes/chart';
import { show as encountersShow } from '@/routes/encounters';

type EncounterRow = {
    id: number;
    number: string;
    visited_at_formatted: string;
    patient_name: string;
    patient_number: string;
    dentist_name: string;
    is_signed: boolean;
};

defineProps<{
    encounters: {
        data: EncounterRow[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Chart', href: chartIndex() }],
    },
});
</script>

<template>
    <Head title="Chart" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Clinical chart"
            description="Encounters and odontogram records"
        />

        <div v-if="encounters.data.length > 0" class="overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Encounter</th>
                        <th class="px-4 py-3 font-medium">Patient</th>
                        <th class="px-4 py-3 font-medium">Dentist</th>
                        <th class="px-4 py-3 font-medium">Visited</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="encounter in encounters.data"
                        :key="encounter.id"
                        class="border-t"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="encountersShow(encounter.id)"
                                class="font-medium text-primary hover:underline"
                                data-test="encounter-link"
                            >
                                {{ encounter.number }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ encounter.patient_name }}</div>
                            <div class="text-muted-foreground text-xs">{{ encounter.patient_number }}</div>
                        </td>
                        <td class="px-4 py-3">{{ encounter.dentist_name }}</td>
                        <td class="px-4 py-3">{{ encounter.visited_at_formatted }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="encounter.is_signed ? 'secondary' : 'outline'">
                                {{ encounter.is_signed ? 'Signed' : 'Draft' }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-else class="text-muted-foreground text-sm">No encounters recorded yet.</p>
    </div>
</template>
