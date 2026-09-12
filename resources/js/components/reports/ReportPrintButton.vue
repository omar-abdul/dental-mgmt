<script setup lang="ts">
import { Button } from '@/components/ui/button';

function printReport(): void {
    window.print();
}
</script>

<template>
    <Button
        type="button"
        class="print:hidden"
        data-test="print-report-button"
        @click="printReport"
    >
        Print report
    </Button>
</template>
