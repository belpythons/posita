<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
});

const openCreateModal = () => {
    editingPartner.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (partner) => {
    editingPartner.value = partner;
    form.name = partner.name;
    form.phone = partner.phone || '';
    form.address = partner.address || '';
    form.is_active = partner.is_active;
    showModal.value = true;
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alamat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="partner in partners" :key="partner.id">
                        <td class="px-6 py-4 font-medium">{{ partner.name }}</td>
                        <td class="px-6 py-4">{{ partner.phone || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ partner.address || '-' }}</td>
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
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold">
                        {{ editingPartner ? 'Edit Partner' : 'Tambah Partner' }}
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea
                            v-model="form.address"
                            class="w-full border rounded-lg px-3 py-2"
                            rows="2"
                        ></textarea>
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
