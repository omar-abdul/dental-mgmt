<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppointmentController from '@/actions/App/Http/Controllers/AppointmentController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PatientPicker, { type PatientSearchResult } from '@/components/PatientPicker.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as appointmentsIndex } from '@/routes/appointments';

type WorkingHours = {
    weekday: number;
    opens_at: string | null;
    closes_at: string | null;
    is_closed: boolean;
};

type Column = {
    id: number;
    label: string;
    dentist_name: string;
    room_name: string | null;
    default_chair_id: number | null;
};

type AppointmentItem = {
    id: number;
    number: string;
    dentist_id: number;
    chair_id: number;
    patient_id: number;
    fee_item_id: number | null;
    patient_name: string;
    patient_label: string;
    fee_name: string | null;
    calendar_color: string;
    starts_at: string;
    ends_at: string;
    starts_at_time: string;
    ends_at_time: string;
    status: string;
    reason: string | null;
    notes: string | null;
    can_update: boolean;
    can_cancel: boolean;
    can_check_in: boolean;
};

type Option = {
    id: number;
    label: string;
    default_chair_id?: number | null;
    default_duration_minutes?: number;
    calendar_color?: string;
};

const props = defineProps<{
    date: string;
    workingHours: WorkingHours;
    columns: Column[];
    appointments: AppointmentItem[];
    dentists: Option[];
    chairs: Option[];
    feeItems: Option[];
    canBook: boolean;
    canCheckIn: boolean;
}>();

const showBookDialog = ref(false);
const showEditDialog = ref(false);
const editingAppointment = ref<AppointmentItem | null>(null);
const bookPatientId = ref('');
const editPatientId = ref('');
const editSelectedPatient = ref<PatientSearchResult | null>(null);

const selectedDate = ref(props.date);

watch(
    () => props.date,
    (value) => {
        selectedDate.value = value;
    },
);

const timeSlots = computed(() => {
    if (props.workingHours.is_closed || !props.workingHours.opens_at || !props.workingHours.closes_at) {
        return [];
    }

    const slots: string[] = [];
    const [openHour, openMinute] = props.workingHours.opens_at.split(':').map(Number);
    const [closeHour, closeMinute] = props.workingHours.closes_at.split(':').map(Number);

    let minutes = openHour * 60 + openMinute;
    const endMinutes = closeHour * 60 + closeMinute;

    while (minutes < endMinutes) {
        const hour = Math.floor(minutes / 60);
        const minute = minutes % 60;
        slots.push(`${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`);
        minutes += 30;
    }

    return slots;
});

const totalMinutes = computed(() => {
    if (props.workingHours.is_closed || !props.workingHours.opens_at || !props.workingHours.closes_at) {
        return 0;
    }

    const [openHour, openMinute] = props.workingHours.opens_at.split(':').map(Number);
    const [closeHour, closeMinute] = props.workingHours.closes_at.split(':').map(Number);

    return closeHour * 60 + closeMinute - (openHour * 60 + openMinute);
});

function minutesFromOpen(time: string): number {
    if (!props.workingHours.opens_at) {
        return 0;
    }

    const [openHour, openMinute] = props.workingHours.opens_at.split(':').map(Number);
    const [hour, minute] = time.split(':').map(Number);

    return hour * 60 + minute - (openHour * 60 + openMinute);
}

function appointmentStyle(appointment: AppointmentItem): Record<string, string> {
    const topMinutes = minutesFromOpen(appointment.starts_at_time);
    const endMinutes = minutesFromOpen(appointment.ends_at_time);
    const durationMinutes = Math.max(endMinutes - topMinutes, 15);
    const total = totalMinutes.value || 1;

    return {
        top: `${(topMinutes / total) * 100}%`,
        height: `${(durationMinutes / total) * 100}%`,
        backgroundColor: appointment.calendar_color,
    };
}

function appointmentsForColumn(dentistId: number): AppointmentItem[] {
    return props.appointments.filter(
        (appointment) =>
            appointment.dentist_id === dentistId &&
            appointment.status !== 'cancelled',
    );
}

function navigateDate(offset: number): void {
    const current = new Date(`${props.date}T12:00:00`);
    current.setDate(current.getDate() + offset);

    router.get(
        appointmentsIndex({ query: { date: current.toISOString().slice(0, 10) } }).url,
        {},
        { preserveState: true, preserveScroll: true },
    );
}

function goToDate(): void {
    router.get(
        appointmentsIndex({ query: { date: selectedDate.value } }).url,
        {},
        { preserveState: true, preserveScroll: true },
    );
}

function openEditDialog(appointment: AppointmentItem): void {
    editingAppointment.value = appointment;
    editPatientId.value = String(appointment.patient_id);
    editSelectedPatient.value = {
        id: appointment.patient_id,
        label: appointment.patient_label,
        patient_number: '',
        full_name: appointment.patient_name,
        phone: '',
    };
    showEditDialog.value = true;
}

function closeEditDialog(): void {
    showEditDialog.value = false;
    editingAppointment.value = null;
    editPatientId.value = '';
    editSelectedPatient.value = null;
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'cancelled' || status === 'no_show') {
        return 'destructive';
    }

    if (status === 'checked_in' || status === 'in_progress' || status === 'in_treatment') {
        return 'default';
    }

    return 'secondary';
}

function defaultBookStartsAt(): string {
    if (props.workingHours.opens_at) {
        return `${props.date}T${props.workingHours.opens_at}`;
    }

    return `${props.date}T08:00`;
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appointments',
                href: appointmentsIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Appointments" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Appointments"
                description="Day calendar for booking and check-in"
            />

            <Button v-if="canBook && !workingHours.is_closed" data-test="book-appointment-button" @click="showBookDialog = true">
                Book appointment
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button variant="outline" size="sm" @click="navigateDate(-1)">
                Previous
            </Button>
            <Input
                v-model="selectedDate"
                type="date"
                class="w-auto"
                @change="goToDate"
            />
            <Button variant="outline" size="sm" @click="navigateDate(1)">
                Next
            </Button>
            <Button variant="secondary" size="sm" as-child>
                <Link :href="appointmentsIndex().url">Today</Link>
            </Button>
        </div>

        <div
            v-if="workingHours.is_closed"
            class="text-muted-foreground rounded-md border px-4 py-12 text-center"
        >
            The clinic is closed on this day.
        </div>

        <div v-else class="overflow-x-auto rounded-md border">
            <div class="flex min-w-[720px]">
                <div class="w-16 shrink-0 border-r">
                    <div class="bg-muted/50 h-10 border-b px-2 py-3 text-xs font-medium">
                        Time
                    </div>
                    <div
                        v-for="slot in timeSlots"
                        :key="slot"
                        class="text-muted-foreground flex h-12 items-start border-b px-2 pt-1 text-xs"
                    >
                        {{ slot }}
                    </div>
                </div>

                <div
                    v-for="column in columns"
                    :key="column.id"
                    class="min-w-40 flex-1 border-r last:border-r-0"
                >
                    <div
                        class="bg-muted/50 h-10 border-b px-2 py-3 text-xs font-medium"
                    >
                        {{ column.label }}
                    </div>

                    <div
                        class="relative"
                        :style="{ height: `${timeSlots.length * 3}rem` }"
                    >
                        <div
                            v-for="slot in timeSlots"
                            :key="`${column.id}-${slot}`"
                            class="h-12 border-b"
                        />

                        <button
                            v-for="appointment in appointmentsForColumn(column.id)"
                            :key="appointment.id"
                            type="button"
                            class="absolute inset-x-1 overflow-hidden rounded-md px-2 py-1 text-left text-xs text-white shadow-sm"
                            :style="appointmentStyle(appointment)"
                            @click="openEditDialog(appointment)"
                        >
                            <p class="truncate font-medium">
                                {{ appointment.patient_name }}
                            </p>
                            <p
                                v-if="appointment.fee_name"
                                class="truncate opacity-90"
                            >
                                {{ appointment.fee_name }}
                            </p>
                            <p class="opacity-80">
                                {{ appointment.starts_at_time }}–{{
                                    appointment.ends_at_time
                                }}
                            </p>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="appointments.length > 0" class="space-y-2">
            <h2 class="text-sm font-medium">All appointments</h2>
            <div class="divide-border overflow-hidden rounded-md border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Time</th>
                            <th class="px-4 py-3 font-medium">Patient</th>
                            <th class="px-4 py-3 font-medium">Procedure</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-border divide-y">
                        <tr v-for="appointment in appointments" :key="appointment.id">
                            <td class="px-4 py-3">
                                {{ appointment.starts_at_time }}–{{ appointment.ends_at_time }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ appointment.patient_name }}
                            </td>
                            <td class="px-4 py-3">
                                {{ appointment.fee_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant(appointment.status)">
                                    {{ appointment.status.replace('_', ' ') }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        v-if="appointment.can_update"
                                        variant="outline"
                                        size="sm"
                                        @click="openEditDialog(appointment)"
                                    >
                                        Edit
                                    </Button>
                                    <Form
                                        v-if="appointment.can_check_in"
                                        v-bind="
                                            AppointmentController.checkIn.form(
                                                appointment.id,
                                            )
                                        "
                                        v-slot="{ processing }"
                                    >
                                        <Button
                                            type="submit"
                                            size="sm"
                                            :disabled="processing"
                                            data-test="check-in-appointment-button"
                                        >
                                            Check in
                                        </Button>
                                    </Form>
                                    <Form
                                        v-if="appointment.can_cancel"
                                        v-bind="
                                            AppointmentController.cancel.form(
                                                appointment.id,
                                            )
                                        "
                                        v-slot="{ processing }"
                                    >
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            size="sm"
                                            :disabled="processing"
                                        >
                                            Cancel
                                        </Button>
                                    </Form>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <Dialog v-model:open="showBookDialog">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Book appointment</DialogTitle>
                <DialogDescription>
                    Schedule a patient visit for {{ date }}.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="AppointmentController.store.form()"
                v-slot="{ errors, processing }"
                class="space-y-4"
                @success="showBookDialog = false; bookPatientId = ''"
            >
                <PatientPicker
                    id="patient_id"
                    v-model="bookPatientId"
                    required
                    :error="errors.patient_id"
                />

                <div class="grid gap-2">
                    <Label for="dentist_id">Dentist</Label>
                    <select
                        id="dentist_id"
                        name="dentist_id"
                        required
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="" disabled selected>Select dentist</option>
                        <option
                            v-for="dentist in dentists"
                            :key="dentist.id"
                            :value="dentist.id"
                        >
                            {{ dentist.label }}
                        </option>
                    </select>
                    <InputError :message="errors.dentist_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="chair_id">Chair</Label>
                    <select
                        id="chair_id"
                        name="chair_id"
                        required
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="" disabled selected>Select chair</option>
                        <option
                            v-for="chair in chairs"
                            :key="chair.id"
                            :value="chair.id"
                        >
                            {{ chair.label }}
                        </option>
                    </select>
                    <InputError :message="errors.chair_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="fee_item_id">Procedure (optional)</Label>
                    <select
                        id="fee_item_id"
                        name="fee_item_id"
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="">None</option>
                        <option
                            v-for="feeItem in feeItems"
                            :key="feeItem.id"
                            :value="feeItem.id"
                        >
                            {{ feeItem.label }}
                        </option>
                    </select>
                    <InputError :message="errors.fee_item_id" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="starts_at">Start time</Label>
                        <Input
                            id="starts_at"
                            name="starts_at"
                            type="datetime-local"
                            :default-value="defaultBookStartsAt()"
                            required
                        />
                        <InputError :message="errors.starts_at" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="duration_minutes">Duration (minutes)</Label>
                        <Input
                            id="duration_minutes"
                            name="duration_minutes"
                            type="number"
                            min="5"
                            step="5"
                            placeholder="From procedure or 30"
                        />
                        <InputError :message="errors.duration_minutes" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="reason">Reason (optional)</Label>
                    <Input id="reason" name="reason" />
                    <InputError :message="errors.reason" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="showBookDialog = false"
                    >
                        Close
                    </Button>
                    <Button type="submit" :disabled="processing" data-test="book-appointment-submit">Book</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="showEditDialog"
        @update:open="
            (open) => {
                if (!open) {
                    closeEditDialog();
                }
            }
        "
    >
        <DialogContent v-if="editingAppointment" class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Edit appointment</DialogTitle>
                <DialogDescription>
                    {{ editingAppointment.number }} — {{ editingAppointment.patient_name }}
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="
                    AppointmentController.update.form(editingAppointment.id)
                "
                v-slot="{ errors, processing }"
                class="space-y-4"
                @success="closeEditDialog()"
            >
                <PatientPicker
                    id="edit_patient_id"
                    v-model="editPatientId"
                    :selected="editSelectedPatient"
                    required
                    :error="errors.patient_id"
                />

                <div class="grid gap-2">
                    <Label for="edit_dentist_id">Dentist</Label>
                    <select
                        id="edit_dentist_id"
                        name="dentist_id"
                        required
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                    >
                        <option
                            v-for="dentist in dentists"
                            :key="dentist.id"
                            :value="dentist.id"
                            :selected="dentist.id === editingAppointment.dentist_id"
                        >
                            {{ dentist.label }}
                        </option>
                    </select>
                    <InputError :message="errors.dentist_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit_chair_id">Chair</Label>
                    <select
                        id="edit_chair_id"
                        name="chair_id"
                        required
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                    >
                        <option
                            v-for="chair in chairs"
                            :key="chair.id"
                            :value="chair.id"
                            :selected="chair.id === editingAppointment.chair_id"
                        >
                            {{ chair.label }}
                        </option>
                    </select>
                    <InputError :message="errors.chair_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit_fee_item_id">Procedure</Label>
                    <select
                        id="edit_fee_item_id"
                        name="fee_item_id"
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                    >
                        <option value="">None</option>
                        <option
                            v-for="feeItem in feeItems"
                            :key="feeItem.id"
                            :value="feeItem.id"
                            :selected="feeItem.id === editingAppointment.fee_item_id"
                        >
                            {{ feeItem.label }}
                        </option>
                    </select>
                    <InputError :message="errors.fee_item_id" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="edit_starts_at">Start time</Label>
                        <Input
                            id="edit_starts_at"
                            name="starts_at"
                            type="datetime-local"
                            :default-value="editingAppointment.starts_at.slice(0, 16)"
                            required
                        />
                        <InputError :message="errors.starts_at" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit_duration_minutes">Duration (minutes)</Label>
                        <Input
                            id="edit_duration_minutes"
                            name="duration_minutes"
                            type="number"
                            min="5"
                            step="5"
                        />
                        <InputError :message="errors.duration_minutes" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="edit_reason">Reason</Label>
                    <Input
                        id="edit_reason"
                        name="reason"
                        :default-value="editingAppointment.reason ?? ''"
                    />
                    <InputError :message="errors.reason" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeEditDialog">
                        Close
                    </Button>
                    <Button type="submit" :disabled="processing">Save</Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
