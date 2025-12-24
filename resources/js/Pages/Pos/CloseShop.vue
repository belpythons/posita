<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    hasSession: {
        type: Boolean,
        default: false,
    },
    currentSession: {
        type: Object,
        default: null,
    },
    consignments: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    actual_cash: '',
    notes: '',
});

const submit = () => {
    form.post('/pos/close');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
};

const cashDifference = () => {
    if (!form.actual_cash || !props.summary) return 0;
    return parseFloat(form.actual_cash) - props.summary.expected_cash;
};
</script>

<template>
    <Head title="Tutup Toko" />

    <EmployeeLayout>
        <template #header>
            <h2 class="text-lg font-semibold text-gray-800">Tutup Toko</h2>
        </template>

        <div class="max-w-lg mx-auto">
            <!-- No active session -->
            <div v-if="!hasSession" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <div class="text-4xl mb-4">⚠️</div>
                <h3 class="text-lg font-semibold text-yellow-800 mb-2">
                    Tidak Ada Sesi Aktif
                </h3>
                <p class="text-yellow-600 mb-4">
                    Anda belum membuka toko hari ini
                </p>
                <Link
                    href="/pos/open"
                    class="inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700"
                >
                    Buka Toko
                </Link>
            </div>

            <!-- Close session form -->
            <div v-else class="space-y-4">
                <!-- Summary Card -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800 mb-3">📊 Ringkasan Hari Ini</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Kas Awal</p>
                            <p class="font-medium">{{ formatCurrency(summary?.opening_cash) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Pendapatan</p>
                            <p class="font-medium text-green-600">{{ formatCurrency(summary?.total_income) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Profit</p>
                            <p class="font-medium text-blue-600">{{ formatCurrency(summary?.total_profit) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Kas Akhir (Estimasi)</p>
                            <p class="font-bold text-lg">{{ formatCurrency(summary?.expected_cash) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Items Summary -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800 mb-3">📦 Barang Terjual</h3>
                    <div v-if="consignments.length === 0" class="text-gray-500 text-center py-4">
                        Belum ada barang
                    </div>
                    <div v-else class="space-y-2 max-h-48 overflow-y-auto">
                        <div
                            v-for="item in consignments"
                            :key="item.id"
                            class="flex justify-between items-center text-sm py-2 border-b"
                        >
                            <div>
                                <p class="font-medium">{{ item.product_name }}</p>
                                <p class="text-gray-500">
                                    Terjual: {{ item.qty_sold }} / {{ item.qty_initial }}
                                </p>
                            </div>
                            <p class="font-medium text-green-600">
                                {{ formatCurrency(item.income) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Close Form -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800 mb-3">💰 Tutup Sesi</h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Kas Akhir Aktual (Uang di Laci)
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">
                                    Rp
                                </span>
                                <input
                                    v-model="form.actual_cash"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    class="w-full border rounded-lg pl-10 pr-4 py-3"
                                    placeholder="0"
                                    required
                                />
                            </div>
                            <p v-if="form.errors.actual_cash" class="text-red-500 text-sm mt-1">
                                {{ form.errors.actual_cash }}
                            </p>
                        </div>

                        <!-- Cash Difference -->
                        <div v-if="form.actual_cash" class="p-3 rounded-lg" :class="cashDifference() >= 0 ? 'bg-green-50' : 'bg-red-50'">
                            <p class="text-sm" :class="cashDifference() >= 0 ? 'text-green-800' : 'text-red-800'">
                                Selisih: {{ formatCurrency(cashDifference()) }}
                                <span v-if="cashDifference() > 0">(Lebih)</span>
                                <span v-else-if="cashDifference() < 0">(Kurang)</span>
                                <span v-else>(Pas)</span>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Catatan (Opsional)
                            </label>
                            <textarea
                                v-model="form.notes"
                                class="w-full border rounded-lg px-3 py-2"
                                rows="2"
                                placeholder="Catatan tambahan..."
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full bg-orange-600 text-white py-3 rounded-lg font-medium hover:bg-orange-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menutup...' : '🔒 Tutup Toko' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </EmployeeLayout>
</template>
