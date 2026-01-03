<!-- Rivaldi | 202312050 -->
<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, reactive, computed, watch } from 'vue';
import { formatMoney } from '@/utils/formatMoney';

// Lucide icons
import {
    Plus,
    Minus,
    Trash2,
    PlusCircle,
    X,
    ShoppingBag,
    Package,
    User,
    Calendar,
    Box,
    ChevronDown
} from 'lucide-vue-next';

const props = defineProps({
    selectedTemplate: {
        type: Object,
        default: null,
    },
    boxTemplates: {
        type: Array,
        default: () => [],
    },
    hasActiveSession: {
        type: Boolean,
        default: false,
    },
    activeSession: {
        type: Object,
        default: null,
    },
});

// Session info for sidebar status
const sessionInfo = computed(() => ({
  shiftName: props.activeSession?.shift_name ?? null,
  openingBalance: props.activeSession?.opening_cash ?? 0,
  isActive: props.hasActiveSession,
  openedAt: props.activeSession?.opened_at ?? null,
}));

// Selected template ID for dropdown
const selectedTemplateId = ref('');

const form = useForm({
    customer_name: '',
    pickup_datetime: '',
    quantity: 1, // Number of boxes to order
    items: [],
});

// Watch for template selection and auto-fill items
watch(selectedTemplateId, (newId) => {
    if (!newId) return;
    
    const template = props.boxTemplates.find(t => t.id === parseInt(newId));
    if (template && template.items_json) {
        // Clear existing items and add template items
        form.items = [];
        
        // items_json is an array of item names, we need to create item objects
        const itemNames = Array.isArray(template.items_json) 
            ? template.items_json 
            : JSON.parse(template.items_json);
            
        itemNames.forEach(itemName => {
            form.items.push({
                product_name: itemName,
                quantity: 1,
                unit_price: Math.round(template.price / itemNames.length), // Distribute price evenly
            });
        });
    }
});

// Add a new line item
const addItem = () => {
    form.items.push({
        product_name: '',
        quantity: 1,
        unit_price: '',
    });
};

// Remove a line item
const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Calculate subtotal (items only)
const itemsSubtotal = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (item.quantity * (item.unit_price || 0));
    }, 0);
});

// Calculate grand total (items * box quantity)
const calculatedTotal = computed(() => {
    return itemsSubtotal.value * (form.quantity || 1);
});

// Get minimum datetime (now + 1 hour)
const minDateTime = computed(() => {
    const now = new Date();
    now.setHours(now.getHours() + 1);
    return now.toISOString().slice(0, 16);
});

const submit = () => {
    form.post('/pos/box', {
        onSuccess: () => {
            form.reset();
        },
    });
};

// Clear template selection (for custom order)
const clearTemplate = () => {
    selectedTemplateId.value = '';
    form.items = [];
    form.quantity = 1;
    addItem();
};

// Initialize with one empty item
if (form.items.length === 0) {
    addItem();
}
</script>

<template>
    <Head title="Buat Order Box" />

    <EmployeeLayout :session-info="sessionInfo">
        <template #header>
            <div class="flex items-center gap-2">
                <Package class="w-6 h-6 text-gray-600" />
                <h2 class="text-lg font-semibold text-gray-800">Buat Order Box</h2>
            </div>
        </template>

        <div class="max-w-6xl mx-auto">
            <form @submit.prevent="submit">
                <div class="lg:flex lg:gap-6">
                    <!-- Left Column: Form Input -->
                    <div class="lg:w-7/12 space-y-5">
                        <!-- Template Selection Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                            <div class="flex items-center gap-2 mb-4">
                                <Box class="w-5 h-5 text-emerald-600" />
                                <label class="font-medium text-gray-800">Template Box</label>
                                <span class="text-xs text-gray-400">(Opsional)</span>
                            </div>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <select
                                        v-model="selectedTemplateId"
                                        class="w-full appearance-none border border-gray-200 rounded-xl px-4 py-3 pr-10 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    >
                                        <option value="">-- Pilih Template atau Input Manual --</option>
                                        <optgroup label="Heavy Meal">
                                            <option 
                                                v-for="template in boxTemplates.filter(t => t.type === 'heavy_meal')" 
                                                :key="template.id" 
                                                :value="template.id"
                                            >
                                                {{ template.name }} - {{ formatMoney(template.price) }}
                                            </option>
                                        </optgroup>
                                        <optgroup label="Snack Box">
                                            <option 
                                                v-for="template in boxTemplates.filter(t => t.type === 'snack_box')" 
                                                :key="template.id" 
                                                :value="template.id"
                                            >
                                                {{ template.name }} - {{ formatMoney(template.price) }}
                                            </option>
                                        </optgroup>
                                    </select>
                                    <ChevronDown class="w-5 h-5 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>
                                <button
                                    v-if="selectedTemplateId"
                                    type="button"
                                    @click="clearTemplate"
                                    class="p-3 text-gray-500 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors duration-200"
                                    title="Reset ke input manual"
                                >
                                    <X class="w-5 h-5" />
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Pilih template untuk auto-fill, atau biarkan kosong untuk input manual
                            </p>
                        </div>

                        <!-- Customer Info Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
                            <div class="flex items-center gap-2 mb-2">
                                <User class="w-5 h-5 text-emerald-600" />
                                <span class="font-medium text-gray-800">Informasi Pelanggan</span>
                            </div>

                            <!-- Customer Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Nama Pelanggan
                                </label>
                                <input
                                    v-model="form.customer_name"
                                    type="text"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    placeholder="Masukkan nama pelanggan"
                                    required
                                />
                                <p v-if="form.errors.customer_name" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.customer_name }}
                                </p>
                            </div>

                            <!-- Box Quantity -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Jumlah Box
                                </label>
                                <div class="flex items-center gap-3">
                                    <div class="inline-flex items-center rounded-xl border border-gray-200 overflow-hidden">
                                        <button
                                            type="button"
                                            @click="form.quantity = Math.max(1, form.quantity - 1)"
                                            class="w-12 h-12 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 transition-colors duration-200"
                                        >
                                            <Minus class="w-5 h-5" />
                                        </button>
                                        <input
                                            v-model.number="form.quantity"
                                            type="number"
                                            min="1"
                                            class="w-16 h-12 text-center text-lg font-semibold border-x border-gray-200 focus:outline-none focus:bg-indigo-50"
                                            required
                                        />
                                        <button
                                            type="button"
                                            @click="form.quantity++"
                                            class="w-12 h-12 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 transition-colors duration-200"
                                        >
                                            <Plus class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <span class="text-sm text-gray-500">box</span>
                                </div>
                                <p v-if="form.errors.quantity" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.quantity }}
                                </p>
                            </div>

                            <!-- Pickup DateTime -->
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-1.5">
                                    <Calendar class="w-4 h-4" />
                                    Tanggal & Waktu Pengambilan
                                </label>
                                <input
                                    v-model="form.pickup_datetime"
                                    type="datetime-local"
                                    :min="minDateTime"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    required
                                />
                                <p v-if="form.errors.pickup_datetime" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.pickup_datetime }}
                                </p>
                            </div>
                        </div>

                        <!-- Line Items Card -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center gap-2">
                                    <ShoppingBag class="w-5 h-5 text-emerald-600" />
                                    <label class="font-medium text-gray-800">
                                        Item per Box
                                    </label>
                                    <span v-if="selectedTemplateId" class="text-xs text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">
                                        dari template
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="addItem"
                                    class="inline-flex items-center gap-1.5 text-sm bg-emerald-50 text-emerald-700 px-4 py-2 rounded-xl hover:bg-emerald-100 font-medium transition-colors duration-200"
                                >
                                    <PlusCircle class="w-5 h-5" />
                                    Tambah Item
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(item, index) in form.items"
                                    :key="index"
                                    class="bg-gray-50 rounded-xl p-4 border border-gray-100"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div class="sm:col-span-1">
                                                <label class="block text-xs text-gray-500 mb-1 font-medium">Nama Produk</label>
                                                <input
                                                    v-model="item.product_name"
                                                    type="text"
                                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                    placeholder="Nasi Goreng"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1 font-medium">Qty/Box</label>
                                                <input
                                                    v-model.number="item.quantity"
                                                    type="number"
                                                    min="1"
                                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-center focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-500 mb-1 font-medium">Harga</label>
                                                <div class="relative">
                                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                                                    <input
                                                        v-model.number="item.unit_price"
                                                        type="number"
                                                        min="0"
                                                        class="w-full border border-gray-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                                        required
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            v-if="form.items.length > 1"
                                            type="button"
                                            @click="removeItem(index)"
                                            class="mt-6 p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                        >
                                            <Trash2 class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <div class="mt-3 text-right text-sm text-gray-600">
                                        Subtotal: <span class="font-semibold">{{ formatMoney(item.quantity * (item.unit_price || 0)) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Summary (visible on small screens) - Orange Gradient -->
                        <div class="lg:hidden bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl border border-orange-200 p-5">
                            <div class="space-y-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">Harga per Box</span>
                                    <span class="font-semibold">{{ formatMoney(itemsSubtotal) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-600">Jumlah Box</span>
                                    <span class="font-semibold">× {{ form.quantity }}</span>
                                </div>
                                <div class="border-t border-orange-200 pt-3 flex justify-between items-center">
                                    <span class="font-semibold text-gray-700">Total</span>
                                    <span class="text-2xl font-bold text-orange-600">
                                        {{ formatMoney(calculatedTotal) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Actions -->
                        <div class="lg:hidden flex gap-3">
                            <Link
                                href="/pos/box"
                                class="flex-1 text-center border border-gray-200 text-gray-700 py-3.5 rounded-xl hover:bg-gray-50 font-medium transition-colors duration-200"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing || form.items.length === 0"
                                class="flex-1 bg-gradient-to-r from-orange-500 to-orange-600 text-white py-3.5 rounded-xl font-semibold hover:from-orange-600 hover:to-orange-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-orange-500/25 transition-all duration-200"
                            >
                                {{ form.processing ? 'Menyimpan...' : 'Buat Order' }}
                            </button>
                        </div>
                    </div>

                    <!-- Right Column: Sticky Summary (Desktop) -->
                    <div class="hidden lg:block lg:w-5/12">
                        <div class="sticky top-8">
                            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                                <!-- Receipt Header - Orange Gradient -->
                                <div class="bg-gradient-to-r from-orange-500 to-amber-500 p-5 text-white text-center">
                                    <Package class="w-10 h-10 mx-auto mb-2 opacity-80" />
                                    <h3 class="font-semibold text-lg">Ringkasan Order</h3>
                                </div>

                                <!-- Receipt Body -->
                                <div class="p-5">
                                    <!-- Items List -->
                                    <div class="border-b border-dashed border-gray-200 pb-4 mb-4">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-3 font-medium">Item per Box</p>
                                        <div v-if="form.items.length === 0" class="text-center py-4 text-gray-400 text-sm">
                                            Belum ada item
                                        </div>
                                        <div v-else class="space-y-2">
                                            <div v-for="(item, index) in form.items" :key="index" class="flex justify-between text-sm">
                                                <span class="text-gray-700">
                                                    {{ item.product_name || 'Item ' + (index + 1) }}
                                                    <span class="text-gray-400">× {{ item.quantity }}</span>
                                                </span>
                                                <span class="font-medium text-gray-800">{{ formatMoney(item.quantity * (item.unit_price || 0)) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price Calculation -->
                                    <div class="space-y-3 border-b border-dashed border-gray-200 pb-4 mb-4">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Harga per Box</span>
                                            <span class="font-semibold text-gray-800">{{ formatMoney(itemsSubtotal) }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Jumlah Box</span>
                                            <span class="font-semibold text-gray-800 flex items-center gap-1">
                                                <Package class="w-4 h-4" />
                                                × {{ form.quantity }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Total -->
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-gray-800 text-lg">TOTAL</span>
                                        <span class="text-3xl font-bold text-orange-600">
                                            {{ formatMoney(calculatedTotal) }}
                                        </span>
                                    </div>

                                    <!-- Customer Info Preview -->
                                    <div v-if="form.customer_name || form.pickup_datetime" class="mt-5 pt-4 border-t border-gray-100 space-y-2 text-sm">
                                        <div v-if="form.customer_name" class="flex items-center gap-2 text-gray-600">
                                            <User class="w-4 h-4" />
                                            <span>{{ form.customer_name }}</span>
                                        </div>
                                        <div v-if="form.pickup_datetime" class="flex items-center gap-2 text-gray-600">
                                            <Calendar class="w-4 h-4" />
                                            <span>{{ new Date(form.pickup_datetime).toLocaleString('id-ID', { 
                                                weekday: 'short',
                                                day: 'numeric', 
                                                month: 'short',
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            }) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="p-5 border-t border-gray-100 space-y-3">
                                    <button
                                        type="submit"
                                        :disabled="form.processing || form.items.length === 0"
                                        class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-3.5 rounded-xl font-semibold hover:from-orange-600 hover:to-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg shadow-orange-500/30"
                                    >
                                        {{ form.processing ? 'Menyimpan...' : 'Buat Order' }}
                                    </button>
                                    <Link
                                        href="/pos/box"
                                        class="block w-full text-center border border-gray-200 text-gray-700 py-3 rounded-xl hover:bg-gray-50 font-medium transition-colors duration-200"
                                    >
                                        Batal
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </EmployeeLayout>
</template>
