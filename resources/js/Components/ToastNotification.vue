<script setup>
import { computed, watch, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(false);

const toast = computed(() => page.props.toast);

watch(toast, (value) => {
    if (value) {
        visible.value = true;
        setTimeout(() => {
            visible.value = false;
        }, 3000);
    }
});

const variantClass = computed(() => {
    switch (toast.value?.type) {
        case 'success':
            return 'bg-green-600';
        case 'error':
            return 'bg-red-600';
        case 'warning':
            return 'bg-orange-500';
        default:
            return 'bg-blue-600';
    }
});
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible && toast"
            class="fixed bottom-6 right-6 z-50 max-w-sm w-full text-white px-4 py-3 rounded-lg shadow-lg"
            :class="variantClass"
        >
            <p class="text-sm font-medium">
                {{ toast.message }}
            </p>
        </div>
    </transition>
</template>
