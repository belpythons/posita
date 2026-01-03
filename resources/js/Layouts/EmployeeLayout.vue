<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { useTheme } from '@/composables/useTheme'
import { Sonner, DropdownMenu, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuLabel } from '@/Components/ui'
import CommandMenu from '@/Components/CommandMenu.vue'
import {
  Store,
  Package,
  ShoppingBag,
  Lock,
  LogOut,
  User,
  ChevronDown,
  Sun,
  Moon,
  PanelRightClose,
  PanelRightOpen
} from 'lucide-vue-next'

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
const { theme, toggleTheme } = useTheme()

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

// Force light mode for Employee
onMounted(() => {
  document.documentElement.classList.remove('dark', 'theme-neutral')
})

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
  <div :class="[
    'min-h-screen bg-background pb-24 lg:pb-0 transition-all duration-300 ease-in-out',
    isSidebarCollapsed ? 'lg:pr-16' : 'lg:pr-60'
  ]">
    <!-- Toast Container -->
    <Sonner />

    <!-- Command Palette (CMD+K) -->
    <CommandMenu persona="employee" />

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-card shadow-sm border-b border-border">
      <div class="flex items-center justify-between h-14 px-4 lg:px-6">
        <!-- Logo / Brand -->
        <div class="flex items-center space-x-3">
          <h1 class="text-lg font-bold text-foreground">
            <span class="text-primary">Posita</span> POS
          </h1>
        </div>

        <!-- Session Status Badge + User Menu -->
        <div class="flex items-center space-x-4">
          <!-- Session Info (Desktop) -->
          <div class="hidden sm:flex items-center space-x-3">
            <div
              :class="[
                'flex items-center space-x-2 px-3 py-1.5 rounded-full text-xs font-medium transition-colors',
                session?.isActive
                  ? 'bg-emerald-100 text-emerald-700'
                  : 'bg-muted text-muted-foreground'
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
              <button class="flex items-center space-x-2 p-2 rounded-lg hover:bg-muted transition-colors">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary/70 flex items-center justify-center text-primary-foreground font-semibold text-sm">
                  {{ user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                </div>
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

            <!-- Theme Toggle -->
            <DropdownMenuItem @click="toggleTheme">
              <Sun v-if="theme === 'admin'" class="w-4 h-4 mr-2" />
              <Moon v-else class="w-4 h-4 mr-2" />
              {{ theme === 'employee' ? 'Admin Theme' : 'Employee Theme' }}
            </DropdownMenuItem>

            <DropdownMenuSeparator />

            <DropdownMenuItem as-child destructive>
              <Link href="/logout" method="post" as="button" class="flex items-center w-full">
                <LogOut class="w-4 h-4 mr-2" />
                Logout
              </Link>
            </DropdownMenuItem>
          </DropdownMenu>
        </div>
      </div>

      <!-- Mobile Session Info Bar -->
      <div class="sm:hidden border-t border-border px-4 py-2 bg-muted/50">
        <div class="flex items-center justify-between">
          <div
            :class="[
              'flex items-center space-x-2 px-2.5 py-1 rounded-full text-xs font-medium',
              session?.isActive
                ? 'bg-emerald-100 text-emerald-700'
                : 'bg-muted text-muted-foreground'
            ]"
          >
            <span
              :class="[
                'w-1.5 h-1.5 rounded-full',
                session?.isActive ? 'bg-emerald-500 animate-pulse-subtle' : 'bg-muted-foreground'
              ]"
            />
            <span>{{ session?.isActive ? 'Aktif' : 'Tutup' }}</span>
          </div>
          <div v-if="session?.openingBalance" class="text-xs text-muted-foreground">
            Saldo: <span class="font-semibold">{{ formatCurrency(session.openingBalance) }}</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Page Title Header -->
    <div v-if="$slots.header" class="bg-card border-b border-border px-4 lg:px-6 py-4">
      <slot name="header" />
    </div>

    <!-- Main Content -->
    <main class="p-4 lg:p-6 lg:max-w-5xl">
      <slot />
    </main>

    <!-- Desktop Right Sidebar Navigation -->
    <aside :class="[
      'hidden lg:fixed lg:inset-y-0 lg:right-0 lg:flex lg:flex-col lg:bg-card lg:border-l lg:border-border transition-all duration-300 ease-in-out',
      isSidebarCollapsed ? 'lg:w-16' : 'lg:w-56'
    ]">
      <!-- Collapse Toggle -->
      <button
        @click="toggleSidebar"
        class="absolute -left-3 top-20 w-6 h-6 rounded-full bg-primary text-primary-foreground flex items-center justify-center shadow-lg hover:bg-primary/90 transition-colors z-50"
        title="Toggle sidebar"
      >
        <PanelRightOpen v-if="isSidebarCollapsed" class="w-3.5 h-3.5" />
        <PanelRightClose v-else class="w-3.5 h-3.5" />
      </button>

      <!-- Sidebar Header -->
      <div class="h-14 flex items-center px-4 border-b border-border">
        <Transition
          enter-active-class="transition-opacity duration-200"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition-opacity duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
          mode="out-in"
        >
          <h2 v-if="!isSidebarCollapsed" class="text-sm font-semibold text-muted-foreground">Navigation</h2>
          <span v-else class="text-primary font-bold">P</span>
        </Transition>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 px-2 py-4 space-y-1">
        <Link
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          :class="[
            'group flex items-center rounded-lg text-sm font-medium transition-all duration-200',
            isSidebarCollapsed ? 'justify-center p-2.5' : 'px-3 py-2.5',
            isActive(item.href)
              ? 'bg-primary text-primary-foreground shadow-md'
              : 'text-muted-foreground hover:bg-muted hover:text-foreground'
          ]"
          :title="isSidebarCollapsed ? item.name : ''"
        >
          <component
            :is="item.icon"
            :class="[
              'w-5 h-5 flex-shrink-0',
              !isSidebarCollapsed && 'mr-3',
              isActive(item.href) ? 'text-primary-foreground' : 'text-muted-foreground group-hover:text-foreground'
            ]"
          />
          <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0 w-0"
            enter-to-class="opacity-100 w-auto"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100 w-auto"
            leave-to-class="opacity-0 w-0"
          >
            <span v-if="!isSidebarCollapsed" class="whitespace-nowrap overflow-hidden">{{ item.name }}</span>
          </Transition>
        </Link>
      </nav>

      <!-- Quick Actions -->
      <div class="border-t border-border p-2">
        <Link
          v-if="!session?.isActive"
          href="/pos/open"
          :class="[
            'flex items-center justify-center w-full bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-medium rounded-lg transition-colors shadow-sm',
            isSidebarCollapsed ? 'p-2.5' : 'px-4 py-2.5'
          ]"
          :title="isSidebarCollapsed ? 'Buka Toko' : ''"
        >
          <Store class="w-4 h-4" :class="!isSidebarCollapsed && 'mr-2'" />
          <span v-if="!isSidebarCollapsed">Buka Toko</span>
        </Link>
        <Link
          v-else
          href="/pos/close"
          :class="[
            'flex items-center justify-center w-full bg-destructive hover:bg-destructive/90 text-destructive-foreground text-sm font-medium rounded-lg transition-colors shadow-sm',
            isSidebarCollapsed ? 'p-2.5' : 'px-4 py-2.5'
          ]"
          :title="isSidebarCollapsed ? 'Tutup Toko' : ''"
        >
          <Lock class="w-4 h-4" :class="!isSidebarCollapsed && 'mr-2'" />
          <span v-if="!isSidebarCollapsed">Tutup Toko</span>
        </Link>
      </div>
    </aside>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="fixed bottom-0 left-0 right-0 bg-card border-t border-border shadow-nav z-50 lg:hidden pb-safe">
      <div class="flex justify-around items-stretch h-16">
        <Link
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          :class="[
            'flex flex-col items-center justify-center flex-1 py-2 transition-all duration-200',
            isActive(item.href)
              ? 'text-primary bg-primary/10'
              : 'text-muted-foreground hover:text-foreground active:bg-muted'
          ]"
        >
          <component
            :is="item.icon"
            :class="[
              'w-5 h-5 transition-transform duration-200',
              isActive(item.href) ? 'scale-110' : ''
            ]"
          />
          <span
            :class="[
              'text-[10px] mt-1 font-medium transition-all duration-200',
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
