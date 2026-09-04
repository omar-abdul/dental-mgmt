<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as imagingIndex } from '@/routes/imaging';
import { show as encountersShow } from '@/routes/encounters';

type ImageFileDetail = {
    id: number;
    original_name: string;
    mime_type: string | null;
    size_bytes: number | null;
    size_formatted: string | null;
    exists_on_disk: boolean;
};

type ImagingOrderDetail = {
    id: number;
    number: string;
    type: string;
    type_label: string;
    notes: string | null;
    status: string;
    status_label: string;
    created_at_formatted: string | null;
    patient: {
        id: number;
        full_name: string;
        patient_number: string;
    };
    dentist_name: string;
    encounter: {
        id: number;
        number: string;
    } | null;
    result: {
        findings: string | null;
        impression: string | null;
        reported_at_formatted: string | null;
    } | null;
    files: ImageFileDetail[];
};

defineProps<{
    order: ImagingOrderDetail;
}>();

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'completed') {
        return 'default';
    }

    if (status === 'cancelled') {
        return 'destructive';
    }

    if (status === 'scheduled') {
        return 'secondary';
    }

    return 'outline';
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Imaging', href: imagingIndex() },
        ],
    },
});
</script>

<template>
    <Head :title="`Imaging order — ${order.number}`" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                variant="small"
                :title="order.number"
                :description="`${order.patient.full_name} (${order.patient.patient_number})`"
            />

            <Badge :variant="statusVariant(order.status)" data-test="imaging-order-status-badge">
                {{ order.status_label }}
            </Badge>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="space-y-3 rounded-md border p-4">
                <h3 class="text-sm font-medium">Order details</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Type</dt>
                        <dd data-test="imaging-order-type">{{ order.type_label }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Dentist</dt>
                        <dd>{{ order.dentist_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Created</dt>
                        <dd>{{ order.created_at_formatted }}</dd>
                    </div>
                    <div v-if="order.encounter" class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Encounter</dt>
                        <dd>
                            <Link
                                :href="encountersShow(order.encounter.id)"
                                class="text-primary hover:underline"
                            >
                                {{ order.encounter.number }}
                            </Link>
                        </dd>
                    </div>
                    <div v-if="order.notes" class="space-y-1">
                        <dt class="text-muted-foreground">Notes</dt>
                        <dd>{{ order.notes }}</dd>
                    </div>
                </dl>
            </section>

            <section v-if="order.result" class="space-y-3 rounded-md border p-4" data-test="imaging-order-result">
                <h3 class="text-sm font-medium">Result metadata</h3>
                <dl class="space-y-2 text-sm">
                    <div v-if="order.result.findings" class="space-y-1">
                        <dt class="text-muted-foreground">Findings</dt>
                        <dd data-test="imaging-order-result-findings">{{ order.result.findings }}</dd>
                    </div>
                    <div v-if="order.result.impression" class="space-y-1">
                        <dt class="text-muted-foreground">Impression</dt>
                        <dd data-test="imaging-order-result-impression">{{ order.result.impression }}</dd>
                    </div>
                    <div v-if="order.result.reported_at_formatted" class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Reported</dt>
                        <dd>{{ order.result.reported_at_formatted }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section v-if="order.files.length > 0" class="space-y-3 rounded-md border p-4" data-test="imaging-order-files">
            <h3 class="text-sm font-medium">Attachments</h3>
            <ul class="space-y-2 text-sm">
                <li
                    v-for="file in order.files"
                    :key="file.id"
                    class="flex items-center justify-between gap-4"
                    data-test="imaging-order-file-item"
                >
                    <span>{{ file.original_name }}</span>
                    <span class="text-muted-foreground text-xs">{{ file.size_formatted }}</span>
                </li>
            </ul>
        </section>

        <Button as-child variant="outline">
            <Link :href="imagingIndex()" data-test="back-to-imaging-link">Back to imaging orders</Link>
        </Button>
    </div>
</template>
