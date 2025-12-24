<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    hasActiveSession: {
        type: Boolean,
        default: false,
    },
    activeSession: {
        type: Object,
        default: null,
    },
    partners: {
        type: Array,
        default: () => [],
    },
    productTemplates: {
        type: Array,
        default: () => [],
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

const showAddModal = ref(false);
const selectedPartnerId = ref('');

const form = useForm({
    partner_id: '',
    product_name: '',
    qty_initial: '',
    base_price: '',
    markup_percent: '10',
});

// Filter templates by selected partner
const filteredTemplates = computed(() => {
    if (!selectedPartnerId.value) return [];
    return props.productTemplates.filter(t => t.partner_id == selectedPartnerId.value);
});

const selectPartner = (partnerId) => {
    selectedPartnerId.value = partnerId;
    form.partner_id = partnerId;
};

const selectTemplate = (template) => {
    form.product_name = template.name;
    form.base_price = template.base_price;
    form.markup_percent = template.default_markup_percent.toString();
};

const calculatedSellingPrice = computed(() => {
    if (!form.base_price || !form.markup_percent) return 0;
    return parseFloat(form.base_price) * (1 + parseFloat(form.markup_percent) / 100);
});

const submit = () => {
    form.post('/pos/consignment', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
            selectedPartnerId.value = '';
        },
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value || 0);
};

// Update sold quantity
const updateSold = (consignment, qtySold) => {
    useForm({ qty_sold: qtySold }).patch(`/pos/consignment/${consignment.id}/sold`);
};
</script>

<template>
    <Head title="Barang Titipan" />

    <EmployeeLayout>
        <template #header>
            <h2 class="text-lg font-semibold text-gray-800">Barang Titipan</h2>
        </template>

        <!-- No active session warning -->
        <div v-if="!hasActiveSession" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <p class="text-yellow-800 text-center">
                ⚠️ Buka toko terlebih dahulu
                <Link href="/pos/open" class="text-blue-600 underline ml-1">Buka Toko</Link>
            </p>
        </div>

        <div v-else>
            <!-- Summary -->
            <div class="bg-blue-50 rounded-lg p-4 mb-4">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-blue-600">Total Item: {{ summary?.total_items || 0 }}</p>
                        <p class="text-sm text-blue-600">Terjual: {{ summary?.total_qty_sold || 0 }} pcs</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-blue-600">Pendapatan</p>
                        <p class="font-bold text-blue-800">{{ formatCurrency(summary?.total_income) }}</p>
                    </div>
                </div>
            </div>

            <!-- Add Button -->
            <button
                @click="showAddModal = true"
                class="w-full bg-green-600 text-white py-3 rounded-lg font-medium mb-4 hover:bg-green-700"
            >
                + Tambah Barang Titipan
            </button>

            <!-- Consignment List -->
            <div class="space-y-3">
                <div v-if="consignments.length === 0" class="text-center py-8 text-gray-500">
                    Belum ada barang hari ini
                </div>
                <div
                    v-for="item in consignments"
                    :key="item.id"
                    class="bg-white rounded-lg shadow p-4"
                >
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="font-medium">{{ item.product_name }}</p>
                            <p class="text-sm text-gray-500">{{ item.partner?.name }}</p>
                        </div>
                        <p class="text-green-600 font-medium">{{ formatCurrency(item.selling_price) }}</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <span>Stok: {{ item.qty_initial }}</span>
                            <span class="mx-2">|</span>
                            <span>Sisa: {{ item.qty_remaining }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Terjual:</label>
                            <input
                                :value="item.qty_sold"
                                @change="updateSold(item, $event.target.value)"
                                type="number"
                                min="0"
                                :max="item.qty_initial"
                                class="w-16 border rounded px-2 py-1 text-center"
                            />
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-t text-sm">
                        <span class="text-gray-500">Subtotal:</span>
                        <span class="font-medium text-green-600 ml-1">
                            {{ formatCurrency(item.subtotal_income) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <div v-if="showAddModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="px-4 py-3 border-b flex justify-between items-center sticky top-0 bg-white">
                    <h3 class="font-semibold">Tambah Barang</h3>
                    <button @click="showAddModal = false" class="text-gray-500">✕</button>
                </div>

                <form @submit.prevent="submit" class="p-4 space-y-4">
                    <!-- Partner Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Penyetok</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="partner in partners"
                                :key="partner.id"
                                type="button"
                                @click="selectPartner(partner.id)"
                                :class="[
                                    'p-2 rounded-lg border text-sm transition-colors',
                                    selectedPartnerId == partner.id
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                {{ partner.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Template Suggestions -->
                    <div v-if="filteredTemplates.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Template Produk</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="template in filteredTemplates"
                                :key="template.id"
                                type="button"
                                @click="selectTemplate(template)"
                                class="px-3 py-1 bg-gray-100 rounded-full text-sm hover:bg-gray-200"
                            >
                                {{ template.name }}
                            </button>
                        </div>
                    </div>

                    <!-- Product Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk</label>
                        <input
                            v-model="form.product_name"
                            type="text"
                            class="w-full border rounded-lg px-3 py-2"
                            required
                        />
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (pcs)</label>
                        <input
                            v-model="form.qty_initial"
                            type="number"
                            min="1"
                            class="w-full border rounded-lg px-3 py-2"
                            required
                        />
                    </div>

                    <!-- Base Price -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga Modal</label>
                        <input
                            v-model="form.base_price"
                            type="number"
                            min="0"
                            class="w-full border rounded-lg px-3 py-2"
                            required
                        />
                    </div>

                    <!-- Markup -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Markup</label>
                        <div class="flex gap-2">
                            <button
                                v-for="markup in [5, 10, 15]"
                                :key="markup"
                                type="button"
                                @click="form.markup_percent = markup.toString()"
                                :class="[
                                    'flex-1 py-2 rounded-lg border transition-colors',
                                    form.markup_percent == markup.toString()
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-gray-200'
                                ]"
                            >
                                {{ markup }}%
                            </button>
                        </div>
                    </div>

                    <!-- Calculated Selling Price -->
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-sm text-gray-600">Harga Jual:</p>
                        <p class="text-xl font-bold text-green-600">
                            {{ formatCurrency(calculatedSellingPrice) }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !form.partner_id"
                        class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </form>
            </div>
        </div>
    </EmployeeLayout>
</template>
