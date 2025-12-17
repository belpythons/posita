<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    consignments: {
        type: Array,
        required: true,
    },
});

import CloseShopItem from './Partials/CloseShopItem.vue';
</script>

<template>
    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Close Shop - Reconciliation</h2>

        <div v-if="consignments.length === 0" class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        No open consignments found for today.
                    </p>
                </div>
            </div>
        </div>

        <div v-else class="space-y-4">
            <CloseShopItem 
                v-for="item in consignments" 
                :key="item.id" 
                :consignment="item" 
            />
        </div>
    </div>
</template>
