<!--
  Created/Modified by: Belva Pranama Sriwibowo
  NIM: 202312066
  Feature: Core & Admin - Layout utama halaman employee/kasir
-->
<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { Sonner, DropdownMenu, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuLabel } from '@/Components/ui'
import CommandMenu from '@/Components/CommandMenu.vue'
import { useTheme } from '@/composables/useTheme'
import {
  Store,
  Package,
  ShoppingBag,
  Lock,
  LogOut,
  User,
  ChevronDown,
  PanelRightClose,
  PanelRightOpen,
  Sun,
  Moon
} from 'lucide-vue-next'

const { toggleTheme, isDark } = useTheme()

/**
 * @typedef {Object} SessionInfo
 * @property {string} [shiftName] - Current shift name
 * @property {number} [openingBalance] - Opening balance amount
 * @property {boolean} [isActive] - Whether a session is active
 * @property {string} [openedAt] - Session open time
 */

const props = defineProps({
  /** @type {SessionInfo} */
  sessionInfo: {
    type: Object,
    default: () => ({
      shiftName: null,
      openingBalance: 0,
      isActive: false,
      openedAt: null
    })
  }
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

// Try to get session info from page props if not passed directly
const session = computed(() => {
  return props.sessionInfo?.shiftName
    ? props.sessionInfo
    : page.props.currentSession || props.sessionInfo
})

// Navigation items with Lucide icons
const navigation = [
  { name: 'Buka Toko', href: '/pos/open', icon: Store },
  { name: 'Barang', href: '/pos/consignment', icon: Package },
  { name: 'Box Order', href: '/pos/box', icon: ShoppingBag },
  { name: 'Tutup', href: '/pos/close', icon: Lock },
]

// Flash message watcher -> triggers sonner.toast()
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      toast.success(flash.success)
    }
    if (flash?.error) {
      toast.error(flash.error)
    }
    if (flash?.warning) {
      toast.warning(flash.warning)
    }
    if (flash?.info) {
      toast.info(flash.info)
    }
  },
  { deep: true, immediate: true }
)

// Theme is now initialized once in app.js - no need to force here

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value || 0)
}

const isActive = (href) => {
  return window.location.pathname === href
}

// Sidebar collapse state (persisted)
const isSidebarCollapsed = ref(localStorage.getItem('employee-sidebar-collapsed') === 'true')

const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
  localStorage.setItem('employee-sidebar-collapsed', String(isSidebarCollapsed.value))
}
</script>

<template>
  <div class="h-screen w-full bg-background flex flex-col overflow-hidden transition-all duration-300 ease-in-out">
    <!-- Toast Container -->
    <Sonner />

    <!-- Command Palette (CMD+K) -->
    <CommandMenu persona="employee" />

    <!-- Top Navigation Bar (Fixed Height) -->
    <header class="flex-none z-40 bg-card shadow-sm border-b border-border">
      <div class="flex items-center justify-between h-16 px-4 lg:px-6">
        <!-- Logo / Brand -->
        <div class="flex items-center space-x-3">
          <h1 class="text-xl font-bold tracking-tight text-foreground">
            <span class="text-gradient-primary uppercase tracking-wider">Posita</span> POS
          </h1>
        </div>

        <!-- Session Status Badge + User Menu -->
        <div class="flex items-center space-x-4">
          <!-- Session Info (Desktop) -->
          <div class="hidden sm:flex items-center space-x-3">
            <div
              :class="[
                'flex items-center space-x-2 px-3 py-1.5 rounded-full text-xs font-medium transition-colors border',
                session?.isActive
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-muted text-muted-foreground border-border'
              ]"
            >
              <span
                :class="[
                  'w-2 h-2 rounded-full',
                  session?.isActive ? 'bg-emerald-500 animate-pulse-subtle' : 'bg-muted-foreground'
                ]"
              />
              <span>{{ session?.isActive ? 'Sesi Aktif' : 'Tidak Ada Sesi' }}</span>
            </div>

            <div v-if="session?.openingBalance" class="text-sm text-muted-foreground">
              Saldo: <span class="font-semibold text-foreground">{{ formatCurrency(session.openingBalance) }}</span>
            </div>
          </div>

          <!-- User Dropdown -->
          <DropdownMenu>
            <template #trigger>
              <button class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-muted transition-colors border border-transparent hover:border-border">
                <span class="hidden sm:block text-sm font-medium text-foreground">{{ user?.name }}</span>
                <ChevronDown class="w-4 h-4 text-muted-foreground" />
              </button>
            </template>

            <DropdownMenuLabel class="font-normal">
              <div class="flex flex-col space-y-1">
                <p class="text-sm font-medium leading-none">{{ user?.name }}</p>
                <p class="text-xs leading-none text-muted-foreground">{{ user?.email }}</p>
              </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <DropdownMenuItem @click="toggleTheme">
              <component :is="isDark ? Sun : Moon" class="w-4 h-4 mr-2" />
              <span>Switch to {{ isDark ? 'Light' : 'Dark' }} Theme</span>
            </DropdownMenuItem>

            <DropdownMenuItem as-child destructive>
              <Link href="/logout" method="post" as="button" class="flex items-center w-full">
                <LogOut class="w-4 h-4 mr-2" />
                Logout
              </Link>
            </DropdownMenuItem>
          </DropdownMenu>
        </div>
      </div>
    </header>

    <!-- Main Content Wrapper -->
    <div class="flex flex-1 overflow-hidden relative flex-row">
      <!-- Main Content Area -->
      <main 
        class="flex-1 overflow-y-auto overflow-x-hidden bg-muted/20 w-full min-w-0" 
      >
        <div class="h-full w-full">
            <slot />
        </div>
      </main>

      <!-- Desktop Right Sidebar Navigation (Fixed) -->
      <aside 
        :class="[
          'hidden lg:flex flex-col h-full bg-card border-l border-border z-30 transition-all duration-300 ease-in-out relative',
          isSidebarCollapsed ? 'w-16' : 'w-64'
        ]"
      >
        <!-- Collapse Toggle -->
        <button
          @click="toggleSidebar"
          class="absolute -left-3 top-6 w-6 h-6 rounded-full bg-card border border-border text-foreground flex items-center justify-center shadow-md hover:bg-muted transition-colors z-50 transform hover:scale-105"
          title="Toggle sidebar"
        >
          <PanelRightOpen v-if="isSidebarCollapsed" class="w-3.5 h-3.5" />
          <PanelRightClose v-else class="w-3.5 h-3.5" />
        </button>

        <!-- Sidebar Header / Brand / Space -->
        <div class="flex-none h-16 flex items-center px-4 border-b border-border bg-card/50 backdrop-blur-sm">
             <Transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
                mode="out-in"
              >
                <div v-if="!isSidebarCollapsed" class="flex flex-col">
                    <span class="text-xs font-semibold uppercase text-muted-foreground tracking-wider">Aplikasi</span>
                    <span class="font-medium text-foreground">Menu Utama</span>
                </div>
                <div v-else class="mx-auto">
                    <span class="text-xs font-bold text-muted-foreground">MENU</span>
                </div>
              </Transition>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-1.5">
          <Link
            v-for="item in navigation"
            :key="item.name"
            :href="item.href"
            :class="[
              'group flex items-center rounded-xl transition-all duration-200 relative overflow-hidden',
              isSidebarCollapsed ? 'justify-center p-3' : 'px-4 py-3',
              isActive(item.href)
                ? 'bg-primary/10 text-primary font-semibold'
                : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground'
            ]"
            :title="isSidebarCollapsed ? item.name : ''"
          >
            <!-- Active Indicator Line -->
            <div v-if="isActive(item.href) && !isSidebarCollapsed" class="absolute left-0 top-1/2 -translate-y-1/2 h-8 w-1 bg-primary rounded-r-full"></div>

            <component
              :is="item.icon"
              :class="[
                'w-5 h-5 flex-shrink-0 transition-colors',
                !isSidebarCollapsed && 'mr-3',
                isActive(item.href) ? 'text-primary' : 'text-muted-foreground group-hover:text-foreground'
              ]"
            />
            <Transition
              enter-active-class="transition-all duration-200"
              enter-from-class="opacity-0 w-0 translate-x-[-10px]"
              enter-to-class="opacity-100 w-auto translate-x-0"
              leave-active-class="transition-all duration-200"
              leave-from-class="opacity-100 w-auto"
              leave-to-class="opacity-0 w-0"
            >
              <span v-if="!isSidebarCollapsed" class="whitespace-nowrap">{{ item.name }}</span>
            </Transition>
          </Link>
        </nav>

        <!-- Quick Actions Footer -->
        <div class="flex-none p-3 border-t border-border bg-muted/10">
          <Link
            v-if="!session?.isActive"
            href="/pos/open"
            :class="[
              'flex items-center justify-center w-full bg-accent hover:bg-accent/90 text-accent-foreground text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-0.5',
              isSidebarCollapsed ? 'p-3' : 'px-4 py-3'
            ]"
            :title="isSidebarCollapsed ? 'Buka Toko' : ''"
          >
            <Store class="w-5 h-5" :class="!isSidebarCollapsed && 'mr-2'" />
            <span v-if="!isSidebarCollapsed">Buka Toko</span>
          </Link>
          <Link
            v-else
            href="/pos/close"
            :class="[
              'flex items-center justify-center w-full bg-destructive/10 hover:bg-destructive text-destructive hover:text-destructive-foreground text-sm font-medium rounded-xl transition-all',
              isSidebarCollapsed ? 'p-3' : 'px-4 py-3'
            ]"
            :title="isSidebarCollapsed ? 'Tutup Toko' : ''"
          >
            <Lock class="w-5 h-5" :class="!isSidebarCollapsed && 'mr-2'" />
            <span v-if="!isSidebarCollapsed">Tutup Toko</span>
          </Link>
        </div>
      </aside>
    </div>

    <!-- Mobile Bottom Navigation Bar (Visible only on mobile) -->
    <nav class="flex-none bg-card border-t border-border shadow-nav z-50 lg:hidden pb-safe">
      <div class="flex justify-around items-center h-16">
        <Link
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          :class="[
            'flex flex-col items-center justify-center flex-1 py-1 transition-all duration-200 h-full',
            isActive(item.href)
              ? 'text-primary'
              : 'text-muted-foreground hover:text-foreground active:bg-muted/20'
          ]"
        >
          <div :class="[
            'p-1.5 rounded-full transition-all',
             isActive(item.href) ? 'bg-primary/10' : 'bg-transparent'
          ]">
              <component
                :is="item.icon"
                :class="[
                  'w-5 h-5 transition-transform duration-200',
                  isActive(item.href) ? 'scale-110' : ''
                ]"
              />
          </div>
          <span
            :class="[
              'text-[10px] mt-0.5 font-medium transition-all duration-200',
              isActive(item.href) ? 'text-primary' : 'text-muted-foreground'
            ]"
          >
            {{ item.name }}
          </span>
        </Link>
      </div>
    </nav>
  </div>
</template>
