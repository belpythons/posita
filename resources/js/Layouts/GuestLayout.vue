<script setup>
import { onMounted, onUnmounted } from 'vue'

/**
 * GuestLayout - Used for authentication pages (login, register, etc.)
 * Features animated background with theme-neutral (Orange) styling
 */

// Force theme-neutral on this layout
onMounted(() => {
  document.documentElement.classList.remove('dark')
  document.documentElement.classList.add('theme-neutral')
})

onUnmounted(() => {
  document.documentElement.classList.remove('theme-neutral')
})
</script>

<template>
  <div class="theme-neutral min-h-screen bg-background">
    <slot />
  </div>
</template>
