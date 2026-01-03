<script setup>
/**
 * GenericModal - Reusable modal component with dark mode support
 * 
 * @example
 * <GenericModal v-model:open="showModal" title="Confirm Action">
 *   <template #trigger>
 *     <ActionButton>Open Modal</ActionButton>
 *   </template>
 *   <p>Modal content here</p>
 *   <template #footer>
 *     <ActionButton variant="negative" @click="showModal = false">Cancel</ActionButton>
 *     <ActionButton @click="confirm">Confirm</ActionButton>
 *   </template>
 * </GenericModal>
 */
import { computed, watch, ref, onMounted, onUnmounted } from 'vue'
import { X, AlertTriangle, AlertCircle, CheckCircle, Info } from 'lucide-vue-next'

const props = defineProps({
  /** Control modal visibility via v-model:open */
  open: {
    type: Boolean,
    default: false
  },
  /** Modal title */
  title: {
    type: String,
    default: ''
  },
  /** Modal description */
  description: {
    type: String,
    default: ''
  },
  /** Modal max width */
  maxWidth: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl', '2xl'].includes(v)
  },
  /** Modal variant for icon styling */
  variant: {
    type: String,
    default: 'default',
    validator: (v) => ['default', 'info', 'success', 'warning', 'danger'].includes(v)
  },
  /** Allow closing by clicking backdrop or pressing ESC */
  closeable: {
    type: Boolean,
    default: true
  },
  /** Show close button in header */
  showCloseButton: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:open', 'close'])
const dialog = ref(null)
const showContent = ref(props.open)

// Sync with v-model
watch(() => props.open, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden'
    showContent.value = true
    dialog.value?.showModal()
  } else {
    document.body.style.overflow = ''
    setTimeout(() => {
      dialog.value?.close()
      showContent.value = false
    }, 200)
  }
})

const close = () => {
  if (props.closeable) {
    emit('update:open', false)
    emit('close')
  }
}

const handleBackdropClick = (e) => {
  if (e.target === e.currentTarget) {
    close()
  }
}

const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.open && props.closeable) {
    e.preventDefault()
    close()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})

const maxWidthClasses = computed(() => ({
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-md',
  lg: 'sm:max-w-lg',
  xl: 'sm:max-w-xl',
  '2xl': 'sm:max-w-2xl'
}[props.maxWidth]))

const variantIcon = computed(() => ({
  default: null,
  info: Info,
  success: CheckCircle,
  warning: AlertCircle,
  danger: AlertTriangle
}[props.variant]))

const variantColors = computed(() => ({
  default: '',
  info: 'bg-blue-500/20 text-blue-500',
  success: 'bg-emerald-500/20 text-emerald-500',
  warning: 'bg-amber-500/20 text-amber-500',
  danger: 'bg-red-500/20 text-red-500'
}[props.variant]))
</script>

<template>
  <!-- Trigger slot (optional) -->
  <slot name="trigger" />

  <!-- Modal Dialog -->
  <Teleport to="body">
    <dialog
      ref="dialog"
      class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent"
    >
      <div
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
        @click="handleBackdropClick"
      >
        <!-- Backdrop -->
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-show="open"
            class="fixed inset-0 bg-black/70 backdrop-blur-sm"
            aria-hidden="true"
          />
        </Transition>

        <!-- Modal Panel -->
        <Transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div
            v-show="open"
            :class="[
              'relative transform overflow-hidden rounded-2xl',
              'bg-card text-card-foreground',
              'shadow-xl transition-all',
              'sm:mx-auto sm:w-full',
              maxWidthClasses
            ]"
            @click.stop
          >
            <template v-if="showContent">
              <!-- Header -->
              <div 
                v-if="title || $slots.header || showCloseButton"
                class="flex items-start justify-between gap-4 px-6 pt-6 pb-0"
              >
                <div class="flex items-start gap-4">
                  <!-- Variant Icon -->
                  <div 
                    v-if="variantIcon"
                    :class="[
                      'flex-shrink-0 p-2.5 rounded-full',
                      variantColors
                    ]"
                  >
                    <component :is="variantIcon" class="w-5 h-5" />
                  </div>

                  <div>
                    <slot name="header">
                      <h3 v-if="title" class="text-lg font-semibold">
                        {{ title }}
                      </h3>
                      <p v-if="description" class="text-sm text-muted-foreground mt-1">
                        {{ description }}
                      </p>
                    </slot>
                  </div>
                </div>

                <!-- Close Button -->
                <button
                  v-if="showCloseButton && closeable"
                  @click="close"
                  class="flex-shrink-0 p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                >
                  <X class="w-5 h-5" />
                </button>
              </div>

              <!-- Content -->
              <div class="px-6 py-4">
                <slot />
              </div>

              <!-- Footer -->
              <div 
                v-if="$slots.footer"
                class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 px-6 py-4 bg-muted/50 border-t border-border"
              >
                <slot name="footer" />
              </div>
            </template>
          </div>
        </Transition>
      </div>
    </dialog>
  </Teleport>
</template>
