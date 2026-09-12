<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar class="print:hidden" />
        <AppContent
            variant="sidebar"
            class="min-w-0 overflow-x-clip print:overflow-visible"
        >
            <AppSidebarHeader class="print:hidden" :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster class="print:hidden" />
    </AppShell>
</template>
