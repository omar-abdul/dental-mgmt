<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import SupplierController from '@/actions/App/Http/Controllers/SupplierController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { index as inventoryIndex } from '@/routes/inventory';
import { index as suppliersIndex } from '@/routes/inventory/suppliers';

type SupplierListItem = {
    id: number;
    name: string;
    contact_name: string | null;
    phone: string | null;
    email: string | null;
};

type PaginatedSuppliers = {
    data: SupplierListItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

defineProps<{
    suppliers: PaginatedSuppliers;
    search: string;
    canCreate: boolean;
}>();

const showCreateDialog = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Inventory', href: inventoryIndex() },
            { title: 'Suppliers', href: suppliersIndex() },
        ],
    },
});
</script>

<template>
    <Head title="Suppliers" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                variant="small"
                title="Suppliers"
                description="Manage inventory suppliers"
            />

            <Button
                v-if="canCreate"
                data-test="add-supplier-button"
                @click="showCreateDialog = true"
            >
                Add supplier
            </Button>
        </div>

        <form :action="suppliersIndex().url" method="get" class="flex gap-2">
            <Input
                name="search"
                :default-value="search"
                placeholder="Search by name"
                class="max-w-md"
                data-test="supplier-search-input"
            />
            <Button type="submit" variant="secondary" data-test="supplier-search-button">
                Search
            </Button>
        </form>

        <div class="divide-border overflow-hidden rounded-md border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Contact</th>
                        <th class="px-4 py-3 font-medium">Phone</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                    </tr>
                </thead>
                <tbody class="divide-border divide-y">
                    <tr v-if="suppliers.data.length === 0">
                        <td colspan="4" class="text-muted-foreground px-4 py-8 text-center">
                            No suppliers found.
                        </td>
                    </tr>
                    <tr v-for="supplier in suppliers.data" :key="supplier.id">
                        <td class="px-4 py-3 font-medium">{{ supplier.name }}</td>
                        <td class="px-4 py-3">{{ supplier.contact_name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ supplier.phone ?? '—' }}</td>
                        <td class="px-4 py-3">{{ supplier.email ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Dialog v-model:open="showCreateDialog">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Add supplier</DialogTitle>
                <DialogDescription>
                    Register a new inventory supplier.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="SupplierController.store.form()"
                v-slot="{ errors, processing }"
                class="space-y-4"
                @success="showCreateDialog = false"
            >
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" name="name" required />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="contact_name">Contact name</Label>
                    <Input id="contact_name" name="contact_name" />
                    <InputError :message="errors.contact_name" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input id="phone" name="phone" />
                        <InputError :message="errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" name="email" type="email" />
                        <InputError :message="errors.email" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="showCreateDialog = false">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing" data-test="create-supplier-button">
                        Create supplier
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
