<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    price: {
        type: Number,
        required: true,
    },
    stock: {
        type: Number,
        required: true,
    },
});

const formattedPrice = computed(() => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(props.price);
});
</script>

<template>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-100 transition-shadow hover:shadow-md">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 leading-tight">
                    {{ title }}
                </h3>
                <p v-if="subtitle" class="text-sm text-gray-500 mt-1">
                    {{ subtitle }}
                </p>
                <div class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Stock: {{ stock }}
                </div>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold text-gray-900">
                    {{ formattedPrice }}
                </p>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            <slot name="action" />
        </div>
    </div>
</template>
