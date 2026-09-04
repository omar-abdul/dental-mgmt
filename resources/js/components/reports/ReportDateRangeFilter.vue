<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    action: string;
    from: string;
    to: string;
}>();
</script>

<template>
    <form
        :action="action"
        method="get"
        class="flex flex-wrap items-end gap-4 rounded-md border p-4"
        data-test="report-date-filter"
    >
        <div class="space-y-2">
            <Label for="report-from">From</Label>
            <Input
                id="report-from"
                name="from"
                type="date"
                :default-value="from"
                data-test="report-from-input"
                required
            />
        </div>
        <div class="space-y-2">
            <Label for="report-to">To</Label>
            <Input
                id="report-to"
                name="to"
                type="date"
                :default-value="to"
                data-test="report-to-input"
                required
            />
        </div>
        <Button type="submit" data-test="report-filter-submit">
            Apply range
        </Button>
    </form>
</template>
