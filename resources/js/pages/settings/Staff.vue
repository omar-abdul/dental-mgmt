<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import StaffController from '@/actions/App/Http/Controllers/Settings/StaffController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as staffIndex } from '@/routes/staff';

type StaffMember = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
};

type RoleOption = {
    value: string;
    label: string;
};

defineProps<{
    staff: StaffMember[];
    roles: RoleOption[];
    creating?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Staff',
                href: staffIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Staff" />

    <h1 class="sr-only">Staff</h1>

    <div class="space-y-8">
        <Heading
            variant="small"
            title="Staff"
            description="Create and manage clinic staff accounts"
        />

        <Form
            v-bind="StaffController.store.form()"
            v-slot="{ errors, processing }"
            class="space-y-4"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" required autocomplete="name" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="role">Role</Label>
                <select
                    id="role"
                    name="role"
                    required
                    class="border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs"
                >
                    <option
                        v-for="role in roles"
                        :key="role.value"
                        :value="role.value"
                    >
                        {{ role.label }}
                    </option>
                </select>
                <InputError :message="errors.role" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </div>

            <Button type="submit" :disabled="processing">
                Create staff member
            </Button>
        </Form>

        <div class="space-y-3">
            <h3 class="text-sm font-medium">Current staff</h3>
            <ul class="divide-border divide-y rounded-md border">
                <li
                    v-for="member in staff"
                    :key="member.id"
                    class="flex items-center justify-between px-4 py-3 text-sm"
                >
                    <div>
                        <p class="font-medium">{{ member.name }}</p>
                        <p class="text-muted-foreground">{{ member.email }}</p>
                    </div>
                    <span class="text-muted-foreground">{{
                        member.role_label
                    }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
