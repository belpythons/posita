<script setup>
import EmployeeLayout from "@/Layouts/EmployeeLayout.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { formatMoney } from "@/utils/formatMoney";

const props = defineProps({
    selectedTemplate: { type: Object, default: null },
    boxTemplates: { type: Array, default: () => [] },
});

const selectedTemplateId = ref("");

const form = useForm({
    customer_name: "",
    box_template_id: null,
    pickup_datetime: "",
    quantity: 1,
    items: [],
});

watch(selectedTemplateId, (newId) => {
    form.box_template_id = newId || null;
    if (!newId) return;

    const template = props.boxTemplates.find((t) => t.id === parseInt(newId));
    if (template && template.items_json) {
        form.items = [];
        const itemNames = Array.isArray(template.items_json)
            ? template.items_json
            : JSON.parse(template.items_json);

        itemNames.forEach((itemName) => {
            form.items.push({
                product_name: itemName,
                quantity: 1,
                unit_price: Math.round(template.price / itemNames.length),
            });
        });
    }
});

const addItem = () => {
    form.items.push({ product_name: "", quantity: 1, unit_price: 0 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const itemsSubtotal = computed(() => {
    return form.items.reduce(
        (sum, item) => sum + item.quantity * (item.unit_price || 0),
        0
    );
});

const calculatedTotal = computed(
    () => itemsSubtotal.value * (form.quantity || 1)
);

const minDateTime = computed(() => {
    const now = new Date();
    now.setHours(now.getHours() + 1);
    return now.toISOString().slice(0, 16);
});

const submit = () => {
    form.post("/pos/box", {
        onSuccess: () => {
            form.reset();
            selectedTemplateId.value = "";
        },
    });
};

const clearTemplate = () => {
    selectedTemplateId.value = "";
    form.box_template_id = null;
    form.items = [];
    form.quantity = 1;
    addItem();
};

if (form.items.length === 0) addItem();
</script>

<template>
    <Head title="Buat Order Box" />

    <EmployeeLayout>
        <template #header>
            <h2 class="text-xl font-bold text-gray-800 tracking-tight">
                Buat Pesanan Box Baru
            </h2>
        </template>

        <div class="max-w-4xl mx-auto py-8 px-4">
            <form
                @submit.prevent="submit"
                class="grid grid-cols-1 lg:grid-cols-3 gap-8"
            >
                <div class="lg:col-span-2 space-y-6">
                    <section
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6"
                    >
                        <h3
                            class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-6"
                        >
                            Informasi Dasar
                        </h3>

                        <div class="space-y-5">
                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 mb-2"
                                    >Pilih Template (Opsional)</label
                                >
                                <div class="relative group">
                                    <select
                                        v-model="selectedTemplateId"
                                        class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500/20 transition-all appearance-none"
                                    >
                                        <option value="">
                                            Input Manual / Kosongkan
                                        </option>
                                        <optgroup label="Heavy Meal">
                                            <option
                                                v-for="template in boxTemplates.filter(
                                                    (t) =>
                                                        t.type === 'heavy_meal'
                                                )"
                                                :key="template.id"
                                                :value="template.id"
                                            >
                                                {{ template.name }}
                                            </option>
                                        </optgroup>
                                        <optgroup label="Snack Box">
                                            <option
                                                v-for="template in boxTemplates.filter(
                                                    (t) =>
                                                        t.type === 'snack_box'
                                                )"
                                                :key="template.id"
                                                :value="template.id"
                                            >
                                                {{ template.name }}
                                            </option>
                                        </optgroup>
                                    </select>
                                    <button
                                        v-if="selectedTemplateId"
                                        type="button"
                                        @click="clearTemplate"
                                        class="absolute right-3 top-2.5 text-gray-400 hover:text-red-500"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 mb-2"
                                        >Nama Pelanggan</label
                                    >
                                    <input
                                        v-model="form.customer_name"
                                        type="text"
                                        placeholder="Masukkan nama..."
                                        class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500/20"
                                        required
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-gray-700 mb-2"
                                        >Waktu Pengambilan</label
                                    >
                                    <input
                                        v-model="form.pickup_datetime"
                                        type="datetime-local"
                                        :min="minDateTime"
                                        class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500/20"
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6"
                    >
                        <div class="flex justify-between items-center mb-6">
                            <h3
                                class="text-sm font-bold text-gray-400 uppercase tracking-widest"
                            >
                                Detail Item (Per Box)
                            </h3>
                            <button
                                type="button"
                                @click="addItem"
                                class="text-sm font-bold text-blue-600 hover:text-blue-700"
                            >
                                + Tambah Menu
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="flex gap-3 items-end group bg-gray-50/50 p-3 rounded-xl border border-transparent hover:border-gray-200 transition-all"
                            >
                                <div class="flex-1">
                                    <label
                                        class="text-[10px] font-bold text-gray-400 uppercase mb-1 block"
                                        >Menu</label
                                    >
                                    <input
                                        v-model="item.product_name"
                                        type="text"
                                        class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/10"
                                        placeholder="..."
                                        required
                                    />
                                </div>
                                <div class="w-20">
                                    <label
                                        class="text-[10px] font-bold text-gray-400 uppercase mb-1 block text-center"
                                        >Qty</label
                                    >
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-center focus:ring-2 focus:ring-blue-500/10"
                                        required
                                    />
                                </div>
                                <div class="w-32">
                                    <label
                                        class="text-[10px] font-bold text-gray-400 uppercase mb-1 block text-right"
                                        >Harga</label
                                    >
                                    <input
                                        v-model.number="item.unit_price"
                                        type="number"
                                        class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-right focus:ring-2 focus:ring-blue-500/10"
                                        required
                                    />
                                </div>
                                <button
                                    v-if="form.items.length > 1"
                                    type="button"
                                    @click="removeItem(index)"
                                    class="p-2 text-gray-300 hover:text-red-500 transition-colors"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24"
                    >
                        <h3
                            class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-6"
                        >
                            Ringkasan Biaya
                        </h3>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500 font-medium"
                                    >Jumlah Pesanan</span
                                >
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        @click="
                                            form.quantity = Math.max(
                                                1,
                                                form.quantity - 1
                                            )
                                        "
                                        class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center hover:bg-gray-50"
                                    >
                                        -
                                    </button>
                                    <span
                                        class="text-sm font-bold w-6 text-center"
                                        >{{ form.quantity }}</span
                                    >
                                    <button
                                        type="button"
                                        @click="form.quantity++"
                                        class="w-8 h-8 rounded-lg border border-gray-200 flex items-center justify-center hover:bg-gray-50"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>

                            <div
                                class="pt-4 border-t border-dashed border-gray-200 space-y-2"
                            >
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500"
                                        >Subtotal per Box</span
                                    >
                                    <span class="font-semibold text-gray-700">{{
                                        formatMoney(itemsSubtotal)
                                    }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500"
                                        >Total Unit</span
                                    >
                                    <span class="font-semibold text-gray-700"
                                        >x{{ form.quantity }}</span
                                    >
                                </div>
                            </div>

                            <div
                                class="pt-4 border-t border-gray-100 flex justify-between items-end"
                            >
                                <span
                                    class="text-xs font-bold text-gray-400 uppercase"
                                    >Total Akhir</span
                                >
                                <span
                                    class="text-2xl font-black text-blue-600"
                                    >{{ formatMoney(calculatedTotal) }}</span
                                >
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all disabled:opacity-50 mt-4"
                            >
                                {{
                                    form.processing
                                        ? "Memproses..."
                                        : "Simpan Pesanan"
                                }}
                            </button>

                            <Link
                                href="/pos/box"
                                class="block w-full text-center text-sm font-bold text-gray-400 hover:text-gray-600 py-2"
                            >
                                Batalkan
                            </Link>
                        </div>
                    </section>
                </div>
            </form>
        </div>
    </EmployeeLayout>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type="number"] {
    -moz-appearance: textfield;
    appearance: textfield;
}
</style>
