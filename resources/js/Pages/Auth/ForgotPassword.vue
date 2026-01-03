<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, Sonner } from '@/Components/ui'
import ActionButton from '@/Components/ActionButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { KeyRound, Mail, ArrowLeft } from 'lucide-vue-next'

defineProps({
  status: String,
})

const form = useForm({
  email: '',
})

const submit = () => {
  form.post(route('password.email'))
}
</script>

<template>
  <GuestLayout>
    <Head title="Lupa Password" />
    <Sonner />

    <!-- Animated Background with Orange Gradient -->
    <!-- Animated Background with Mixed Gradient -->
    <div class="fixed inset-0 -z-10 overflow-hidden bg-gradient-to-br from-emerald-100 via-white to-orange-100">
      <!-- Floating Shapes -->
      <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl animate-float" />
      <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl animate-float" style="animation-delay: 1.5s" />
      <div class="absolute top-1/2 left-1/2 w-48 h-48 bg-emerald-400/10 rounded-full blur-2xl animate-float" style="animation-delay: 0.75s" />

      <!-- Dot Pattern Overlay -->
      <div class="absolute inset-0 bg-pattern-dots opacity-50" />
    </div>

    <!-- Forgot Password Card -->
    <div class="min-h-screen flex items-center justify-center p-4">
      <Card class="w-full max-w-md glass border-white/10 animate-fade-in">
        <CardHeader class="text-center">
          <!-- Logo Icon with Green gradient -->
          <div class="mx-auto w-16 h-16 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-emerald-500/20">
              <KeyRound class="w-5 h-5 text-white" />
          </div>
          <CardTitle class="text-2xl text-slate-800">Lupa Password?</CardTitle>
          <CardDescription class="text-slate-500">
            Masukkan email Anda dan kami akan mengirim link reset password.
          </CardDescription>
        </CardHeader>

        <CardContent>
          <!-- Success Message -->
          <div v-if="status" class="mb-4 text-sm font-medium text-emerald-400 bg-emerald-500/10 rounded-lg p-3">
            {{ status }}
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <!-- Email -->
            <div class="space-y-2">
              <InputLabel for="email" class="text-slate-700">Email</InputLabel>
              <div class="relative">
                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 z-10" />
                <TextInput
                  id="email"
                  type="email"
                  v-model="form.email"
                  variant="auth"
                  :error="!!form.errors.email"
                  class="pl-10 py-3 bg-white border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                  placeholder="nama@email.com"
                  required
                  autofocus
                  autocomplete="username"
                />
              </div>
              <p v-if="form.errors.email" class="text-sm text-red-400">
                {{ form.errors.email }}
              </p>
            </div>

            <!-- Actions -->
            <div class="space-y-3">
              <ActionButton
                type="submit"
                :icon="Mail"
                :loading="form.processing"
                :disabled="form.processing"
                full-width
                size="lg"
              >
                {{ form.processing ? 'Mengirim...' : 'Kirim Link Reset' }}
              </ActionButton>

              <Link
                :href="route('login')"
                class="flex items-center justify-center gap-2 text-sm text-slate-500 hover:text-emerald-600 transition-colors"
              >
                <ArrowLeft class="w-4 h-4" />
                Kembali ke Login
              </Link>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </GuestLayout>
</template>
