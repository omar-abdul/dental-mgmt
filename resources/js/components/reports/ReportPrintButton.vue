<script setup lang="ts">
import { Button } from '@/components/ui/button';
</script>

<template>
    <Button data-test="print-report-button" @click="() => window.print()">
        Print report
    </Button>
</template>
