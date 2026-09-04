<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PatientChartController from '@/actions/App/Http/Controllers/PatientChartController';
import TreatmentPlanController from '@/actions/App/Http/Controllers/TreatmentPlanController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as chartIndex } from '@/routes/chart';
import { show as encountersShow } from '@/routes/encounters';
import { show as patientsShow } from '@/routes/patients';

const FDI_UPPER = ['18', '17', '16', '15', '14', '13', '12', '11', '21', '22', '23', '24', '25', '26', '27', '28'];
const FDI_LOWER = ['48', '47', '46', '45', '44', '43', '42', '41', '31', '32', '33', '34', '35', '36', '37', '38'];

type ToothRecord = {
    tooth_fdi: string;
    status: string;
    surfaces: string[];
};

type ToothHistoryEntry = {
    id: number;
    tooth_fdi: string;
    previous_status: string | null;
    new_status: string;
    surfaces: string[];
    notes: string | null;
    recorded_at_formatted: string | null;
};

type PlanItem = {
    id: number;
    description: string;
    tooth_fdi: string | null;
    fee_formatted: string;
    acceptance_status: string;
    acceptance_label: string;
};

type TreatmentPlanRecord = {
    id: number;
    title: string | null;
    notes: string | null;
    items: PlanItem[];
};

const props = defineProps<{
    patient: {
        id: number;
        full_name: string;
        patient_number: string;
    };
    teeth: ToothRecord[];
    toothHistory: ToothHistoryEntry[];
    treatmentPlans: TreatmentPlanRecord[];
    recentEncounters: Array<{ id: number; number: string; visited_at_formatted: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    surfaceOptions: string[];
    acceptanceOptions: Array<{ value: string; label: string }>;
    dentists: Array<{ id: number; label: string }>;
    feeItems: Array<{ id: number; label: string; price_cents: number }>;
    canUpdateOdontogram: boolean;
    canCreatePlan: boolean;
}>();

const selectedTooth = ref('36');
const selectedStatus = ref('healthy');
const selectedSurfaces = ref<string[]>([]);
const odontogramNotes = ref('');

const teethMap = computed(() => {
    const map = new Map<string, ToothRecord>();

    for (const tooth of props.teeth) {
        map.set(tooth.tooth_fdi, tooth);
    }

    return map;
});

function toothStatus(toothFdi: string): string {
    return teethMap.value.get(toothFdi)?.status ?? 'healthy';
}

function toothClass(toothFdi: string): string {
    const status = toothStatus(toothFdi);

    return `odontogram-tooth odontogram-tooth--${status}`;
}

function selectTooth(toothFdi: string): void {
    selectedTooth.value = toothFdi;
    const record = teethMap.value.get(toothFdi);
    selectedStatus.value = record?.status ?? 'healthy';
    selectedSurfaces.value = record?.surfaces ? [...record.surfaces] : [];
    odontogramNotes.value = '';
}

function toggleSurface(surface: string): void {
    if (selectedSurfaces.value.includes(surface)) {
        selectedSurfaces.value = selectedSurfaces.value.filter((value) => value !== surface);
    } else {
        selectedSurfaces.value = [...selectedSurfaces.value, surface];
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Chart', href: chartIndex() },
            { title: 'Patient chart' },
        ],
    },
});
</script>

<template>
    <Head :title="`Chart — ${patient.full_name}`" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                variant="small"
                :title="patient.full_name"
                :description="`${patient.patient_number} · Dental chart`"
            />
            <Button as-child variant="outline">
                <Link :href="patientsShow(patient.id)">Patient profile</Link>
            </Button>
        </div>

        <section class="space-y-4 rounded-md border p-4">
            <h3 class="text-sm font-medium">FDI odontogram</h3>

            <div class="space-y-3">
                <div class="odontogram-arch">
                    <button
                        v-for="tooth in FDI_UPPER"
                        :key="tooth"
                        type="button"
                        :class="[toothClass(tooth), { 'ring-2 ring-primary': selectedTooth === tooth }]"
                        :data-test="`odontogram-tooth-${tooth}`"
                        @click="selectTooth(tooth)"
                    >
                        {{ tooth }}
                    </button>
                </div>
                <div class="odontogram-arch">
                    <button
                        v-for="tooth in FDI_LOWER"
                        :key="tooth"
                        type="button"
                        :class="[toothClass(tooth), { 'ring-2 ring-primary': selectedTooth === tooth }]"
                        :data-test="`odontogram-tooth-${tooth}`"
                        @click="selectTooth(tooth)"
                    >
                        {{ tooth }}
                    </button>
                </div>
            </div>

            <Form
                v-if="canUpdateOdontogram"
                v-bind="PatientChartController.updateOdontogram.form(patient.id)"
                v-slot="{ errors, processing }"
                class="space-y-4 rounded-md border bg-muted/20 p-4"
                data-test="odontogram-form"
            >
                <input type="hidden" name="tooth_fdi" :value="selectedTooth" />
                <input type="hidden" name="status" :value="selectedStatus" />
                <input
                    v-for="surface in selectedSurfaces"
                    :key="surface"
                    type="hidden"
                    name="surfaces[]"
                    :value="surface"
                />

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="selected_tooth">Selected tooth</Label>
                        <Input id="selected_tooth" :model-value="selectedTooth" readonly data-test="selected-tooth" />
                    </div>
                    <div class="space-y-2">
                        <Label for="tooth_status">Status</Label>
                        <select
                            id="tooth_status"
                            v-model="selectedStatus"
                            class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm"
                            data-test="tooth-status-select"
                        >
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError :message="errors.status" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label>Surfaces</Label>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="surface in surfaceOptions"
                            :key="surface"
                            type="button"
                            size="sm"
                            :variant="selectedSurfaces.includes(surface) ? 'default' : 'outline'"
                            :data-test="`surface-${surface}`"
                            @click="toggleSurface(surface)"
                        >
                            {{ surface }}
                        </Button>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="odontogram_notes">Notes</Label>
                    <Input id="odontogram_notes" v-model="odontogramNotes" name="notes" data-test="odontogram-notes" />
                    <InputError :message="errors.notes" />
                </div>

                <Button type="submit" :disabled="processing" data-test="save-odontogram-button">
                    Save tooth
                </Button>
            </Form>

            <p
                v-else
                class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                data-test="odontogram-readonly-notice"
            >
                You have read-only access to the odontogram.
            </p>
        </section>

        <section class="space-y-3 rounded-md border p-4">
            <h3 class="text-sm font-medium">Tooth history</h3>
            <ul v-if="toothHistory.length > 0" class="space-y-2 text-sm">
                <li v-for="entry in toothHistory" :key="entry.id" class="rounded-md border p-3">
                    <div class="font-medium">
                        Tooth {{ entry.tooth_fdi }} · {{ entry.new_status }}
                    </div>
                    <div class="text-muted-foreground text-xs">
                        {{ entry.recorded_at_formatted }}
                        <span v-if="entry.surfaces.length > 0"> · Surfaces: {{ entry.surfaces.join(', ') }}</span>
                    </div>
                    <p v-if="entry.notes" class="mt-1">{{ entry.notes }}</p>
                </li>
            </ul>
            <p v-else class="text-muted-foreground text-sm">No tooth history yet.</p>
        </section>

        <section class="space-y-4 rounded-md border p-4">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-sm font-medium">Treatment plans</h3>
            </div>

            <div v-for="plan in treatmentPlans" :key="plan.id" class="space-y-3 rounded-md border p-3">
                <div>
                    <div class="font-medium">{{ plan.title ?? 'Treatment plan' }}</div>
                    <p v-if="plan.notes" class="text-muted-foreground text-sm">{{ plan.notes }}</p>
                </div>
                <ul v-if="plan.items.length > 0" class="space-y-2 text-sm">
                    <li
                        v-for="item in plan.items"
                        :key="item.id"
                        class="flex items-start justify-between gap-4 rounded-md border p-2"
                    >
                        <div>
                            <div>{{ item.description }}</div>
                            <div v-if="item.tooth_fdi" class="text-muted-foreground text-xs">
                                Tooth {{ item.tooth_fdi }}
                            </div>
                        </div>
                        <div class="text-right space-y-2">
                            <div>{{ item.fee_formatted }}</div>
                            <Badge variant="outline" :data-test="`plan-item-status-${item.id}`">
                                {{ item.acceptance_label }}
                            </Badge>
                            <Form
                                v-if="canCreatePlan"
                                v-bind="TreatmentPlanController.updateItem.form(plan.id, item.id)"
                                v-slot="{ errors, processing }"
                                class="flex flex-col items-end gap-2"
                                :data-test="`plan-item-acceptance-form-${item.id}`"
                            >
                                <select
                                    name="acceptance_status"
                                    class="border-input bg-background h-8 rounded-md border px-2 text-xs"
                                    :data-test="`plan-item-acceptance-select-${item.id}`"
                                    :default-value="item.acceptance_status"
                                >
                                    <option
                                        v-for="option in acceptanceOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError :message="errors.acceptance_status" />
                                <Button
                                    type="submit"
                                    size="sm"
                                    variant="outline"
                                    :disabled="processing"
                                    :data-test="`update-plan-item-button-${item.id}`"
                                >
                                    Update status
                                </Button>
                            </Form>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground text-sm">No plan items yet.</p>

                <Form
                    v-if="canCreatePlan"
                    v-bind="TreatmentPlanController.storeItem.form(plan.id)"
                    v-slot="{ errors, processing }"
                    class="grid gap-3 rounded-md border bg-muted/20 p-3 sm:grid-cols-2"
                    :data-test="`plan-item-form-${plan.id}`"
                >
                    <div class="space-y-2 sm:col-span-2">
                        <Label :for="`description_${plan.id}`">Description</Label>
                        <Input :id="`description_${plan.id}`" name="description" required />
                        <InputError :message="errors.description" />
                    </div>
                    <div class="space-y-2">
                        <Label :for="`tooth_fdi_${plan.id}`">Tooth FDI</Label>
                        <Input :id="`tooth_fdi_${plan.id}`" name="tooth_fdi" />
                    </div>
                    <div class="space-y-2">
                        <Label :for="`fee_cents_${plan.id}`">Fee (cents)</Label>
                        <Input :id="`fee_cents_${plan.id}`" name="fee_cents" type="number" min="0" value="0" />
                    </div>
                    <div class="space-y-2">
                        <Label :for="`acceptance_status_${plan.id}`">Acceptance</Label>
                        <select
                            :id="`acceptance_status_${plan.id}`"
                            name="acceptance_status"
                            class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm"
                            data-test="plan-item-acceptance-select"
                        >
                            <option
                                v-for="option in acceptanceOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError :message="errors.acceptance_status" />
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <Button type="submit" size="sm" :disabled="processing" data-test="add-plan-item-button">
                            Add item
                        </Button>
                    </div>
                </Form>
            </div>

            <Form
                v-if="canCreatePlan"
                v-bind="PatientChartController.storePlan.form(patient.id)"
                v-slot="{ errors, processing }"
                class="grid gap-3 rounded-md border p-3 sm:grid-cols-2"
                data-test="create-plan-form"
            >
                <div class="space-y-2 sm:col-span-2">
                    <Label for="plan_title">Plan title</Label>
                    <Input id="plan_title" name="title" />
                </div>
                <div class="space-y-2">
                    <Label for="plan_dentist">Dentist</Label>
                    <select
                        id="plan_dentist"
                        name="dentist_id"
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm"
                        required
                    >
                        <option v-for="dentist in dentists" :key="dentist.id" :value="dentist.id">
                            {{ dentist.label }}
                        </option>
                    </select>
                    <InputError :message="errors.dentist_id" />
                </div>
                <div class="space-y-2 sm:col-span-2">
                    <Label for="plan_notes">Notes</Label>
                    <Input id="plan_notes" name="notes" />
                </div>
                <div class="sm:col-span-2">
                    <Button type="submit" :disabled="processing" data-test="create-plan-button">
                        Create treatment plan
                    </Button>
                </div>
            </Form>
        </section>

        <section v-if="recentEncounters.length > 0" class="space-y-3 rounded-md border p-4">
            <h3 class="text-sm font-medium">Recent encounters</h3>
            <ul class="space-y-2 text-sm">
                <li v-for="encounter in recentEncounters" :key="encounter.id">
                    <Link
                        :href="encountersShow(encounter.id)"
                        class="text-primary hover:underline"
                    >
                        {{ encounter.number }} · {{ encounter.visited_at_formatted }}
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>

<style scoped>
.odontogram-arch {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    justify-content: center;
}

.odontogram-tooth {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.375rem;
    border: 1px solid hsl(var(--border));
    font-size: 0.65rem;
    font-weight: 600;
    background: hsl(var(--background));
}

.odontogram-tooth--healthy {
    background: #ecfdf5;
}

.odontogram-tooth--caries {
    background: #fef3c7;
}

.odontogram-tooth--filled,
.odontogram-tooth--root_canal {
    background: #dbeafe;
}

.odontogram-tooth--missing,
.odontogram-tooth--extracted {
    background: #f3f4f6;
    color: #9ca3af;
}

.odontogram-tooth--crown,
.odontogram-tooth--bridge,
.odontogram-tooth--implant {
    background: #ede9fe;
}

.odontogram-tooth--fractured,
.odontogram-tooth--impacted,
.odontogram-tooth--other {
    background: #fee2e2;
}
</style>
