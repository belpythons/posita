<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    selectedTemplate: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    box_template_id: props.selectedTemplate.id,
    customer_name: '',
    quantity: 1,
    pickup_datetime: '',
});

const totalPrice = computed(() => {
    return props.selectedTemplate.price * form.quantity;
});

const submit = () => {
    form.post('/pos/box');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
};

// Get minimum datetime (now + 1 hour)
const minDateTime = computed(() => {
    const now = new Date();
    now.setHours(now.getHours() + 1);
    return now.toISOString().slice(0, 16);
});
</script>

<template>
    <Head :title="`Order - ${selectedTemplate.name}`" />

    <EmployeeLayout>
        <template #header>
            <h2 class="text-lg font-semibold text-gray-800">Buat Order</h2>
        </template>

        <div class="max-w-md mx-auto">
            <!-- Template Info -->
            <div class="bg-white rounded-lg shadow p-4 mb-4">
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-3xl">
                        {{ selectedTemplate.type === 'heavy_meal' ? '🍱' : '🍪' }}
                    </span>
                    <div>
                        <h3 class="font-semibold text-lg">{{ selectedTemplate.name }}</h3>
                        <p class="text-green-600 font-bold">
                            {{ formatCurrency(selectedTemplate.price) }} / box
                        </p>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-sm text-gray-600 font-medium mb-1">Isi Box:</p>
                    <ul class="text-sm text-gray-700 list-disc list-inside">
                        <li v-for="(item, idx) in selectedTemplate.items_json" :key="idx">
                            {{ item }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Order Form -->
            <div class="bg-white rounded-lg shadow p-4">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Pelanggan
                        </label>
                        <input
                            v-model="form.customer_name"
                            type="text"
                            class="w-full border rounded-lg px-3 py-2"
                            placeholder="Nama lengkap"
                            required
                        />
                        <p v-if="form.errors.customer_name" class="text-red-500 text-sm mt-1">
                            {{ form.errors.customer_name }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Jumlah Box
                        </label>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="form.quantity = Math.max(1, form.quantity - 1)"
                                class="w-10 h-10 rounded-lg border bg-gray-50 hover:bg-gray-100"
                            >
                                -
                            </button>
                            <input
                                v-model.number="form.quantity"
                                type="number"
                                min="1"
                                class="w-20 border rounded-lg px-3 py-2 text-center"
                            />
                            <button
                                type="button"
                                @click="form.quantity++"
                                class="w-10 h-10 rounded-lg border bg-gray-50 hover:bg-gray-100"
                            >
                                +
                            </button>
                        </div>
                        <p v-if="form.errors.quantity" class="text-red-500 text-sm mt-1">
                            {{ form.errors.quantity }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Waktu Pengambilan
                        </label>
                        <input
                            v-model="form.pickup_datetime"
                            type="datetime-local"
                            :min="minDateTime"
                            class="w-full border rounded-lg px-3 py-2"
                            required
                        />
                        <p v-if="form.errors.pickup_datetime" class="text-red-500 text-sm mt-1">
                            {{ form.errors.pickup_datetime }}
                        </p>
                    </div>

                    <!-- Total -->
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total:</span>
                            <span class="text-2xl font-bold text-green-600">
                                {{ formatCurrency(totalPrice) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <Link
                            href="/pos/box"
                            class="flex-1 py-3 rounded-lg border text-center text-gray-600 hover:bg-gray-50"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Buat Order' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </EmployeeLayout>
</template>
