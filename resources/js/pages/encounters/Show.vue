<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import EncounterController from '@/actions/App/Http/Controllers/EncounterController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as chartIndex } from '@/routes/chart';
import { chart as patientsChart } from '@/routes/patients';

type Amendment = {
    id: number;
    body: string;
    created_at_formatted: string | null;
    author_name: string | null;
};

type SoapNoteDetail = {
    subjective: string | null;
    objective: string | null;
    assessment: string | null;
    plan: string | null;
    is_signed: boolean;
    signed_at_formatted: string | null;
    signed_by_name: string | null;
    amendments: Amendment[];
};

defineProps<{
    encounter: {
        id: number;
        number: string;
        visited_at_formatted: string;
        patient: {
            id: number;
            full_name: string;
            patient_number: string;
        };
        dentist_name: string;
        treatment_id: number | null;
    };
    soapNote: SoapNoteDetail | null;
    canUpdateSoap: boolean;
    canSign: boolean;
    canAmend: boolean;
}>();

const amendmentBody = ref('');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Chart', href: chartIndex() },
            { title: 'Encounter' },
        ],
    },
});
</script>

<template>
    <Head :title="`Encounter ${encounter.number}`" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                variant="small"
                :title="encounter.number"
                :description="`${encounter.patient.full_name} · ${encounter.visited_at_formatted}`"
            />
            <Badge :variant="soapNote?.is_signed ? 'secondary' : 'outline'" data-test="encounter-sign-badge">
                {{ soapNote?.is_signed ? 'Signed' : 'Draft' }}
            </Badge>
        </div>

        <section class="rounded-md border p-4 text-sm">
            <dl class="grid gap-2 sm:grid-cols-2">
                <div>
                    <dt class="text-muted-foreground">Patient</dt>
                    <dd>{{ encounter.patient.full_name }} ({{ encounter.patient.patient_number }})</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Dentist</dt>
                    <dd>{{ encounter.dentist_name }}</dd>
                </div>
            </dl>
            <div class="mt-3">
                <Button as-child variant="outline" size="sm">
                    <Link :href="patientsChart(encounter.patient.id)">Open patient chart</Link>
                </Button>
            </div>
        </section>

        <section v-if="soapNote" class="space-y-4 rounded-md border p-4">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-sm font-medium">SOAP note</h3>
                <p v-if="soapNote.is_signed" class="text-muted-foreground text-xs" data-test="signed-meta">
                    Signed by {{ soapNote.signed_by_name }} · {{ soapNote.signed_at_formatted }}
                </p>
            </div>

            <Form
                v-if="canUpdateSoap"
                v-bind="EncounterController.updateSoap.form(encounter.id)"
                v-slot="{ errors, processing }"
                class="grid gap-4 lg:grid-cols-2"
                data-test="soap-form"
            >
                <div class="space-y-2">
                    <Label for="subjective">Subjective</Label>
                    <textarea
                        id="subjective"
                        name="subjective"
                        rows="4"
                        class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                        :default-value="soapNote.subjective ?? ''"
                        data-test="soap-subjective"
                    />
                    <InputError :message="errors.subjective" />
                </div>
                <div class="space-y-2">
                    <Label for="objective">Objective</Label>
                    <textarea
                        id="objective"
                        name="objective"
                        rows="4"
                        class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                        :default-value="soapNote.objective ?? ''"
                        data-test="soap-objective"
                    />
                    <InputError :message="errors.objective" />
                </div>
                <div class="space-y-2">
                    <Label for="assessment">Assessment</Label>
                    <textarea
                        id="assessment"
                        name="assessment"
                        rows="4"
                        class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                        :default-value="soapNote.assessment ?? ''"
                        data-test="soap-assessment"
                    />
                    <InputError :message="errors.assessment" />
                </div>
                <div class="space-y-2">
                    <Label for="plan">Plan</Label>
                    <textarea
                        id="plan"
                        name="plan"
                        rows="4"
                        class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                        :default-value="soapNote.plan ?? ''"
                        data-test="soap-plan"
                    />
                    <InputError :message="errors.plan" />
                </div>
                <div class="lg:col-span-2 flex flex-wrap gap-3">
                    <Button type="submit" :disabled="processing" data-test="save-soap-button">
                        Save draft
                    </Button>
                </div>
            </Form>

            <Form
                v-if="canUpdateSoap && canSign"
                v-bind="EncounterController.sign.form(encounter.id)"
                v-slot="{ processing: signing }"
            >
                <Button type="submit" variant="secondary" :disabled="signing" data-test="sign-encounter-button">
                    Sign encounter
                </Button>
            </Form>

            <div v-if="!canUpdateSoap" class="grid gap-4 lg:grid-cols-2 text-sm">
                <div class="space-y-1 rounded-md border p-3">
                    <div class="font-medium">Subjective</div>
                    <p data-test="soap-subjective-display">{{ soapNote.subjective || '—' }}</p>
                </div>
                <div class="space-y-1 rounded-md border p-3">
                    <div class="font-medium">Objective</div>
                    <p data-test="soap-objective-display">{{ soapNote.objective || '—' }}</p>
                </div>
                <div class="space-y-1 rounded-md border p-3">
                    <div class="font-medium">Assessment</div>
                    <p data-test="soap-assessment-display">{{ soapNote.assessment || '—' }}</p>
                </div>
                <div class="space-y-1 rounded-md border p-3">
                    <div class="font-medium">Plan</div>
                    <p data-test="soap-plan-display">{{ soapNote.plan || '—' }}</p>
                </div>
            </div>

            <div v-if="soapNote.is_signed && canAmend" class="space-y-3 rounded-md border bg-muted/20 p-4">
                <h4 class="text-sm font-medium">Add amendment</h4>
                <Form
                    v-bind="EncounterController.storeAmendment.form(encounter.id)"
                    v-slot="{ errors, processing }"
                    class="space-y-3"
                    data-test="amendment-form"
                >
                    <div class="space-y-2">
                        <Label for="amendment_body">Amendment text</Label>
                        <textarea
                            id="amendment_body"
                            name="body"
                            rows="3"
                            v-model="amendmentBody"
                            class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                            data-test="amendment-body"
                        />
                        <InputError :message="errors.body" />
                    </div>
                    <Button type="submit" :disabled="processing" data-test="submit-amendment-button">
                        Submit amendment
                    </Button>
                </Form>
            </div>

            <div v-if="soapNote.amendments.length > 0" class="space-y-2">
                <h4 class="text-sm font-medium">Amendments</h4>
                <ul class="space-y-2 text-sm">
                    <li
                        v-for="amendment in soapNote.amendments"
                        :key="amendment.id"
                        class="rounded-md border p-3"
                        data-test="amendment-row"
                    >
                        <div class="text-muted-foreground text-xs">
                            {{ amendment.author_name }} · {{ amendment.created_at_formatted }}
                        </div>
                        <p class="mt-1">{{ amendment.body }}</p>
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>
