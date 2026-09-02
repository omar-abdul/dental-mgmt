<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <div
        :class="className"
        class="flex size-10 items-center justify-center rounded-md bg-[hsl(173_58%_39%)] text-sm font-bold text-white"
        v-bind="$attrs"
    >
        GS
    </div>
</template>
