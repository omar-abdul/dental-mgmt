<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type ComboboxOption = {
    id: number;
    label: string;
};

const props = withDefaults(
    defineProps<{
        id?: string;
        name?: string;
        label?: string;
        required?: boolean;
        options: ComboboxOption[];
        modelValue?: string | number | null;
        defaultOptionId?: number | null;
        placeholder?: string;
        searchDataTest?: string;
        optionDataTest?: string;
        error?: string;
        clearable?: boolean;
    }>(),
    {
        id: 'option_id',
        name: 'option_id',
        label: 'Option',
        required: false,
        modelValue: null,
        defaultOptionId: null,
        placeholder: 'Type to search',
        searchDataTest: 'local-combobox-search',
        optionDataTest: 'local-combobox-option',
        error: undefined,
        clearable: true,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const inputId = computed(() => props.id);

function findOption(id: number | null | undefined): ComboboxOption | null {
    if (id === null || id === undefined) {
        return null;
    }

    return props.options.find((option) => option.id === id) ?? null;
}

const selectedOption = ref<ComboboxOption | null>(
    findOption(props.defaultOptionId) ?? findOption(props.modelValue ? Number(props.modelValue) : null),
);
const query = ref('');
const isOpen = ref(false);
const highlightedIndex = ref(-1);

const filteredOptions = computed(() => {
    const trimmed = query.value.trim().toLowerCase();

    if (trimmed === '') {
        return props.options;
    }

    return props.options.filter((option) => option.label.toLowerCase().includes(trimmed));
});

const hasSelection = computed(() => selectedOption.value !== null);

watch(
    () => props.modelValue,
    (value) => {
        if (value === null || value === '' || value === undefined) {
            if (selectedOption.value !== null && String(selectedOption.value.id) !== String(value)) {
                selectedOption.value = null;
            }

            return;
        }

        if (selectedOption.value !== null && String(selectedOption.value.id) === String(value)) {
            return;
        }

        selectedOption.value = findOption(Number(value));
    },
    { immediate: true },
);

watch(
    () => props.defaultOptionId,
    (value) => {
        if (props.modelValue !== null && props.modelValue !== '' && props.modelValue !== undefined) {
            return;
        }

        selectedOption.value = findOption(value);

        if (selectedOption.value !== null) {
            emit('update:modelValue', String(selectedOption.value.id));
        }
    },
    { immediate: true },
);

watch(
    () => props.options,
    () => {
        if (selectedOption.value !== null) {
            const refreshed = findOption(selectedOption.value.id);

            if (refreshed !== null) {
                selectedOption.value = refreshed;
            }
        }
    },
);

function clearSelection(): void {
    selectedOption.value = null;
    query.value = '';
    isOpen.value = false;
    highlightedIndex.value = -1;
    emit('update:modelValue', '');
}

function selectOption(option: ComboboxOption): void {
    selectedOption.value = option;
    query.value = '';
    isOpen.value = false;
    highlightedIndex.value = -1;
    emit('update:modelValue', String(option.id));
}

function onInput(value: string | number): void {
    const nextValue = String(value);
    query.value = nextValue;
    selectedOption.value = null;
    emit('update:modelValue', '');
    isOpen.value = true;
    highlightedIndex.value = filteredOptions.value.length > 0 ? 0 : -1;
}

function onKeydown(event: KeyboardEvent): void {
    if (!isOpen.value || filteredOptions.value.length === 0) {
        if (event.key === 'Escape') {
            isOpen.value = false;
            highlightedIndex.value = -1;
        }

        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1);

        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);

        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();

        const option = filteredOptions.value[highlightedIndex.value];

        if (option !== undefined) {
            selectOption(option);
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
    query.value = '';
});
</script>

<template>
    <div class="grid gap-2">
        <Label :for="inputId">{{ label }}</Label>

        <input
            type="hidden"
            :id="inputId"
            :name="name"
            :value="selectedOption?.id ?? ''"
            :required="required"
        />

        <div v-if="hasSelection" class="flex items-center gap-2">
            <div
                class="border-input bg-background flex h-9 min-w-0 flex-1 items-center rounded-md border px-3 py-1 text-sm shadow-xs"
            >
                <span class="truncate">{{ selectedOption?.label }}</span>
            </div>
            <Button
                v-if="clearable"
                type="button"
                variant="outline"
                size="sm"
                @click="clearSelection"
            >
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
                :placeholder="placeholder"
                :data-test="searchDataTest"
                @update:model-value="onInput"
                @keydown="onKeydown"
                @focus="isOpen = filteredOptions.length > 0"
            />

            <div
                v-if="isOpen"
                :id="`${inputId}_results`"
                role="listbox"
                class="border-input bg-background absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border shadow-md"
            >
                <button
                    v-for="(option, index) in filteredOptions"
                    :key="option.id"
                    type="button"
                    role="option"
                    :aria-selected="index === highlightedIndex"
                    class="hover:bg-accent focus:bg-accent flex w-full px-3 py-2 text-left text-sm"
                    :class="{ 'bg-accent': index === highlightedIndex }"
                    :data-test="optionDataTest"
                    @mousedown.prevent="selectOption(option)"
                >
                    {{ option.label }}
                </button>

                <p
                    v-if="query.trim() !== '' && filteredOptions.length === 0"
                    class="text-muted-foreground px-3 py-2 text-sm"
                >
                    No options found.
                </p>
            </div>
        </div>

        <InputError :message="error" />
    </div>
</template>
