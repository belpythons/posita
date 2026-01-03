<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { Card, CardContent, Label } from '@/Components/ui'
import ActionButton from '@/Components/ActionButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Store, Package, Lock, FileText, ArrowRight } from 'lucide-vue-next'
import { computed } from 'vue'

const props = defineProps({
  hasActiveSession: {
    type: Boolean,
    default: false,
  },
  activeSession: {
    type: Object,
    default: null,
  },
  lastClosedSession: {
    type: Object,
    default: null,
  },
  today: {
    type: String,
    default: '',
  },
})

const form = useForm({
  opening_cash: '',
})

const submit = () => {
  form.post('/pos/open')
}

const sessionInfo = computed(() => ({
  shiftName: props.activeSession?.shift_name ?? null,
  openingBalance: props.activeSession?.opening_cash ?? 0,
  isActive: props.hasActiveSession,
  openedAt: props.activeSession?.opened_at ?? null,
}))

const formatMoney = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value || 0)
}
</script>

<template>
  <Head title="Buka Toko" />

  <EmployeeLayout :session-info="sessionInfo">
    <div class="relative w-full max-w-5xl mx-auto overflow-hidden rounded-3xl shadow-2xl">
      <div class="flex flex-col lg:flex-row min-h-[500px]">
        <!-- Left Side - Hero with Emerald Gradient -->
        <div class="relative lg:w-1/2 w-full flex flex-col justify-center items-center text-white p-8 lg:p-12 bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-500 overflow-hidden">
          <!-- Floating Elements -->
          <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full blur-xl animate-float" />
          <div class="absolute bottom-20 right-10 w-32 h-32 bg-white/10 rounded-full blur-xl animate-float" style="animation-delay: 1s" />

          <!-- Glass Panel -->
          <div class="relative z-10 text-center space-y-6 glass rounded-2xl p-8 bg-white/10 border-white/20">
            <Store class="w-16 h-16 mx-auto text-white/90" />
            <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight text-white drop-shadow">
              Kantin Cerdas
            </h1>
            <p class="text-white/80 max-w-sm mx-auto leading-relaxed">
              Selamat datang di sistem kasir modern untuk kantin Anda.
              Mulailah hari dengan membuka sesi toko dan kelola transaksi dengan mudah.
            </p>
            <div class="bg-white/20 backdrop-blur-md border border-white/30 rounded-xl py-4 px-6 inline-block">
              <p class="uppercase text-xs tracking-wider text-white/70 font-semibold">Hari Ini</p>
              <p class="text-xl font-bold text-white drop-shadow-sm">
                {{ today }}
              </p>
            </div>
          </div>
        </div>

        <!-- Right Side - Form -->
        <div class="lg:w-1/2 w-full bg-card p-8 lg:p-12 flex flex-col justify-center">
          <!-- Already has active session -->
          <div v-if="hasActiveSession" class="space-y-6 animate-fade-in">
            <div class="text-center">
              <div class="w-16 h-16 mx-auto bg-emerald-100 rounded-full flex items-center justify-center mb-4">
                <Store class="w-8 h-8 text-emerald-600" />
              </div>
              <h3 class="text-2xl font-semibold text-foreground">
                Sesi Toko Aktif
              </h3>
              <p class="text-muted-foreground mt-1">
                Anda sudah membuka toko hari ini
              </p>
            </div>

            <Card class="border-emerald-200 bg-emerald-50/50 dark:border-emerald-800 dark:bg-emerald-950/20">
              <CardContent class="pt-6">
                <div class="space-y-3">
                  <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">Kas Awal:</span>
                    <span class="font-bold text-foreground text-lg">
                      {{ formatMoney(activeSession?.opening_cash || 0) }}
                    </span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">Dibuka:</span>
                    <span class="text-foreground">{{ activeSession?.opened_at }}</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <div class="flex flex-col sm:flex-row gap-3">
              <!-- Positive Action: Green -->
              <Link href="/pos/consignment" class="flex-1">
                <ActionButton :icon="Package" full-width>
                  Input Barang Titipan
                </ActionButton>
              </Link>
              <!-- Negative Action: Orange -->
              <Link href="/pos/close" class="flex-1">
                <ActionButton variant="negative" :icon="Lock" full-width>
                  Tutup Toko
                </ActionButton>
              </Link>
            </div>
          </div>

          <!-- Open new session form -->
          <div v-else class="space-y-6">
            <!-- Last Session Report Download -->
            <div
              v-if="lastClosedSession"
              class="flex items-center justify-between p-4 rounded-xl bg-muted border border-border animate-fade-in"
            >
              <div>
                <p class="text-sm font-medium text-foreground">Sesi Terakhir</p>
                <p class="text-xs text-muted-foreground">{{ lastClosedSession.closed_at }}</p>
              </div>
              <a 
                :href="`/pos/report/${lastClosedSession.id}`" 
                target="_blank" 
                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-primary hover:text-primary/80 border border-primary/30 rounded-lg hover:bg-primary/5 transition-colors"
              >
                <FileText class="w-4 h-4 mr-1" />
                Laporan
              </a>
            </div>

            <!-- Form Header -->
            <div class="text-center animate-fade-in">
              <Store class="w-16 h-16 mx-auto text-primary mb-4" />
              <h3 class="text-2xl font-semibold text-foreground">Buka Sesi Toko</h3>
              <p class="text-muted-foreground mt-1">Masukkan kas awal hari ini</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-5">
              <div class="space-y-2">
                <Label for="opening_cash">Kas Awal (Modal)</Label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground font-medium z-10">
                    Rp
                  </span>
                  <TextInput
                    id="opening_cash"
                    v-model="form.opening_cash"
                    type="number"
                    min="0"
                    step="1000"
                    :error="!!form.errors.opening_cash"
                    class="pl-10 text-lg py-3"
                    placeholder="0"
                    required
                  />
                </div>
                <p v-if="form.errors.opening_cash" class="text-sm text-destructive">
                  {{ form.errors.opening_cash }}
                </p>
              </div>

              <!-- Positive Action: Green with Orange Icon -->
              <ActionButton
                type="submit"
                :icon="Store"
                :loading="form.processing"
                :disabled="form.processing"
                full-width
                size="lg"
              >
                {{ form.processing ? 'Membuka...' : 'Buka Toko Sekarang' }}
              </ActionButton>
            </form>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>