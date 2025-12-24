<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);

const navigation = [
    { name: 'Dashboard', href: '/admin', icon: 'dashboard' },
    { name: 'Partners', href: '/admin/partners', icon: 'people' },
    { name: 'Box Templates', href: '/admin/box-templates', icon: 'inventory' },
    { name: 'Users', href: '/admin/users', icon: 'person' },
];

const isSidebarOpen = ref(true);

const isActive = (href) => {
    return window.location.pathname.startsWith(href);
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 transition-transform duration-300',
                isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Logo -->
            <div class="flex h-16 items-center justify-center border-b border-gray-700">
                <h1 class="text-xl font-bold text-white">Posita Admin</h1>
            </div>

            <!-- Navigation -->
            <nav class="mt-5 px-2">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'group flex items-center px-4 py-3 text-sm font-medium rounded-md mb-1 transition-colors',
                        isActive(item.href)
                            ? 'bg-gray-900 text-white'
                            : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                    ]"
                >
                    <span class="mr-3">{{ item.icon }}</span>
                    {{ item.name }}
                </Link>
            </nav>

            <!-- User Info -->
            <div class="absolute bottom-0 left-0 right-0 border-t border-gray-700 p-4">
                <div class="flex items-center">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">
                            {{ user?.name }}
                        </p>
                        <p class="text-xs text-gray-400 truncate">
                            {{ user?.email }}
                        </p>
                    </div>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="text-gray-400 hover:text-white text-sm"
                    >
                        Logout
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div :class="['transition-all duration-300', isSidebarOpen ? 'ml-64' : 'ml-0']">
            <!-- Top Bar -->
            <header class="bg-white shadow h-16 flex items-center px-6">
                <button
                    @click="isSidebarOpen = !isSidebarOpen"
                    class="text-gray-500 hover:text-gray-700"
                >
                    ☰
                </button>
                <div class="ml-4">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
