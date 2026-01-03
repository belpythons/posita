<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import BaseButton from '@/Components/BaseButton.vue';
import StatsCard from '@/Components/StatsCard.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    currentSession: {
        type: Object,
        default: null
    },
    stats: {
        type: Object,
        default: () => ({
            todaySales: 0,
            itemsSold: 0,
            boxOrders: 0
        })
    }
});

const page = usePage();

// Get session info for layout
const sessionInfo = computed(() => ({
    isActive: !!props.currentSession,
    shiftName: props.currentSession?.shift_name || null,
    openingBalance: props.currentSession?.opening_balance || 0,
    openedAt: props.currentSession?.opened_at || null
}));
</script>

<template>
    <Head title="POS Dashboard" />

    <EmployeeLayout :session-info="sessionInfo">
        <template #header>
            <h2 class="text-lg font-semibold text-slate-800">
                POS Dashboard
            </h2>
        </template>

        <!-- Stats Section -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <StatsCard
                title="Penjualan Hari Ini"
                :value="stats?.todaySales || 0"
                prefix="Rp "
                :currency="true"
                variant="success"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>
            </StatsCard>

            <StatsCard
                title="Item Terjual"
                :value="stats?.itemsSold || 0"
                suffix=" pcs"
                variant="positive"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </template>
            </StatsCard>

            <StatsCard
                title="Box Orders"
                :value="stats?.boxOrders || 0"
                suffix=" order"
                variant="default"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </template>
            </StatsCard>
        </div>

        <!-- Quick Actions Grid -->
        <div class="bg-white rounded-xl shadow-card p-5 mb-6">
            <h3 class="text-base font-semibold text-slate-800 mb-4">Menu Cepat</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <Link 
                    v-if="!sessionInfo.isActive"
                    href="/pos/open"
                    class="flex flex-col items-center justify-center p-4 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-all group"
                >
                    <div class="w-12 h-12 rounded-full bg-emerald-500 text-white flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-emerald-700">Buka Toko</span>
                </Link>

                <Link 
                    href="/pos/consignment"
                    class="flex flex-col items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all group"
                >
                    <div class="w-12 h-12 rounded-full bg-blue-500 text-white flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-blue-700">Kelola Barang</span>
                </Link>

                <Link 
                    href="/pos/box"
                    class="flex flex-col items-center justify-center p-4 bg-amber-50 hover:bg-amber-100 rounded-xl transition-all group"
                >
                    <div class="w-12 h-12 rounded-full bg-amber-500 text-white flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-amber-700">Box Order</span>
                </Link>

                <Link 
                    v-if="sessionInfo.isActive"
                    href="/pos/close"
                    class="flex flex-col items-center justify-center p-4 bg-red-50 hover:bg-red-100 rounded-xl transition-all group"
                >
                    <div class="w-12 h-12 rounded-full bg-red-500 text-white flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-red-700">Tutup Toko</span>
                </Link>

                <Link 
                    v-else
                    href="/profile"
                    class="flex flex-col items-center justify-center p-4 bg-slate-50 hover:bg-slate-100 rounded-xl transition-all group"
                >
                    <div class="w-12 h-12 rounded-full bg-slate-500 text-white flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-700">Profil</span>
                </Link>
            </div>
        </div>

        <!-- Session Info Card (if active) -->
        <div v-if="sessionInfo.isActive" class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm">Sesi Aktif</p>
                    <p class="text-xl font-bold mt-1">{{ sessionInfo.shiftName || 'Shift Pagi' }}</p>
                    <p class="text-emerald-100 text-sm mt-2">
                        Saldo Awal: <span class="font-semibold text-white">Rp {{ new Intl.NumberFormat('id-ID').format(sessionInfo.openingBalance) }}</span>
                    </p>
                </div>
                <div class="text-right">
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions when no session -->
        <div v-else class="bg-slate-100 rounded-xl p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-700 mb-2">Belum Ada Sesi Aktif</h3>
            <p class="text-sm text-slate-500 mb-4">
                Silakan buka toko terlebih dahulu untuk memulai transaksi hari ini.
            </p>
            <Link href="/pos/open">
                <BaseButton variant="primary" size="lg">
                    <template #icon-left>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                    </template>
                    Buka Toko Sekarang
                </BaseButton>
            </Link>
        </div>
    </EmployeeLayout>
</template>
