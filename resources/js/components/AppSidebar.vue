<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    LayoutGrid,
    Package,
    Receipt,
    Settings,
    Stethoscope,
    Users,
    BarChart3,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as appointmentsIndex } from '@/routes/appointments';
import { index as billingIndex } from '@/routes/billing';
import { index as inventoryIndex } from '@/routes/inventory';
import { index as patientsIndex } from '@/routes/patients';
import { index as reportsIndex } from '@/routes/reports';
import { edit as settingsIndex } from '@/routes/profile';
import { index as treatmentsIndex } from '@/routes/treatments';
import type { NavItem } from '@/types';

const page = usePage();

const allowedModules = computed(
    () => page.props.auth.user?.allowed_modules ?? [],
);

const allNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid, module: 'dashboard' },
    { title: 'Patients', href: patientsIndex(), icon: Users, module: 'patients' },
    {
        title: 'Appointments',
        href: appointmentsIndex(),
        icon: CalendarDays,
        module: 'appointments',
    },
    {
        title: 'Treatments',
        href: treatmentsIndex(),
        icon: Stethoscope,
        module: 'treatments',
    },
    { title: 'Billing', href: billingIndex(), icon: Receipt, module: 'billing' },
    {
        title: 'Inventory',
        href: inventoryIndex(),
        icon: Package,
        module: 'inventory',
    },
    { title: 'Reports', href: reportsIndex(), icon: BarChart3, module: 'reports' },
    {
        title: 'Settings',
        href: settingsIndex(),
        icon: Settings,
        module: 'settings',
    },
];

const mainNavItems = computed(() =>
    allNavItems.filter(
        (item) =>
            item.module && allowedModules.value.includes(item.module as string),
    ),
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
