<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col justify-between p-10 text-white lg:flex"
            style="background-color: hsl(222 47% 11%)"
        >
            <div class="relative z-20 flex items-center gap-3 text-lg font-medium">
                <AppLogoIcon class="size-10" />
                <span>{{ name }}</span>
            </div>
            <div class="relative z-20">
                <p class="text-lg text-white/80">
                    Staff portal for clinic operations
                </p>
            </div>
        </div>
        <div class="flex h-full flex-col bg-white lg:p-8">
            <div
                class="mx-auto flex w-full flex-1 flex-col justify-center space-y-6 sm:w-[400px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1
                        v-if="title"
                        class="text-2xl font-semibold tracking-tight text-[hsl(222_47%_11%)]"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-muted-foreground text-sm"
                    >
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
