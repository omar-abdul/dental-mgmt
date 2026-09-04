<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import NotificationTemplateController from '@/actions/App/Http/Controllers/Settings/NotificationTemplateController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index as notificationTemplatesIndex } from '@/routes/notification-templates';

type Template = {
    code: string;
    channel: string;
    name: string;
    body: string;
};

defineProps<{
    templates: Template[];
    placeholders: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Notification templates',
                href: notificationTemplatesIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Notification templates" />

    <h1 class="sr-only">Notification templates</h1>

    <div class="space-y-8">
        <Heading
            variant="small"
            title="Notification templates"
            description="Edit SMS template bodies used for appointment reminders and receipts. Placeholders are replaced when a reminder is queued."
        />

        <p class="text-muted-foreground text-sm">
            Available placeholders:
            <span
                v-for="placeholder in placeholders"
                :key="placeholder"
                class="bg-muted mr-1 inline-block rounded px-1.5 py-0.5 font-mono text-xs"
            >
                {{ placeholder }}
            </span>
        </p>

        <article
            v-for="template in templates"
            :key="template.code"
            class="space-y-4 rounded-lg border p-4"
            :data-test="`template-${template.code}`"
        >
            <div>
                <h2 class="text-base font-medium">{{ template.name }}</h2>
                <p class="text-muted-foreground text-sm">
                    {{ template.code }} · {{ template.channel }}
                </p>
            </div>

            <Form
                v-bind="NotificationTemplateController.update.form(template.code)"
                v-slot="{ errors, processing }"
                class="space-y-4"
            >
                <div class="grid gap-2">
                    <Label :for="`body-${template.code}`">Message body</Label>
                    <textarea
                        :id="`body-${template.code}`"
                        name="body"
                        rows="4"
                        required
                        class="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm shadow-xs"
                        :data-test="`template-body-${template.code}`"
                    >{{ template.body }}</textarea>
                    <InputError :message="errors.body" />
                </div>

                <Button
                    type="submit"
                    :disabled="processing"
                    :data-test="`save-template-${template.code}`"
                >
                    Save template
                </Button>
            </Form>
        </article>
    </div>
</template>
