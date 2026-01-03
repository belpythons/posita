<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';
import { formatMoney } from '@/utils/formatMoney';
import { AlertTriangle, BarChart3, Package, Wallet, Lock, Store, Loader2 } from 'lucide-vue-next';
import ActionButton from '@/Components/ActionButton.vue';

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

// Reactive object to track leftover qty for each item
const leftoverQty = reactive({});

// Initialize leftover quantities from consignments
props.consignments.forEach(item => {
    // Default to qty_initial (nothing sold) - user will input actual leftover
    leftoverQty[item.id] = item.qty_remaining ?? item.qty_initial;
});

const form = useForm({
    actual_cash: '',
    notes: '',
});

// Calculate sold quantity for an item
const getQtySold = (item) => {
    const leftover = parseInt(leftoverQty[item.id]) || 0;
    return Math.max(0, item.qty_initial - leftover);
};

// Calculate income for an item
const getItemIncome = (item) => {
    return getQtySold(item) * item.selling_price;
};

// Calculate profit for an item
const getItemProfit = (item) => {
    return getQtySold(item) * (item.selling_price - item.base_price);
};

// Calculated totals based on current leftover inputs
const calculatedTotals = computed(() => {
    let totalIncome = 0;
    let totalProfit = 0;
    let totalSold = 0;

    props.consignments.forEach(item => {
        const qtySold = getQtySold(item);
        totalSold += qtySold;
        totalIncome += qtySold * item.selling_price;
        totalProfit += qtySold * (item.selling_price - item.base_price);
    });

    const expectedCash = (props.summary?.opening_cash || 0) + totalIncome;

    return {
        total_income: totalIncome,
        total_profit: totalProfit,
        total_sold: totalSold,
        opening_cash: props.summary?.opening_cash || 0,
        expected_cash: expectedCash,
    };
});

// Cash difference: expected - actual (positive = kurang/shortage, negative = lebih/overage)
const cashDifference = computed(() => {
    if (!form.actual_cash) return 0;
    return calculatedTotals.value.expected_cash - parseFloat(form.actual_cash);
});

const submit = () => {
    // Build leftovers array from reactive object
    const leftovers = props.consignments.map(item => ({
        id: item.id,
        qty_remaining: parseInt(leftoverQty[item.id]) || 0,
    }));

    form.transform(data => ({
        ...data,
        leftovers: leftovers,
    })).post('/pos/close');
};

// Validate leftover doesn't exceed initial
const validateLeftover = (item) => {
    const max = item.qty_initial;
    if (leftoverQty[item.id] > max) {
        leftoverQty[item.id] = max;
    }
    if (leftoverQty[item.id] < 0) {
        leftoverQty[item.id] = 0;
    }
};

// Session info for sidebar status
const sessionInfo = computed(() => ({
  shiftName: props.currentSession?.shift_name ?? 'Sesi Aktif',
  openingBalance: props.currentSession?.opening_cash ?? props.summary?.opening_cash ?? 0,
  isActive: props.hasSession,
  openedAt: props.currentSession?.opened_at ?? null,
}));
</script>

<template>
    <Head title="Tutup Toko" />

    <EmployeeLayout :session-info="sessionInfo">
        <template #header>
            <h2 class="text-lg font-semibold text-gray-800">Tutup Toko</h2>
        </template>

        <div class="max-w-lg mx-auto">
            <!-- No active session -->
            <div v-if="!hasSession" class="bg-orange-50 border border-orange-200 rounded-lg p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center mx-auto mb-4">
                    <AlertTriangle class="w-6 h-6 text-orange-600" />
                </div>
                <h3 class="text-lg font-semibold text-orange-800 mb-2">
                    Tidak Ada Sesi Aktif
                </h3>
                <p class="text-orange-600 mb-4">
                    Anda belum membuka toko hari ini
                </p>
                <Link
                    href="/pos/open"
                    class="inline-flex items-center gap-2 bg-emerald-600 text-white px-6 py-2 rounded-lg hover:bg-emerald-700 shadow-lg shadow-emerald-500/30 transition-all font-semibold"
                >
                    <Store class="w-5 h-5" />
                    Buka Toko
                </Link>
            </div>

            <!-- Close session form -->
            <div v-else class="space-y-4">
                <!-- Summary Card -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <BarChart3 class="w-5 h-5 text-primary" />
                        Ringkasan Hari Ini
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Kas Awal</p>
                            <p class="font-medium">{{ formatMoney(calculatedTotals.opening_cash) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Pendapatan</p>
                            <p class="font-medium text-green-600">{{ formatMoney(calculatedTotals.total_income) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Total Profit</p>
                            <p class="font-medium text-emerald-700">{{ formatMoney(calculatedTotals.total_profit) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Kas Akhir (Estimasi)</p>
                            <p class="font-bold text-lg">{{ formatMoney(calculatedTotals.expected_cash) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Items with Leftover Input -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <Package class="w-5 h-5 text-primary" />
                        Input Sisa Stok
                    </h3>
                    <p class="text-sm text-gray-500 mb-4">Masukkan jumlah sisa stok untuk setiap barang</p>
                    
                    <div v-if="consignments.length === 0" class="text-gray-500 text-center py-4">
                        Belum ada barang
                    </div>
                    <div v-else class="space-y-4 max-h-72 overflow-y-auto">
                        <div
                            v-for="item in consignments"
                            :key="item.id"
                            class="border rounded-lg p-3"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium">{{ item.product_name }}</p>
                                    <p class="text-xs text-gray-500">{{ item.partner?.name }}</p>
                                </div>
                                <p class="text-sm text-gray-600">@ {{ formatMoney(item.selling_price) }}</p>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-2 text-sm">
                                <div>
                                    <p class="text-gray-500 text-xs">Stok Awal</p>
                                    <p class="font-medium">{{ item.qty_initial }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs">Sisa Stok</p>
                                    <input
                                        v-model.number="leftoverQty[item.id]"
                                        @blur="validateLeftover(item)"
                                        type="number"
                                        :min="0"
                                        :max="item.qty_initial"
                                        class="w-full border rounded px-2 py-1 text-center font-medium"
                                    />
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs">Terjual</p>
                                    <p class="font-medium text-green-600">{{ getQtySold(item) }}</p>
                                </div>
                            </div>
                            
                            <div class="mt-2 pt-2 border-t flex justify-between text-sm">
                                <span class="text-gray-500">Pendapatan:</span>
                                <span class="font-medium text-green-600">{{ formatMoney(getItemIncome(item)) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Close Form -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <Wallet class="w-5 h-5 text-primary" />
                        Tutup Sesi
                    </h3>
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
                            <p v-if="form.errors.actual_cash" class="text-xs text-orange-500 mt-1">
                                {{ form.errors.actual_cash }}
                            </p>
                        </div>

                        <!-- Cash Difference -->
                        <div v-if="form.actual_cash" class="p-3 rounded-lg" :class="cashDifference <= 0 ? 'bg-emerald-50' : 'bg-orange-50'">
                            <p class="text-sm" :class="cashDifference <= 0 ? 'text-emerald-800' : 'text-orange-800'">
                                Selisih: {{ formatMoney(Math.abs(cashDifference)) }}
                                <span v-if="cashDifference > 0">(Kurang)</span>
                                <span v-else-if="cashDifference < 0">(Lebih)</span>
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

                        <ActionButton
                            type="submit"
                            variant="negative"
                            :icon="Lock"
                            :loading="form.processing"
                            :disabled="form.processing"
                            full-width
                        >
                            {{ form.processing ? 'Menutup...' : 'Tutup Toko' }}
                        </ActionButton>
                    </form>
                </div>
            </div>
        </div>
    </EmployeeLayout>
</template>
