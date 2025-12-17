<script setup>
import { useForm } from '@inertiajs/vue3';
import ConsignmentCard from '@/Components/ConsignmentCard.vue';

const props = defineProps({
    consignment: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    remaining_stock: 0,
    disposition: '',
});

const submit = () => {
    form.put(route('pos.update-close', props.consignment.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <ConsignmentCard
        :title="consignment.product_name"
        :subtitle="consignment.partner?.name"
        :price="consignment.selling_price"
        :stock="consignment.initial_stock"
    >
        <template #action>
            <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div class="sm:col-span-1">
                    <label :for="`remaining_stock_${consignment.id}`" class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</label>
                    <input
                        :id="`remaining_stock_${consignment.id}`"
                        type="number"
                        v-model="form.remaining_stock"
                        min="0"
                        :max="consignment.initial_stock"
                        class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        required
                    />
                    <div v-if="form.errors.remaining_stock" class="text-red-500 text-xs mt-1">{{ form.errors.remaining_stock }}</div>
                </div>

                <div class="sm:col-span-2">
                    <label :for="`disposition_${consignment.id}`" class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Disposition</label>
                    <select
                        :id="`disposition_${consignment.id}`"
                        v-model="form.disposition"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                    >
                        <option value="">None (Sold All / Kept)</option>
                        <option value="returned">Returned to Partner</option>
                        <option value="donated">Donated</option>
                    </select>
                    <div v-if="form.errors.disposition" class="text-red-500 text-xs mt-1">{{ form.errors.disposition }}</div>
                </div>

                <div class="sm:col-span-1">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                    >
                        Reconcile
                    </button>
                </div>
            </form>
        </template>
    </ConsignmentCard>
</template>
