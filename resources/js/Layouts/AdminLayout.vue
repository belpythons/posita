<!--
  Created/Modified by: Belva Pranama Sriwibowo
  NIM: 202312066
  Feature: Core & Admin - Layout utama halaman admin
-->
<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { Sonner, DropdownMenu, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuLabel, Sheet } from '@/Components/ui'
import CommandMenu from '@/Components/CommandMenu.vue'
import { useTheme } from '@/composables/useTheme'
import {
  LayoutDashboard,
  Users,
  Package,
  UserCircle,
  Menu,
  LogOut,
  Settings,
  ChevronDown,
  PanelLeftClose,
  PanelLeftOpen,
  Sun,
  Moon
} from 'lucide-vue-next'

const { toggleTheme, isDark } = useTheme()

const page = usePage()
const user = computed(() => page.props.auth?.user)

// Navigation items
const navigation = [
  { name: 'Dashboard', href: '/admin', icon: LayoutDashboard },
  { name: 'Partners', href: '/admin/partners', icon: Users },
  { name: 'Box Templates', href: '/admin/box-templates', icon: Package },
  { name: 'Users', href: '/admin/users', icon: UserCircle },
]

const isSidebarCollapsed = ref(localStorage.getItem('admin-sidebar-collapsed') === 'true')
const isMobileSidebarOpen = ref(false)

const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
  localStorage.setItem('admin-sidebar-collapsed', String(isSidebarCollapsed.value))
}

// Flash message watcher
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) toast.success(flash.success)
    if (flash?.error) toast.error(flash.error)
    if (flash?.warning) toast.warning(flash.warning)
    if (flash?.info) toast.info(flash.info)
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
</script>

<template>
  <div class="h-screen w-full bg-background flex flex-col overflow-hidden transition-all duration-300 ease-in-out">
    <!-- Toast Container -->
    <Sonner />

    <!-- Command Palette -->
    <CommandMenu persona="admin" />

    <!-- Top Navigation Bar (Fixed Height) -->
    <header class="flex-none z-40 bg-card shadow-sm border-b border-border">
      <div class="flex items-center justify-between h-16 px-4 lg:px-6">
        <!-- Logo / Brand & Mobile Menu Trigger -->
        <div class="flex items-center space-x-4">
            <!-- Mobile Menu -->
            <button @click="isMobileSidebarOpen = true" class="lg:hidden p-2 -ml-2 text-muted-foreground hover:bg-muted rounded-lg">
                <Menu class="w-6 h-6" />
            </button>

            <h1 class="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                <span class="text-primary text-2xl font-black tracking-tighter">Posita</span>
                <span class="text-xs bg-primary/10 text-primary px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Admin</span>
            </h1>
        </div>

        <!-- Right Side: User Menu -->
        <div class="flex items-center space-x-4">
          <DropdownMenu>
            <template #trigger>
              <button class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-muted transition-colors border border-transparent hover:border-border">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-blue-500 flex items-center justify-center text-white font-semibold text-sm shadow-sm overflow-hidden">
                  <img 
                    v-if="user?.profile_photo_path" 
                    :src="`/storage/${user.profile_photo_path}`" 
                    class="w-full h-full object-cover" 
                  />
                  <span v-else>{{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}</span>
                </div>
                <!-- Hide Name on Mobile -->
                <div class="hidden md:flex flex-col items-start leading-none ml-1">
                     <span class="text-sm font-medium text-foreground">{{ user?.name }}</span>
                     <span class="text-[10px] text-muted-foreground">Administrator</span>
                </div>
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
                Logout
              </Link>
            </DropdownMenuItem>
          </DropdownMenu>
        </div>
      </div>
    </header>

    <!-- Main Content Wrapper -->
    <div class="flex flex-1 overflow-hidden relative flex-row">
      <!-- Desktop Sidebar (Left, Relative, Flex Item) -->
      <aside 
        :class="[
          'hidden lg:flex flex-col h-full bg-card border-r border-border z-30 transition-all duration-300 ease-in-out relative',
          isSidebarCollapsed ? 'w-16' : 'w-64'
        ]"
      >
         <!-- Collapse Toggle -->
        <button
          @click="toggleSidebar"
          class="absolute -right-3 top-6 w-6 h-6 rounded-full bg-card border border-border text-foreground flex items-center justify-center shadow-md hover:bg-muted transition-colors z-50 transform hover:scale-105"
          title="Toggle sidebar"
        >
          <PanelLeftOpen v-if="isSidebarCollapsed" class="w-3.5 h-3.5" />
          <PanelLeftClose v-else class="w-3.5 h-3.5" />
        </button>

        <!-- Sidebar Header / Navigation Title -->
        <div class="flex-none h-14 flex items-center px-4 border-b border-border bg-card/50 backdrop-blur-sm">
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
                    <span class="text-xs font-semibold uppercase text-muted-foreground tracking-wider">Main Menu</span>
                </div>
                <div v-else class="mx-auto">
                    <Menu class="w-4 h-4 text-muted-foreground" />
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
      </aside>

      <!-- Main Content Area -->
      <main 
        class="flex-1 overflow-y-auto overflow-x-hidden bg-muted/20 w-full min-w-0" 
      >
        <div class="h-full w-full p-4 lg:p-8 max-w-7xl mx-auto">
            <slot />
        </div>
      </main>
    </div>

    <!-- Mobile Sidebar (Sheet) -->
    <Sheet
      v-model:open="isMobileSidebarOpen"
      side="left"
      title="Navigation"
      class="w-72 bg-card text-foreground"
    >
       <div class="flex flex-col h-full">
            <div class="p-6 border-b border-border">
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <span class="text-primary">Posita</span> Admin
                </h2>
            </div>
            
            <nav class="flex-1 p-4 space-y-2">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center px-4 py-3 rounded-xl transition-colors',
                         isActive(item.href)
                            ? 'bg-primary/10 text-primary font-medium'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    ]"
                    @click="isMobileSidebarOpen = false"
                >
                    <component :is="item.icon" class="w-5 h-5 mr-3" />
                    {{ item.name }}
                </Link>
            </nav>
            
            <div class="p-4 border-t border-border">
                 <Link href="/logout" method="post" as="button" class="flex items-center w-full px-4 py-3 rounded-xl text-destructive hover:bg-destructive/10 transition-colors">
                    <LogOut class="w-5 h-5 mr-3" />
                    Sign Out
                 </Link>
            </div>
       </div>
    </Sheet>
  </div>
</template>
