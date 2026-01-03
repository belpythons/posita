<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { useTheme } from '@/composables/useTheme'
import { Sheet, DropdownMenu, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuLabel, Sonner } from '@/Components/ui'
import CommandMenu from '@/Components/CommandMenu.vue'
import {
  LayoutDashboard,
  Users,
  Package,
  UserCircle,
  Menu,
  X,
  LogOut,
  Settings,
  ChevronDown,
  Sun,
  Moon,
  PanelLeftClose,
  PanelLeft
} from 'lucide-vue-next'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const { theme, toggleTheme } = useTheme()

// Navigation items with Lucide icons
const navigation = [
  { name: 'Dashboard', href: '/admin', icon: LayoutDashboard },
  { name: 'Partners', href: '/admin/partners', icon: Users },
  { name: 'Box Templates', href: '/admin/box-templates', icon: Package },
  { name: 'Users', href: '/admin/users', icon: UserCircle },
]

const isSidebarOpen = ref(localStorage.getItem('admin-sidebar-open') !== 'false')
const isMobileSidebarOpen = ref(false)
const isProfileDropdownOpen = ref(false)
const isMobile = ref(false)

// Persist sidebar state
const toggleDesktopSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
  localStorage.setItem('admin-sidebar-open', String(isSidebarOpen.value))
}

const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024
  if (!isMobile.value) {
    isMobileSidebarOpen.value = false
  }
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  // Force dark mode for Admin
  document.documentElement.classList.remove('theme-neutral')
  document.documentElement.classList.add('dark')
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

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

const isActive = (href) => {
  const path = window.location.pathname
  if (href === '/admin') {
    return path === '/admin' || path === '/admin/'
  }
  return path.startsWith(href)
}

const toggleSidebar = () => {
  if (isMobile.value) {
    isMobileSidebarOpen.value = !isMobileSidebarOpen.value
  } else {
    isSidebarOpen.value = !isSidebarOpen.value
  }
}

const closeMobileSidebar = () => {
  isMobileSidebarOpen.value = false
}
</script>

<template>
  <div class="dark min-h-screen bg-background">
    <!-- Toast Container -->
    <Sonner />

    <!-- Command Palette (CMD+K) -->
    <CommandMenu persona="admin" />

    <!-- Mobile Sidebar (Sheet) -->
    <Sheet
      v-model:open="isMobileSidebarOpen"
      side="left"
      title="Navigation"
      class="w-64 bg-sidebar text-sidebar-foreground"
    >
      <nav class="flex-1 px-3 py-4 space-y-1">
        <Link
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          :class="[
            'group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200',
            isActive(item.href)
              ? 'bg-primary text-primary-foreground shadow-lg'
              : 'text-sidebar-foreground/70 hover:bg-sidebar-hover hover:text-sidebar-foreground'
          ]"
          @click="closeMobileSidebar"
        >
          <component
            :is="item.icon"
            :class="[
              'w-5 h-5 flex-shrink-0',
              isActive(item.href) ? 'text-primary-foreground' : 'text-sidebar-foreground/60'
            ]"
          />
          <span class="ml-3">{{ item.name }}</span>
        </Link>
      </nav>
    </Sheet>

    <!-- Desktop Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-40 hidden lg:flex flex-col bg-sidebar transition-all duration-300 ease-in-out',
        isSidebarOpen ? 'w-64' : 'w-20'
      ]"
    >
      <!-- Logo Section -->
      <div class="flex h-16 items-center justify-between border-b border-sidebar-foreground/10 px-4">
        <Transition
          enter-active-class="transition-opacity duration-200"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition-opacity duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
          mode="out-in"
        >
          <h1 v-if="isSidebarOpen" class="text-xl font-bold text-sidebar-foreground tracking-tight">
            <span class="text-primary">Posita</span> Admin
          </h1>
          <span v-else class="text-xl font-bold text-primary">P</span>
        </Transition>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <Link
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          :class="[
            'group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200',
            isActive(item.href)
              ? 'bg-primary text-primary-foreground shadow-lg'
              : 'text-sidebar-foreground/70 hover:bg-sidebar-hover hover:text-sidebar-foreground'
          ]"
        >
          <component
            :is="item.icon"
            :class="[
              'w-5 h-5 flex-shrink-0',
              isActive(item.href) ? 'text-primary-foreground' : 'text-sidebar-foreground/60 group-hover:text-sidebar-foreground'
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
            <span v-if="isSidebarOpen" class="ml-3 whitespace-nowrap overflow-hidden">
              {{ item.name }}
            </span>
          </Transition>
        </Link>
      </nav>

      <!-- User Info Footer -->
      <div class="border-t border-sidebar-foreground/10 p-4">
        <div :class="['flex items-center', isSidebarOpen ? 'space-x-3' : 'justify-center']">
          <div class="flex-shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-primary to-primary/70 flex items-center justify-center text-primary-foreground font-semibold text-sm shadow-lg">
            {{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}
          </div>
          <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <div v-if="isSidebarOpen" class="flex-1 min-w-0">
              <p class="text-sm font-medium text-sidebar-foreground truncate">
                {{ user?.name || 'Admin' }}
              </p>
              <p class="text-xs text-sidebar-foreground/60 truncate">
                {{ user?.email || 'admin@example.com' }}
              </p>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Collapse Toggle Button -->
      <button
        @click="toggleDesktopSidebar"
        class="absolute -right-3 top-20 w-6 h-6 rounded-full bg-primary text-primary-foreground flex items-center justify-center shadow-lg hover:bg-primary/90 transition-colors z-50"
        title="Toggle sidebar"
      >
        <PanelLeftClose v-if="isSidebarOpen" class="w-3.5 h-3.5" />
        <PanelLeft v-else class="w-3.5 h-3.5" />
      </button>
    </aside>

    <!-- Main Content Area -->
    <div
      :class="[
        'transition-all duration-300 ease-in-out min-h-screen flex flex-col',
        isMobile ? 'ml-0' : (isSidebarOpen ? 'lg:ml-64' : 'lg:ml-20')
      ]"
    >
      <!-- Top Header -->
      <header class="sticky top-0 z-30 bg-card/80 backdrop-blur-md border-b border-border shadow-sm">
        <div class="flex items-center justify-between h-16 px-4 lg:px-6">
          <!-- Left: Menu Toggle -->
          <div class="flex items-center space-x-4">
            <button
              @click="toggleSidebar"
              class="p-2 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors focus:outline-none focus:ring-2 focus:ring-ring/20"
            >
              <Menu class="w-6 h-6" />
            </button>

            <div class="hidden sm:block">
              <slot name="header" />
            </div>
          </div>

          <!-- Right: Profile Dropdown -->
          <DropdownMenu>
            <template #trigger>
              <button
                class="flex items-center space-x-3 p-2 rounded-lg hover:bg-muted transition-colors focus:outline-none focus:ring-2 focus:ring-ring/20"
              >
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-primary/70 flex items-center justify-center text-primary-foreground font-semibold text-sm">
                  {{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}
                </div>
                <span class="hidden md:block text-sm font-medium text-foreground">
                  {{ user?.name || 'Admin' }}
                </span>
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
              <Sun v-if="theme === 'employee'" class="w-4 h-4 mr-2" />
              <Moon v-else class="w-4 h-4 mr-2" />
              {{ theme === 'admin' ? 'Employee Theme' : 'Admin Theme' }}
            </DropdownMenuItem>

            <DropdownMenuItem as-child>
              <Link href="/profile" class="flex items-center">
                <Settings class="w-4 h-4 mr-2" />
                Profile Settings
              </Link>
            </DropdownMenuItem>

            <DropdownMenuSeparator />

            <DropdownMenuItem as-child destructive>
              <Link href="/logout" method="post" as="button" class="flex items-center w-full">
                <LogOut class="w-4 h-4 mr-2" />
                Sign Out
              </Link>
            </DropdownMenuItem>
          </DropdownMenu>
        </div>

        <!-- Mobile Header Slot -->
        <div class="sm:hidden px-4 pb-3" v-if="$slots.header">
          <slot name="header" />
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-4 lg:p-6">
        <slot />
      </main>

      <!-- Footer -->
      <footer class="py-4 px-6 text-center text-xs text-muted-foreground border-t border-border">
        © {{ new Date().getFullYear() }} Posita. All rights reserved.
      </footer>
    </div>
  </div>
</template>
