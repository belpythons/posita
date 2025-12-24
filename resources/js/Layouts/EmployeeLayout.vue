<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ToastNotification from '@/Components/ToastNotification.vue'
</script>

<template>
  <AuthenticatedLayout>
    <!-- HEADER -->
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-blue-700">
          <slot name="header" />
        </h2>

        <span class="text-sm text-orange-500 font-medium">
          Employee Panel
        </span>
      </div>
    </template>

    <!-- MAIN CONTENT -->
    <main class="px-6 py-6 bg-blue-50 min-h-[calc(100vh-4rem)]">
      <slot />
    </main>

    <!-- GLOBAL UI FEEDBACK -->
    <ToastNotification />
  </AuthenticatedLayout>
</template>
