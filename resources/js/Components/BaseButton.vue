<template>
  <button :type="type" :disabled="disabled" :class="buttonClasses">
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'

defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'danger'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const variantClasses = {
  primary: 'bg-blue-600 hover:bg-blue-700 text-white',
  secondary: 'bg-gray-600 hover:bg-gray-700 text-white',
  danger: 'bg-red-600 hover:bg-red-700 text-white',
}

const buttonClasses = computed(() => [
  'inline-flex',
  'items-center',
  'justify-center',
  'px-4',
  'py-2',
  'rounded-md',
  'font-medium',
  'text-sm',
  'transition-colors',
  'duration-150',
  'focus:outline-none',
  'focus:ring-2',
  'focus:ring-offset-2',
  disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
  !disabled && variantClasses[variant],
  disabled && 'bg-gray-400 text-gray-600',
])
</script>
