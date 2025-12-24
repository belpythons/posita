<template>
  <span :class="badgeClasses">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

defineProps({
  label: {
    type: String,
    required: true,
  },
  variant: {
    type: String,
    default: 'info',
    validator: (value) => ['info', 'success', 'warning', 'danger'].includes(value),
  },
})

const variantClasses = {
  info: 'bg-blue-100 text-blue-800 border border-blue-300',
  success: 'bg-green-100 text-green-800 border border-green-300',
  warning: 'bg-orange-100 text-orange-800 border border-orange-300',
  danger: 'bg-red-100 text-red-800 border border-red-300',
}

const badgeClasses = computed(() => [
  'inline-flex',
  'items-center',
  'px-3',
  'py-1',
  'rounded-full',
  'text-sm',
  'font-medium',
  'whitespace-nowrap',
  variantClasses[variant],
])
</script>
