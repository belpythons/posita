<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    templates: {
        type: Array,
        default: () => [],
    },
    heavyMealTemplates: {
        type: Array,
        default: () => [],
    },
    snackBoxTemplates: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const editingTemplate = ref(null);
const newItem = ref('');

const form = useForm({
    name: '',
    type: 'heavy_meal',
    price: '',
    items_json: [],
    is_active: true,
});

const openCreateModal = () => {
    editingTemplate.value = null;
    form.reset();
    form.items_json = [];
    showModal.value = true;
};

const openEditModal = (template) => {
    editingTemplate.value = template;
    form.name = template.name;
    form.type = template.type;
    form.price = template.price;
    form.items_json = [...(template.items_json || [])];
    form.is_active = template.is_active;
    showModal.value = true;
};

const addItem = () => {
    if (newItem.value.trim()) {
        form.items_json.push(newItem.value.trim());
        newItem.value = '';
    }
};

const removeItem = (index) => {
    form.items_json.splice(index, 1);
};

const submit = () => {
    if (editingTemplate.value) {
        form.put(`/admin/box-templates/${editingTemplate.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/admin/box-templates', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteTemplate = (template) => {
    if (confirm(`Hapus template "${template.name}"?`)) {
        useForm({}).delete(`/admin/box-templates/${template.id}`);
    }
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
    <Head title="Box Templates" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Box Templates</h2>
        </template>

        <!-- Header Actions -->
        <div class="flex justify-between items-center mb-6">
            <p class="text-gray-600">Total: {{ templates.length }} template</p>
            <button
                @click="openCreateModal"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
            >
                + Tambah Template
            </button>
        </div>

        <!-- Heavy Meal Templates -->
        <div class="mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">🍱 Makan Berat</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="template in heavyMealTemplates"
                    :key="template.id"
                    class="bg-white rounded-lg shadow p-4"
                >
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="font-medium">{{ template.name }}</h4>
                        <span
                            :class="[
                                'px-2 py-1 text-xs rounded-full',
                                template.is_active
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-red-100 text-red-800'
                            ]"
                        >
                            {{ template.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <p class="text-lg font-bold text-green-600 mb-2">
                        {{ formatCurrency(template.price) }}
                    </p>
                    <div class="text-sm text-gray-600 mb-3">
                        <p class="font-medium mb-1">Isi:</p>
                        <ul class="list-disc list-inside">
                            <li v-for="(item, idx) in template.items_json" :key="idx">{{ item }}</li>
                        </ul>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="openEditModal(template)"
                            class="text-sm text-blue-600 hover:text-blue-800"
                        >
                            Edit
                        </button>
                        <button
                            @click="deleteTemplate(template)"
                            class="text-sm text-red-600 hover:text-red-800"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
            <p v-if="heavyMealTemplates.length === 0" class="text-gray-500 text-center py-8">
                Belum ada template makan berat
            </p>
        </div>

        <!-- Snack Box Templates -->
        <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">🍪 Snack Box</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="template in snackBoxTemplates"
                    :key="template.id"
                    class="bg-white rounded-lg shadow p-4"
                >
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="font-medium">{{ template.name }}</h4>
                        <span
                            :class="[
                                'px-2 py-1 text-xs rounded-full',
                                template.is_active
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-red-100 text-red-800'
                            ]"
                        >
                            {{ template.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <p class="text-lg font-bold text-green-600 mb-2">
                        {{ formatCurrency(template.price) }}
                    </p>
                    <div class="text-sm text-gray-600 mb-3">
                        <p class="font-medium mb-1">Isi:</p>
                        <ul class="list-disc list-inside">
                            <li v-for="(item, idx) in template.items_json" :key="idx">{{ item }}</li>
                        </ul>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="openEditModal(template)"
                            class="text-sm text-blue-600 hover:text-blue-800"
                        >
                            Edit
                        </button>
                        <button
                            @click="deleteTemplate(template)"
                            class="text-sm text-red-600 hover:text-red-800"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
            <p v-if="snackBoxTemplates.length === 0" class="text-gray-500 text-center py-8">
                Belum ada template snack box
            </p>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold">
                        {{ editingTemplate ? 'Edit Template' : 'Tambah Template' }}
                    </h3>
                </div>
                <form @submit.prevent="submit" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full border rounded-lg px-3 py-2"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
                        <select v-model="form.type" class="w-full border rounded-lg px-3 py-2">
                            <option value="heavy_meal">Makan Berat</option>
                            <option value="snack_box">Snack Box</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Harga</label>
                        <input
                            v-model="form.price"
                            type="number"
                            min="0"
                            class="w-full border rounded-lg px-3 py-2"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Isi Box</label>
                        <div class="flex gap-2 mb-2">
                            <input
                                v-model="newItem"
                                type="text"
                                class="flex-1 border rounded-lg px-3 py-2"
                                placeholder="Tambah item..."
                                @keyup.enter.prevent="addItem"
                            />
                            <button
                                type="button"
                                @click="addItem"
                                class="bg-gray-200 px-3 py-2 rounded-lg hover:bg-gray-300"
                            >
                                +
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(item, idx) in form.items_json"
                                :key="idx"
                                class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm flex items-center gap-1"
                            >
                                {{ item }}
                                <button type="button" @click="removeItem(idx)" class="text-blue-600 hover:text-blue-800">×</button>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            id="is_active"
                            class="mr-2"
                        />
                        <label for="is_active" class="text-sm text-gray-700">Aktif</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
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
