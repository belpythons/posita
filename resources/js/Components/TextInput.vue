<script setup>
/**
 * TextInput - Enhanced input field with variant support
 * 
 * @example
 * <TextInput v-model="email" variant="auth" placeholder="Email" />
 */
import { onMounted, ref, computed } from 'vue';

const props = defineProps({
  /** Input variant: 'default' or 'auth' (white bg for dark pages) */
  variant: {
    type: String,
    default: 'default',
    validator: (v) => ['default', 'auth'].includes(v)
  },
  /** Whether to show error styling */
  error: {
    type: Boolean,
    default: false
  }
})

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });

const inputClasses = computed(() => {
  const base = [
    'w-full rounded-lg border shadow-sm',
    'transition-all duration-200',
    'focus:outline-none focus:ring-2 focus:ring-offset-0',
    'disabled:opacity-50 disabled:cursor-not-allowed'
  ]

  if (props.variant === 'auth') {
    // Auth variant: White background, dark text (for dark login pages)
    return [
      ...base,
      'bg-white text-slate-900 placeholder:text-slate-400',
      props.error 
        ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' 
        : 'border-slate-300 focus:border-emerald-500 focus:ring-emerald-500/20'
    ]
  }

  // Default variant: Uses CSS variables
  return [
    ...base,
    'bg-background text-foreground placeholder:text-muted-foreground',
    props.error 
      ? 'border-destructive focus:border-destructive focus:ring-destructive/20' 
      : 'border-input focus:border-primary focus:ring-ring/20'
  ]
})
</script>

<template>
    <input
        :class="inputClasses"
        v-model="model"
        ref="input"
    />
</template>
