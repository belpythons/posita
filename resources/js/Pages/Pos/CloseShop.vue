<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import axios from "axios";

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
});

const consignmentItems = ref([]);
const isLoading = ref(false);
const validationErrors = ref({});
const successMessage = ref("");

const form = useForm({
    actual_cash: 0,
    items: [],
});

const fetchConsignmentItems = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get("/api/consignments/daily", {
            params: {
                date: props.session.date,
                user_id: props.session.input_by_user_id,
            },
        });

        consignmentItems.value = response.data.map((item) => ({
            id: item.id,
            product_name: item.product_name,
            initial_stock: item.initial_stock,
            remaining_stock: item.remaining_stock || 0,
            selling_price: parseFloat(item.selling_price),
            base_price: parseFloat(item.base_price),
        }));

        updateFormItems();
    } catch (error) {
        console.error("Error fetching consignments:", error);
        validationErrors.value = { error: "Failed to load consignment items" };
    } finally {
        isLoading.value = false;
    }
};

const updateFormItems = () => {
    form.items = consignmentItems.value.map((item) => ({
        id: item.id,
        remaining_stock: item.remaining_stock,
    }));
};

const calculateItemProfit = (item) => {
    const quantitySold = item.initial_stock - item.remaining_stock;
    const profitPerUnit = item.selling_price - item.base_price;
    return quantitySold * profitPerUnit;
};

const totalProfit = computed(() => {
    return consignmentItems.value.reduce((sum, item) => {
        return sum + calculateItemProfit(item);
    }, 0);
});

const totalRevenue = computed(() => {
    return consignmentItems.value.reduce((sum, item) => {
        const quantitySold = item.initial_stock - item.remaining_stock;
        return sum + quantitySold * item.selling_price;
    }, 0);
});

const expectedCash = computed(() => {
    return parseFloat(props.session.start_cash) + totalRevenue.value;
});

const cashDiscrepancy = computed(() => {
    return form.actual_cash - expectedCash.value;
});

watch(
    () => consignmentItems.value,
    (newItems) => {
        validateItems();
        updateFormItems();
    },
    { deep: true }
);

const validateItems = () => {
    validationErrors.value = {};

    consignmentItems.value.forEach((item, index) => {
        if (item.remaining_stock < 0) {
            if (!validationErrors.value[`items.${index}`]) {
                validationErrors.value[`items.${index}`] = [];
            }
            validationErrors.value[`items.${index}`].push(
                "Remaining stock cannot be negative"
            );
        }

        if (item.remaining_stock > item.initial_stock) {
            if (!validationErrors.value[`items.${index}`]) {
                validationErrors.value[`items.${index}`] = [];
            }
            validationErrors.value[`items.${index}`].push(
                `Remaining stock cannot exceed initial stock (${item.initial_stock})`
            );
        }
    });
};

const submit = () => {
    if (!form.actual_cash || form.actual_cash < 0) {
        validationErrors.value.actual_cash = [
            "Actual cash is required and must be positive",
        ];
        return;
    }

    validateItems();

    if (Object.keys(validationErrors.value).length > 0) {
        return;
    }

    form.put(route("pos.updateClose", { dailyConsignment: props.session.id }), {
        onSuccess: (page) => {
            successMessage.value = "Shop closed successfully!";
            setTimeout(() => {
                window.location.href = route("pos.dashboard");
            }, 2000);
        },
        onError: (errors) => {
            validationErrors.value = errors;
        },
    });
};

fetchConsignmentItems();
</script>

<template>
    <div class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">
        <h2 class="text-3xl font-bold mb-8 text-gray-800">
            Close Shop - Reconciliation
        </h2>

        <div
            v-if="isLoading"
            class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6"
        >
            <p class="text-sm text-blue-700">Loading consignment items...</p>
        </div>

        <div
            v-if="successMessage"
            class="bg-green-50 border-l-4 border-green-400 p-4 mb-6"
        >
            <p class="text-sm text-green-700">{{ successMessage }}</p>
        </div>

        <div
            v-if="!isLoading && consignmentItems.length === 0"
            class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"
        >
            <p class="text-sm text-yellow-700">
                No open consignments found for today.
            </p>
        </div>

        <form
            v-if="!isLoading && consignmentItems.length > 0"
            @submit.prevent="submit"
            class="space-y-8"
        >
            <div
                class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500"
            >
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    Session Information
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Session Date</p>
                        <p class="text-lg font-semibold text-gray-800">
                            {{ new Date(session.date).toLocaleDateString() }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Start Cash</p>
                        <p class="text-lg font-semibold text-gray-800">
                            {{
                                Number(session.start_cash).toLocaleString(
                                    "id-ID",
                                    { style: "currency", currency: "IDR" }
                                )
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Status</p>
                        <p class="text-lg font-semibold text-blue-600">
                            {{ session.status }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-xl font-semibold text-gray-800">
                    Item Details
                </h3>

                <div
                    v-for="(item, index) in consignmentItems"
                    :key="item.id"
                    class="bg-white p-6 rounded-lg shadow-md border-l-4 border-gray-300"
                    :class="{
                        'border-red-500': validationErrors[`items.${index}`],
                    }"
                >
                    <div class="mb-4">
                        <h4 class="text-lg font-semibold text-gray-800">
                            {{ item.product_name }}
                        </h4>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Initial Stock</label
                            >
                            <input
                                type="number"
                                :value="item.initial_stock"
                                disabled
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Remaining Stock</label
                            >
                            <input
                                type="number"
                                v-model.number="item.remaining_stock"
                                min="0"
                                :max="item.initial_stock"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="{
                                    'border-red-500':
                                        validationErrors[`items.${index}`],
                                }"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Base Price</label
                            >
                            <input
                                type="number"
                                :value="item.base_price"
                                disabled
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Selling Price</label
                            >
                            <input
                                type="number"
                                :value="item.selling_price"
                                disabled
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-700"
                            />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-gray-200"
                    >
                        <div>
                            <p class="text-sm text-gray-600">Quantity Sold</p>
                            <p class="text-lg font-semibold text-gray-800">
                                {{ item.initial_stock - item.remaining_stock }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Revenue</p>
                            <p class="text-lg font-semibold text-blue-600">
                                {{
                                    (
                                        (item.initial_stock -
                                            item.remaining_stock) *
                                        item.selling_price
                                    ).toLocaleString("id-ID", {
                                        style: "currency",
                                        currency: "IDR",
                                    })
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Profit</p>
                            <p class="text-lg font-semibold text-green-600">
                                {{
                                    calculateItemProfit(item).toLocaleString(
                                        "id-ID",
                                        { style: "currency", currency: "IDR" }
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="validationErrors[`items.${index}`]"
                        class="mt-3 text-sm text-red-600"
                    >
                        <ul>
                            <li
                                v-for="(error, i) in validationErrors[
                                    `items.${index}`
                                ]"
                                :key="i"
                                class="flex items-center"
                            >
                                <span class="mr-2">•</span>
                                {{ error }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div
                class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500"
            >
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    Cash Reconciliation
                </h3>

                <div class="space-y-4">
                    <div
                        class="flex justify-between items-center p-3 bg-gray-50 rounded"
                    >
                        <span class="text-gray-700 font-medium"
                            >Expected Cash</span
                        >
                        <span class="text-lg font-semibold text-gray-800">{{
                            expectedCash.toLocaleString("id-ID", {
                                style: "currency",
                                currency: "IDR",
                            })
                        }}</span>
                    </div>

                    <div>
                        <label
                            for="actual_cash"
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Actual Cash Counted</label
                        >
                        <input
                            id="actual_cash"
                            v-model.number="form.actual_cash"
                            type="number"
                            step="0.01"
                            min="0"
                            class="block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{
                                'border-red-500': validationErrors.actual_cash,
                            }"
                            placeholder="Enter actual cash amount"
                        />
                        <div
                            v-if="validationErrors.actual_cash"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ validationErrors.actual_cash }}
                        </div>
                    </div>

                    <div
                        class="flex justify-between items-center p-3 rounded"
                        :class="
                            cashDiscrepancy >= 0 ? 'bg-green-50' : 'bg-red-50'
                        "
                    >
                        <span class="text-gray-700 font-medium"
                            >Cash Discrepancy</span
                        >
                        <span
                            :class="
                                cashDiscrepancy >= 0
                                    ? 'text-green-600'
                                    : 'text-red-600'
                            "
                            class="text-lg font-semibold"
                        >
                            {{ cashDiscrepancy >= 0 ? "+" : ""
                            }}{{
                                cashDiscrepancy.toLocaleString("id-ID", {
                                    style: "currency",
                                    currency: "IDR",
                                })
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <div
                class="bg-blue-50 p-6 rounded-lg shadow-md border-l-4 border-blue-500"
            >
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    Daily Summary
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white p-4 rounded">
                        <p class="text-sm text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-bold text-blue-600">
                            {{
                                totalRevenue.toLocaleString("id-ID", {
                                    style: "currency",
                                    currency: "IDR",
                                })
                            }}
                        </p>
                    </div>
                    <div class="bg-white p-4 rounded">
                        <p class="text-sm text-gray-600">Total Profit</p>
                        <p class="text-2xl font-bold text-green-600">
                            {{
                                totalProfit.toLocaleString("id-ID", {
                                    style: "currency",
                                    currency: "IDR",
                                })
                            }}
                        </p>
                    </div>
                    <div class="bg-white p-4 rounded">
                        <p class="text-sm text-gray-600">Profit Margin</p>
                        <p class="text-2xl font-bold text-purple-600">
                            {{
                                totalRevenue > 0
                                    ? (
                                          (totalProfit / totalRevenue) *
                                          100
                                      ).toFixed(2)
                                    : 0
                            }}%
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="validationErrors.error"
                class="bg-red-50 border-l-4 border-red-400 p-4 mb-6"
            >
                <p class="text-sm text-red-700">{{ validationErrors.error }}</p>
            </div>

            <div class="flex justify-end gap-4">
                <a
                    href="javascript:history.back()"
                    class="px-6 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 font-medium transition"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 font-medium transition"
                >
                    {{
                        form.processing
                            ? "Submitting..."
                            : "Close Shop & Calculate Profit"
                    }}
                </button>
            </div>
        </form>
    </div>
</template>
