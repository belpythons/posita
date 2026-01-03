<script setup>
/**
 * ActionButton - Primary action button with gradient styling
 * 
 * @example
 * <ActionButton variant="positive" :icon="Store">Buka Toko</ActionButton>
 * <ActionButton variant="negative" :icon="Lock">Tutup Toko</ActionButton>
 */
import { computed } from 'vue'
import { Loader2 } from 'lucide-vue-next'

const props = defineProps({
  /** Button variant: 'positive' (green) or 'negative' (orange) */
  variant: {
    type: String,
    default: 'positive',
    validator: (v) => ['positive', 'negative'].includes(v)
  },
  /** Lucide icon component to display */
  icon: {
    type: [Object, Function],
    default: null
  },
  /** Button type attribute */
  type: {
    type: String,
    default: 'button'
  },
  /** Disabled state */
  disabled: {
    type: Boolean,
    default: false
  },
  /** Loading state - shows spinner and disables button */
  loading: {
    type: Boolean,
    default: false
  },
  /** Size variant */
  size: {
    type: String,
    default: 'default',
    validator: (v) => ['sm', 'default', 'lg'].includes(v)
  },
  /** Full width button */
  fullWidth: {
    type: Boolean,
    default: false
  }
})

const buttonClasses = computed(() => {
  const base = [
    'inline-flex items-center justify-center font-semibold rounded-xl',
    'transition-all duration-200 ease-out',
    'focus:outline-none focus:ring-2 focus:ring-offset-2',
    'disabled:opacity-50 disabled:cursor-not-allowed',
    'active:scale-[0.98]',
    'shadow-lg hover:shadow-xl'
  ]

  // Size classes
  const sizes = {
    sm: 'text-sm px-3 py-2',
    default: 'text-sm px-4 py-2.5',
    lg: 'text-base px-6 py-3'
  }

  // Variant classes - Only positive (green) and negative (orange)
  const variants = {
    positive: [
      'bg-gradient-to-r from-emerald-500 to-emerald-600',
      'hover:from-emerald-600 hover:to-emerald-700',
      'text-white',
      'focus:ring-emerald-500',
      'shadow-emerald-500/25 hover:shadow-emerald-500/40'
    ],
    negative: [
      'bg-gradient-to-r from-orange-500 to-orange-600',
      'hover:from-orange-600 hover:to-orange-700',
      'text-white',
      'focus:ring-orange-500',
      'shadow-orange-500/25 hover:shadow-orange-500/40'
    ]
  }

  return [
    ...base,
    sizes[props.size],
    ...variants[props.variant],
    props.fullWidth && 'w-full'
  ]
})

// Icon classes - White for all variants
const iconClasses = computed(() => {
  const sizeClass = props.size === 'lg' ? 'w-5 h-5' : 'w-4 h-4'
  // White icon for all variants
  return [sizeClass, 'mr-2', 'text-white']
})
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
  >
    <!-- Loading Spinner -->
    <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
    
    <!-- Icon (Orange gradient for positive, white for others) -->
    <component 
      v-else-if="icon"
      :is="icon" 
      :class="iconClasses"
    />
    
    <!-- Button Text -->
    <slot />
  </button>
</template>

<style scoped>
/* Orange gradient icon for positive variant - SVG stroke approach */
.icon-gradient-orange {
  stroke: url(#orange-gradient);
}
</style>
