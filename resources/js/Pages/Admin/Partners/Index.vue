<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import { formatMoney } from '@/utils/formatMoney';

const props = defineProps({
    partners: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const editingPartner = ref(null);

const form = useForm({
    name: '',
    phone: '',
    address: '',
    is_active: true,
    product_templates: [],
});

const openCreateModal = () => {
    editingPartner.value = null;
    form.reset();
    form.product_templates = [];
    showModal.value = true;
};

const openEditModal = (partner) => {
    editingPartner.value = partner;
    form.name = partner.name;
    form.phone = partner.phone || '';
    form.address = partner.address || '';
    form.is_active = partner.is_active;
    form.product_templates = partner.product_templates?.map(t => ({
        id: t.id,
        name: t.name,
        base_price: parseFloat(t.base_price),
        default_selling_price: parseFloat(t.default_selling_price),
    })) || [];
    showModal.value = true;
};

const addTemplate = () => {
    form.product_templates.push({
        id: null,
        name: '',
        base_price: '',
        default_selling_price: '',
    });
};

const removeTemplate = (index) => {
    form.product_templates.splice(index, 1);
};

const submit = () => {
    if (editingPartner.value) {
        form.put(`/admin/partners/${editingPartner.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/admin/partners', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deletePartner = (partner) => {
    if (confirm(`Hapus partner "${partner.name}"?`)) {
        useForm({}).delete(`/admin/partners/${partner.id}`);
    }
};
</script>

<template>
    <Head title="Partners" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Partners (Penyetok)</h2>
        </template>

        <!-- Header Actions -->
        <div class="flex justify-between items-center mb-6">
            <p class="text-gray-600">Total: {{ partners.length }} partner</p>
            <button
                @click="openCreateModal"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
            >
                + Tambah Partner
            </button>
        </div>

        <!-- Partners Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telepon</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Template</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="partner in partners" :key="partner.id">
                        <td class="px-6 py-4 font-medium">{{ partner.name }}</td>
                        <td class="px-6 py-4">{{ partner.phone || '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">
                                {{ partner.product_templates?.length || 0 }} produk
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'px-2 py-1 text-xs rounded-full',
                                    partner.is_active
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'
                                ]"
                            >
                                {{ partner.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button
                                @click="openEditModal(partner)"
                                class="text-blue-600 hover:text-blue-800 mr-3"
                            >
                                Edit
                            </button>
                            <button
                                @click="deletePartner(partner)"
                                class="text-red-600 hover:text-red-800"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b sticky top-0 bg-white">
                    <h3 class="text-lg font-semibold">
                        {{ editingPartner ? 'Edit Partner' : 'Tambah Partner' }}
                    </h3>
                </div>
                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <!-- Partner Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full border rounded-lg px-3 py-2"
                                required
                            />
                            <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="w-full border rounded-lg px-3 py-2"
                            />
                        </div>
                        <div class="flex items-end">
                            <div class="flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    id="is_active"
                                    class="mr-2"
                                />
                                <label for="is_active" class="text-sm text-gray-700">Aktif</label>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea
                            v-model="form.address"
                            class="w-full border rounded-lg px-3 py-2"
                            rows="2"
                        ></textarea>
                    </div>

                    <!-- Product Templates Section -->
                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-medium text-gray-800">Template Produk</h4>
                            <button
                                type="button"
                                @click="addTemplate"
                                class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded-lg hover:bg-green-200"
                            >
                                + Tambah Template
                            </button>
                        </div>
                        
                        <div v-if="form.product_templates.length === 0" class="text-center py-4 text-gray-500 text-sm">
                            Belum ada template produk
                        </div>
                        
                        <div v-else class="space-y-3">
                            <div
                                v-for="(template, index) in form.product_templates"
                                :key="index"
                                class="bg-gray-50 rounded-lg p-3"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="flex-1 grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Nama Produk</label>
                                            <input
                                                v-model="template.name"
                                                type="text"
                                                class="w-full border rounded px-2 py-1 text-sm"
                                                placeholder="Contoh: Risoles"
                                                required
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Harga Beli</label>
                                            <div class="relative">
                                                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">Rp</span>
                                                <input
                                                    v-model="template.base_price"
                                                    type="number"
                                                    min="0"
                                                    class="w-full border rounded pl-7 pr-2 py-1 text-sm"
                                                    placeholder="0"
                                                    required
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Harga Jual</label>
                                            <div class="relative">
                                                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">Rp</span>
                                                <input
                                                    v-model="template.default_selling_price"
                                                    type="number"
                                                    min="0"
                                                    class="w-full border rounded pl-7 pr-2 py-1 text-sm"
                                                    placeholder="0"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeTemplate(index)"
                                        class="text-red-500 hover:text-red-700 mt-4"
                                    >
                                        ✕
                                    </button>
                                </div>
                                <div v-if="template.base_price && template.default_selling_price" class="mt-2 text-xs text-gray-500">
                                    Profit: {{ formatMoney(template.default_selling_price - template.base_price) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
