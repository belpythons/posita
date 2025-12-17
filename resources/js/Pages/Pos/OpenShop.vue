<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    partners: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    partner_id: '',
    product_name: '',
    initial_stock: 0,
    base_price: 0,
    markup: 0,
});

const submit = () => {
    form.post(route('pos.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Open Shop - Add Consignment</h2>
        
        <form @submit.prevent="submit" class="space-y-6 bg-white p-6 rounded-lg shadow">
            <div>
                <label for="partner_id" class="block text-sm font-medium text-gray-700">Partner</label>
                <select
                    id="partner_id"
                    v-model="form.partner_id"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                    required
                >
                    <option value="" disabled>Select a partner</option>
                    <option v-for="partner in partners" :key="partner.id" :value="partner.id">
                        {{ partner.name }}
                    </option>
                </select>
                <div v-if="form.errors.partner_id" class="text-red-500 text-sm mt-1">{{ form.errors.partner_id }}</div>
            </div>

            <div>
                <label for="product_name" class="block text-sm font-medium text-gray-700">Product Name</label>
                <input
                    type="text"
                    id="product_name"
                    v-model="form.product_name"
                    class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                    required
                />
                <div v-if="form.errors.product_name" class="text-red-500 text-sm mt-1">{{ form.errors.product_name }}</div>
            </div>

            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-3">
                <div>
                    <label for="initial_stock" class="block text-sm font-medium text-gray-700">Initial Stock</label>
                    <input
                        type="number"
                        id="initial_stock"
                        v-model="form.initial_stock"
                        min="0"
                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        required
                    />
                    <div v-if="form.errors.initial_stock" class="text-red-500 text-sm mt-1">{{ form.errors.initial_stock }}</div>
                </div>

                <div>
                    <label for="base_price" class="block text-sm font-medium text-gray-700">Base Price</label>
                    <input
                        type="number"
                        id="base_price"
                        v-model="form.base_price"
                        min="0"
                        step="0.01"
                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        required
                    />
                    <div v-if="form.errors.base_price" class="text-red-500 text-sm mt-1">{{ form.errors.base_price }}</div>
                </div>

                <div>
                    <label for="markup" class="block text-sm font-medium text-gray-700">Markup (%)</label>
                    <input
                        type="number"
                        id="markup"
                        v-model="form.markup"
                        min="0"
                        class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        required
                    />
                    <div v-if="form.errors.markup" class="text-red-500 text-sm mt-1">{{ form.errors.markup }}</div>
                </div>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                    Save Consignment
                </button>
            </div>
        </form>
    </div>
</template>
