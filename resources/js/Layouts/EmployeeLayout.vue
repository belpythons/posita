<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);

const navigation = [
    { name: 'Buka Toko', href: '/pos/open', icon: '🏪' },
    { name: 'Barang', href: '/pos/consignment', icon: '📦' },
    { name: 'Tutup Toko', href: '/pos/close', icon: '🔒' },
    { name: 'Box Order', href: '/pos/box', icon: '🍱' },
];

const isActive = (href) => {
    return window.location.pathname === href;
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 pb-20">
        <!-- Top Header -->
        <header class="bg-white shadow-sm sticky top-0 z-40">
            <div class="flex items-center justify-between h-14 px-4">
                <h1 class="text-lg font-semibold text-gray-800">Posita POS</h1>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-600">{{ user?.name }}</span>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="text-sm text-red-500 hover:text-red-700"
                    >
                        Logout
                    </Link>
                </div>
            </div>
        </header>

        <!-- Page Title -->
        <div class="bg-white border-b px-4 py-3">
            <slot name="header" />
        </div>

        <!-- Main Content -->
        <main class="p-4">
            <slot />
        </main>

        <!-- Bottom Navigation (Mobile) -->
        <nav class="fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-50">
            <div class="flex justify-around items-center h-16">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex flex-col items-center justify-center flex-1 h-full transition-colors',
                        isActive(item.href)
                            ? 'text-blue-600 bg-blue-50'
                            : 'text-gray-500 hover:text-gray-700'
                    ]"
                >
                    <span class="text-xl">{{ item.icon }}</span>
                    <span class="text-xs mt-1">{{ item.name }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>
