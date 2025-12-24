<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    partners: { type: Array, required: true },
    activeSessionId: { type: Number, default: null }
});

const page = usePage();
const showSuccess = ref(false);

// Mengambil pesan sukses dari flash session Laravel
const flashSuccess = computed(() => page.props.flash?.success);

// Menampilkan notifikasi otomatis saat ada flash message
watch(flashSuccess, (newVal) => {
    if (newVal) {
        showSuccess.value = true;
        setTimeout(() => showSuccess.value = false, 5000);
    }
});

const form = useForm({
    partner_id: '',
    product_name: '',
    initial_stock: 1,
    base_price: '', // Diubah ke string kosong agar placeholder terlihat
    markup: 10,
    shop_session_id: props.activeSessionId, 
});

const submit = () => {
    form.post(route('pos.store-open'), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset form kecuali partner agar input berkelanjutan lebih cepat
            form.reset('product_name', 'initial_stock', 'base_price');
        },
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-2xl mx-auto">
            
            <div class="mb-8">
                <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                    Open Shop
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Tambahkan produk konsinyasi baru untuk memulai sesi penjualan hari ini.
                </p>
            </div>

            <transition name="slide-fade">
                <div v-if="showSuccess && flashSuccess" 
                     class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg shadow-sm flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-emerald-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm font-medium text-emerald-800">{{ flashSuccess }}</p>
                    </div>
                    <button @click="showSuccess = false" class="text-emerald-500 hover:text-emerald-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </transition>

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                <form @submit.prevent="submit" class="p-8 space-y-6">
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Partner / Supplier</label>
                        <select v-model="form.partner_id" 
                                class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all sm:text-sm py-3"
                                :class="{'border-red-400 ring-red-100': form.errors.partner_id}" required>
                            <option value="" disabled>Pilih Partner...</option>
                            <option v-for="partner in partners" :key="partner.id" :value="partner.id">{{ partner.name }}</option>
                        </select>
                        <p v-if="form.errors.partner_id" class="mt-2 text-xs text-red-500">{{ form.errors.partner_id }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Nama Produk</label>
                        <input v-model="form.product_name" type="text" placeholder="Contoh: Keripik Singkong Madu"
                               class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all sm:text-sm py-3"
                               :class="{'border-red-400': form.errors.product_name}" required />
                        <p v-if="form.errors.product_name" class="mt-2 text-xs text-red-500">{{ form.errors.product_name }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Stok Awal</label>
                            <input v-model="form.initial_stock" type="number" min="1"
                                   class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all sm:text-sm py-3" required />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Harga Dasar (Rp)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                                <input v-model="form.base_price" type="number" placeholder="0"
                                       class="w-full bg-gray-50 border-gray-200 rounded-xl pl-10 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all sm:text-sm py-3" required />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Markup (%)</label>
                            <select v-model="form.markup" class="w-full bg-gray-50 border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all sm:text-sm py-3">
                                <option :value="5">5%</option>
                                <option :value="10">10%</option>
                                <option :value="15">15%</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" :disabled="form.processing"
                                class="w-full flex justify-center items-center py-4 px-6 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Memproses...
                            </span>
                            <span v-else>Simpan & Buka Toko</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Animasi Notifikasi */
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
    transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-20px);
    opacity: 0;
}
</style>