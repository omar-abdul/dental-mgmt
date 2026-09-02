<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { search as patientsSearch } from '@/routes/patients';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type PatientSearchResult = {
    id: number;
    label: string;
    patient_number: string;
    full_name: string;
    phone: string;
};

const props = withDefaults(
    defineProps<{
        id?: string;
        name?: string;
        label?: string;
        required?: boolean;
        modelValue?: string | number | null;
        selected?: PatientSearchResult | null;
        error?: string;
    }>(),
    {
        id: 'patient_id',
        name: 'patient_id',
        label: 'Patient',
        required: false,
        modelValue: null,
        selected: null,
        error: undefined,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const inputId = computed(() => props.id);
const selectedPatient = ref<PatientSearchResult | null>(props.selected);
const query = ref('');
const results = ref<PatientSearchResult[]>([]);
const isOpen = ref(false);
const highlightedIndex = ref(-1);
const debounceTimer = ref<number | null>(null);

const http = useHttp({
    q: '',
});

const hasSelection = computed(() => selectedPatient.value !== null);

watch(
    () => props.selected,
    (value) => {
        selectedPatient.value = value;
    },
);

watch(
    () => props.modelValue,
    (value) => {
        if (value === null || value === '' || value === undefined) {
            if (selectedPatient.value !== null && String(selectedPatient.value.id) !== String(value)) {
                selectedPatient.value = null;
            }

            return;
        }

        if (selectedPatient.value !== null && String(selectedPatient.value.id) === String(value)) {
            return;
        }

        if (props.selected !== null && String(props.selected.id) === String(value)) {
            selectedPatient.value = props.selected;
        }
    },
    { immediate: true },
);

function clearSelection(): void {
    selectedPatient.value = null;
    query.value = '';
    results.value = [];
    isOpen.value = false;
    highlightedIndex.value = -1;
    emit('update:modelValue', '');
}

function selectPatient(patient: PatientSearchResult): void {
    selectedPatient.value = patient;
    query.value = '';
    results.value = [];
    isOpen.value = false;
    highlightedIndex.value = -1;
    emit('update:modelValue', String(patient.id));
}

function scheduleSearch(value: string): void {
    if (debounceTimer.value !== null) {
        window.clearTimeout(debounceTimer.value);
    }

    debounceTimer.value = window.setTimeout(() => {
        runSearch(value);
    }, 200);
}

function runSearch(value: string): void {
    const trimmed = value.trim();

    if (trimmed === '') {
        results.value = [];
        isOpen.value = false;
        highlightedIndex.value = -1;

        return;
    }

    http.q = trimmed;

    http.get(patientsSearch.url({ query: { q: trimmed } }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (response) => {
            const payload = response as { patients?: PatientSearchResult[] };
            results.value = payload.patients ?? [];
            isOpen.value = true;
            highlightedIndex.value = results.value.length > 0 ? 0 : -1;
        },
    });
}

function onInput(value: string | number): void {
    const nextValue = String(value);
    query.value = nextValue;
    selectedPatient.value = null;
    emit('update:modelValue', '');
    scheduleSearch(nextValue);
}

function onKeydown(event: KeyboardEvent): void {
    if (!isOpen.value || results.value.length === 0) {
        if (event.key === 'Escape') {
            isOpen.value = false;
            highlightedIndex.value = -1;
        }

        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedIndex.value = Math.min(highlightedIndex.value + 1, results.value.length - 1);

        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);

        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();

        const patient = results.value[highlightedIndex.value];

        if (patient !== undefined) {
            selectPatient(patient);
        }

        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        isOpen.value = false;
        highlightedIndex.value = -1;
    }
}

onBeforeUnmount(() => {
    if (debounceTimer.value !== null) {
        window.clearTimeout(debounceTimer.value);
    }
});
</script>

<template>
    <div class="grid gap-2">
        <Label :for="inputId">{{ label }}</Label>

        <input
            type="hidden"
            :id="inputId"
            :name="name"
            :value="selectedPatient?.id ?? ''"
            :required="required"
        />

        <div v-if="hasSelection" class="flex items-center gap-2">
            <div
                class="border-input bg-background flex h-9 min-w-0 flex-1 items-center rounded-md border px-3 py-1 text-sm shadow-xs"
            >
                <span class="truncate">{{ selectedPatient?.label }}</span>
            </div>
            <Button type="button" variant="outline" size="sm" @click="clearSelection">
                Clear
            </Button>
        </div>

        <div v-else class="relative">
            <Input
                :id="`${inputId}_search`"
                :model-value="query"
                type="search"
                autocomplete="off"
                role="combobox"
                :aria-expanded="isOpen"
                aria-autocomplete="list"
                :aria-controls="`${inputId}_results`"
                placeholder="Type to search patients"
                @update:model-value="onInput"
                @keydown="onKeydown"
                @focus="isOpen = results.length > 0"
            />

            <div
                v-if="isOpen"
                :id="`${inputId}_results`"
                role="listbox"
                class="border-input bg-background absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border shadow-md"
            >
                <button
                    v-for="(patient, index) in results"
                    :key="patient.id"
                    type="button"
                    role="option"
                    :aria-selected="index === highlightedIndex"
                    class="hover:bg-accent focus:bg-accent flex w-full flex-col items-start px-3 py-2 text-left text-sm"
                    :class="{ 'bg-accent': index === highlightedIndex }"
                    @mousedown.prevent="selectPatient(patient)"
                >
                    <span class="font-medium">{{ patient.full_name }}</span>
                    <span class="text-muted-foreground text-xs">
                        {{ patient.patient_number }} · {{ patient.phone }}
                    </span>
                </button>

                <p
                    v-if="!http.processing && query.trim() !== '' && results.length === 0"
                    class="text-muted-foreground px-3 py-2 text-sm"
                >
                    No patients found.
                </p>
            </div>
        </div>

        <InputError :message="error" />
    </div>
</template>
