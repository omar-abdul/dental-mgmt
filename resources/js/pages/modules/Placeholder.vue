<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

defineProps<{
    title: string;
    module: string;
}>();
</script>

<template>
    <Head :title="title" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            :title="title"
            description="This module is coming soon in a future release."
        />
    </div>
</template>
