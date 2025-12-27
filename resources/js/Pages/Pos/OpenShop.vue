<script setup> // Nurita Wahuyuni | 202312061
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import BaseButton from '@/Components/BaseButton.vue';
import Badge from '@/Components/Badge.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { formatMoney } from '@/utils/formatMoney';
import { computed } from 'vue';

const props = defineProps({
    hasActiveSession: {
        type: Boolean,
        default: false,
    },
    activeSession: {
        type: Object,
        default: null,
    },
    lastClosedSession: {
        type: Object,
        default: null,
    },
    today: {
        type: String,
        default: '',
    },
    partners: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    opening_cash: '',
    selected_partners: [],
    consignments: [],
});

// Transform selected partner IDs into consignments array
const consignmentsData = computed(() => {
    return form.selected_partners.map(partnerId => {
        const partner = props.partners.find(p => p.id === parseInt(partnerId));
        return {
            partner_id: parseInt(partnerId),
            product_template_id: partner?.product_template_id || partner?.default_product_template_id,
        };
    }).filter(item => item.product_template_id); // Filter out partners without product_template
});

const submit = () => {
    form.clearErrors();
    
    // Check if we have at least opening cash
    if (!form.opening_cash) {
        form.setError('opening_cash', 'Kas awal harus diisi');
        return;
    }

    // Update form consignments data before validating
    form.consignments = consignmentsData.value;

    // Check if we have at least one consignment (required)
    if (form.consignments.length === 0) {
        form.setError('consignments', 'Minimal pilih 1 pemasok/partner');
        return;
    }

    form.post('/pos/open', {
        onSuccess: () => {
            // Server will redirect via RedirectResponse
        },
        onError: () => {
            // Errors will be automatically set on form.errors
        },
    });
};
</script>

<template>
    <Head title="Buka Toko" />

    <EmployeeLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-900">Manajemen Toko</h2>
                <Badge :label="hasActiveSession ? 'Aktif' : 'Siap Dibuka'" :variant="hasActiveSession ? 'success' : 'info'" />
            </div>
        </template>

        <div class="max-w-3xl mx-auto p-4 sm:p-6 lg:p-8">
            <!-- Active Session State -->
            <div v-if="hasActiveSession" class="space-y-6">
                <!-- Status Card -->
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl shadow-sm border border-orange-200 overflow-hidden">
                    <div class="p-8 text-center">
                        <div class="text-6xl mb-4">✅</div>
                        <h3 class="text-4xl font-bold text-orange-900 mb-2">
                            Sesi Toko Aktif
                        </h3>
                        <p class="text-orange-700 text-2xl mb-8">
                            Toko Anda telah dibuka untuk hari ini
                        </p>

                        <!-- Session Details Card -->
                        <div class="bg-white rounded-lg p-6 mb-8 shadow-sm">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="text-left border-r border-gray-200">
                                    <p class="text-lg text-gray-500 font-medium mb-2">KAS AWAL</p>
                                    <p class="text-4xl font-bold text-orange-600">
                                        {{ formatMoney(activeSession?.opening_cash || 0) }}
                                    </p>
                                </div>
                                <div class="text-left">
                                    <p class="text-lg text-gray-500 font-medium mb-2">WAKTU PEMBUKAAN</p>
                                    <p class="text-2xl text-gray-700 font-semibold">
                                        {{ activeSession?.opened_at }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <Link
                                href="/pos/consignment"
                                as="button"
                                class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-150 shadow-sm hover:shadow-md"
                            >
                                <span class="mr-2">📦</span>
                                Input Barang Titipan
                            </Link>
                            <Link
                                href="/pos/close"
                                as="button"
                                class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-3 bg-orange-600 text-white font-medium rounded-lg hover:bg-orange-700 transition-colors duration-150 shadow-sm hover:shadow-md"
                            >
                                <span class="mr-2">🔒</span>
                                Tutup Toko
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inactive Session State - Form -->
            <div v-else class="space-y-6">
                <!-- Last Session Report Card -->
                <div v-if="lastClosedSession" class="bg-white rounded-xl shadow-sm border border-orange-200 p-6 hover:shadow-md transition-shadow duration-150">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <p class="text-base font-semibold text-orange-600 uppercase tracking-wide">Sesi Sebelumnya</p>
                            <p class="text-base text-gray-500 mt-1">{{ lastClosedSession.closed_at }}</p>
                        </div>
                        <a
                            :href="`/pos/report/${lastClosedSession.id}`"
                            class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-base font-medium rounded-lg hover:bg-orange-700 transition-colors duration-150 whitespace-nowrap"
                            target="_blank"
                        >
                            📄 Download Laporan
                        </a>
                    </div>
                </div>

                <!-- Open Session Form Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-orange-700 px-6 sm:px-8 py-5 text-center">
                        <div class="text-4xl mb-3">🏪</div>
                        <h3 class="text-xl font-bold text-white mb-1">Buka Sesi Toko</h3>
                        <p class="text-orange-100 text-sm">Mulai transaksi untuk hari ini</p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 sm:p-8 space-y-6">
                        <!-- Opening Cash Input -->
                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-3">
                                Kas Awal (Modal)
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-semibold text-lg">
                                    Rp
                                </span>
                                <input
                                    v-model="form.opening_cash"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    class="w-full border border-gray-300 rounded-lg pl-12 pr-4 py-3 text-lg placeholder-gray-400 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-150"
                                    placeholder="Masukkan jumlah"
                                    required
                                />
                            </div>
                            <p v-if="form.errors.opening_cash" class="text-red-500 text-sm mt-2 font-medium">
                                {{ form.errors.opening_cash }}
                            </p>
                        </div>

                        <!-- Consignment Selection -->
                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-3">
                                Pilih Barang Titipan <span class="text-red-500 font-normal">*</span>
                            </label>
                            <select
                                v-model="form.selected_partners"
                                multiple
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-lg text-gray-900 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-150 bg-white"
                            >
                                <option disabled value="">
                                    Pilih partner/pemasok
                                </option>
                                <option v-for="partner in partners" :key="partner.id" :value="partner.id.toString()">
                                    {{ partner.name }}
                                </option>
                            </select>
                            <p class="text-sm text-gray-500 mt-2">
                                💡 Tahan Ctrl/Cmd untuk memilih lebih dari satu
                            </p>
                            <p v-if="form.selected_partners.length > 0" class="text-sm text-orange-600 mt-2">
                                ✓ {{ form.selected_partners.length }} pemasok dipilih
                            </p>
                            <p v-if="form.errors.consignments" class="text-red-500 text-sm mt-2 font-medium">
                                {{ form.errors.consignments }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-orange-600 to-orange-700 text-white font-semibold text-lg rounded-lg hover:from-orange-700 hover:to-orange-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150 shadow-sm hover:shadow-md"
                            >
                                <span v-if="!form.processing" class="flex items-center">
                                    <span class="mr-2">🚀</span>
                                    Buka Toko Sekarang
                                </span>
                                <span v-else class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Membuka Toko...
                                </span>
                            </button>
                        </div>

                        <!-- Error Message -->
                        <p v-if="form.errors.error" class="text-red-600 text-center font-medium bg-red-50 rounded-lg p-3 text-base">
                            ⚠️ {{ form.errors.error }}
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </EmployeeLayout>
</template> 
