<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as appointmentsIndex } from '@/routes/appointments';
import { index as billingIndex } from '@/routes/billing';
import { index as inventoryIndex } from '@/routes/inventory';
import { index as patientsIndex } from '@/routes/patients';
import type { Auth } from '@/types/auth';

type Kpis = {
    todays_appointments: number | null;
    active_patients: number | null;
    unpaid_invoices: number | null;
    low_stock_items: number | null;
};

type WeeklyVisit = {
    key: string;
    label: string;
    count: number;
};

type ActivityItem = {
    id: number;
    action: string;
    description: string | null;
    user_name: string;
    created_at: string | null;
};

type UpcomingItem = {
    id: number;
    number: string;
    starts_at: string;
    time_label: string;
    patient_name: string;
    dentist_name: string;
    status: string;
    status_label: string;
};

const props = defineProps<{
    kpis: Kpis;
    weekly_visits: WeeklyVisit[] | null;
    recent_activity: ActivityItem[];
    upcoming: UpcomingItem[] | null;
}>();

const page = usePage<{ auth: Auth }>();
const user = computed(() => page.props.auth.user);

const allowedModules = computed(
    () => user.value?.allowed_modules ?? [],
);

const kpiCards = computed(() => {
    const cards: Array<{
        key: keyof Kpis;
        title: string;
        value: number;
        href: string | null;
    }> = [];

    if (props.kpis.todays_appointments !== null) {
        cards.push({
            key: 'todays_appointments',
            title: "Today's appointments",
            value: props.kpis.todays_appointments,
            href: allowedModules.value.includes('appointments')
                ? appointmentsIndex().url
                : null,
        });
    }

    if (props.kpis.active_patients !== null) {
        cards.push({
            key: 'active_patients',
            title: 'Active patients',
            value: props.kpis.active_patients,
            href: allowedModules.value.includes('patients')
                ? patientsIndex().url
                : null,
        });
    }

    if (props.kpis.unpaid_invoices !== null) {
        cards.push({
            key: 'unpaid_invoices',
            title: 'Unpaid invoices',
            value: props.kpis.unpaid_invoices,
            href: allowedModules.value.includes('billing')
                ? billingIndex().url
                : null,
        });
    }

    if (props.kpis.low_stock_items !== null) {
        cards.push({
            key: 'low_stock_items',
            title: 'Low-stock items',
            value: props.kpis.low_stock_items,
            href: allowedModules.value.includes('inventory')
                ? inventoryIndex().url
                : null,
        });
    }

    return cards;
});

const weeklyMaxCount = computed(() => {
    if (!props.weekly_visits?.length) {
        return 0;
    }

    return Math.max(...props.weekly_visits.map((day) => day.count));
});

function barHeight(count: number): string {
    if (weeklyMaxCount.value === 0) {
        return '0%';
    }

    const percentage = (count / weeklyMaxCount.value) * 100;

    return `${Math.max(percentage, count > 0 ? 8 : 0)}%`;
}

function formatActivityTime(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'cancelled' || status === 'no_show') {
        return 'destructive';
    }

    if (status === 'confirmed' || status === 'checked_in') {
        return 'secondary';
    }

    return 'default';
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <Heading
                title="Overview"
                description="Clinic dashboard overview"
            />

            <div
                v-if="user"
                class="bg-muted/50 inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm"
            >
                <span class="font-medium">{{ user.name }}</span>
                <span class="text-muted-foreground">·</span>
                <span class="text-muted-foreground">{{ user.role_label }}</span>
            </div>
        </div>

        <div
            v-if="kpiCards.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        >
            <Card v-for="card in kpiCards" :key="card.key">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="text-muted-foreground text-sm font-medium"
                    >
                        <Link
                            v-if="card.href"
                            :href="card.href"
                            class="hover:text-foreground transition-colors"
                        >
                            {{ card.title }}
                        </Link>
                        <span v-else>{{ card.title }}</span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">{{ card.value }}</p>
                </CardContent>
            </Card>
        </div>

        <Card v-if="weekly_visits">
            <CardHeader>
                <CardTitle class="text-base">Weekly visits</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex h-40 items-end justify-between gap-2">
                    <div
                        v-for="day in weekly_visits"
                        :key="day.key"
                        class="flex flex-1 flex-col items-center gap-2"
                    >
                        <span class="text-muted-foreground text-xs tabular-nums">
                            {{ day.count }}
                        </span>
                        <div
                            class="bg-muted flex h-28 w-full items-end rounded-md"
                        >
                            <div
                                class="bg-primary w-full rounded-md transition-all"
                                :style="{ height: barHeight(day.count) }"
                            />
                        </div>
                        <span class="text-muted-foreground text-xs font-medium">
                            {{ day.label }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Recent activity</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul
                        v-if="recent_activity.length"
                        class="divide-border divide-y"
                    >
                        <li
                            v-for="item in recent_activity"
                            :key="item.id"
                            class="py-3 first:pt-0 last:pb-0"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="text-sm font-medium capitalize">
                                        {{ item.action }}
                                    </p>
                                    <p
                                        v-if="item.description"
                                        class="text-muted-foreground text-sm"
                                    >
                                        {{ item.description }}
                                    </p>
                                    <p class="text-muted-foreground text-xs">
                                        {{ item.user_name }}
                                    </p>
                                </div>
                                <time
                                    class="text-muted-foreground shrink-0 text-xs whitespace-nowrap"
                                >
                                    {{ formatActivityTime(item.created_at) }}
                                </time>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-muted-foreground text-sm">
                        No recent activity yet.
                    </p>
                </CardContent>
            </Card>

            <Card v-if="upcoming !== null">
                <CardHeader>
                    <CardTitle class="text-base">Upcoming today</CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="upcoming.length"
                        class="divide-border overflow-hidden rounded-md border"
                    >
                        <table class="w-full text-sm">
                            <thead class="bg-muted/50 text-left">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Time</th>
                                    <th class="px-4 py-3 font-medium">
                                        Patient
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Dentist
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in upcoming"
                                    :key="item.id"
                                    class="border-border border-t"
                                >
                                    <td class="px-4 py-3 tabular-nums">
                                        {{ item.time_label }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.patient_name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.dentist_name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge
                                            :variant="
                                                statusVariant(item.status)
                                            "
                                        >
                                            {{ item.status_label }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-muted-foreground text-sm">
                        No upcoming appointments for the rest of today.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
