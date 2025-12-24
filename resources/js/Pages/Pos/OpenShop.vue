<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    hasActiveSession: {
        type: Boolean,
        default: false,
    },
    activeSession: {
        type: Object,
        default: null,
    },
    today: {
        type: String,
        default: '',
    },
});

const form = useForm({
    opening_cash: '',
});

const submit = () => {
    form.post('/pos/open');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <Head title="Buka Toko" />

    <EmployeeLayout>
        <template #header>
            <h2 class="text-lg font-semibold text-gray-800">Buka Toko</h2>
        </template>

        <div class="max-w-md mx-auto">
            <!-- Already has active session -->
            <div v-if="hasActiveSession" class="bg-green-50 border border-green-200 rounded-lg p-6">
                <div class="text-center">
                    <div class="text-4xl mb-4">✅</div>
                    <h3 class="text-lg font-semibold text-green-800 mb-2">
                        Sesi Toko Aktif
                    </h3>
                    <p class="text-green-600 mb-4">
                        Anda sudah membuka toko hari ini
                    </p>
                    <div class="bg-white rounded-lg p-4 mb-4 text-left">
                        <p class="text-sm text-gray-600">Kas Awal:</p>
                        <p class="text-xl font-bold text-gray-800">
                            {{ formatCurrency(activeSession?.opening_cash || 0) }}
                        </p>
                        <p class="text-sm text-gray-500 mt-2">
                            Dibuka: {{ activeSession?.opened_at }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <Link
                            href="/pos/consignment"
                            class="bg-blue-600 text-white px-4 py-3 rounded-lg text-center hover:bg-blue-700"
                        >
                            📦 Input Barang Titipan
                        </Link>
                        <Link
                            href="/pos/close"
                            class="bg-orange-600 text-white px-4 py-3 rounded-lg text-center hover:bg-orange-700"
                        >
                            🔒 Tutup Toko
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Open new session form -->
            <div v-else class="bg-white rounded-lg shadow p-6">
                <div class="text-center mb-6">
                    <div class="text-4xl mb-2">🏪</div>
                    <h3 class="text-lg font-semibold text-gray-800">Buka Sesi Toko</h3>
                    <p class="text-sm text-gray-500">{{ today }}</p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Kas Awal (Modal)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">
                                Rp
                            </span>
                            <input
                                v-model="form.opening_cash"
                                type="number"
                                min="0"
                                step="1000"
                                class="w-full border rounded-lg pl-10 pr-4 py-3 text-lg"
                                placeholder="0"
                                required
                            />
                        </div>
                        <p v-if="form.errors.opening_cash" class="text-red-500 text-sm mt-1">
                            {{ form.errors.opening_cash }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Membuka...' : '🚀 Buka Toko Sekarang' }}
                    </button>
                </form>
            </div>
        </div>
    </EmployeeLayout>
</template>