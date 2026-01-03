<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, Sonner } from '@/Components/ui'
import ActionButton from '@/Components/ActionButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Eye, EyeOff, LogIn, Mail, Lock } from 'lucide-vue-next'

defineProps({
  canResetPassword: Boolean,
  status: String,
})

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <GuestLayout>
    <Head title="Log in" />
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

    <!-- Login Card -->
    <div class="min-h-screen flex items-center justify-center p-4">
      <Card class="w-full max-w-md glass border-white/10 animate-fade-in">
        <CardHeader class="text-center">
          <!-- Logo Icon with Green gradient -->
          <div class="mx-auto w-16 h-16 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-emerald-500/20">
              <LogIn class="w-5 h-5 text-white" />
          </div>
          <CardTitle class="text-2xl text-slate-800">Selamat Datang</CardTitle>
          <CardDescription class="text-slate-500">
            Masuk ke akun Posita Anda
          </CardDescription>
        </CardHeader>

        <CardContent>
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

            <!-- Password -->
            <div class="space-y-2">
              <InputLabel for="password" class="text-slate-700">Password</InputLabel>
              <div class="relative">
                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 z-10" />
                <TextInput
                  id="password"
                  :type="showPassword ? 'text' : 'password'"
                  v-model="form.password"
                  variant="auth"
                  :error="!!form.errors.password"
                  class="pl-10 pr-10 py-3 bg-white border-slate-200 text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                  placeholder="••••••••"
                  required
                  autocomplete="current-password"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors z-10"
                >
                  <EyeOff v-if="showPassword" class="w-5 h-5" />
                  <Eye v-else class="w-5 h-5" />
                </button>
              </div>
              <p v-if="form.errors.password" class="text-sm text-red-400">
                {{ form.errors.password }}
              </p>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
              <label class="flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.remember"
                  class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                />
                <span class="ml-2 text-sm text-slate-600">Ingat saya</span>
              </label>

              <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-sm text-emerald-400 hover:text-emerald-300 transition-colors"
              >
                Lupa password?
              </Link>
            </div>

            <!-- Submit Button - ActionButton with Green + Orange Icon -->
            <ActionButton
              type="submit"
              :icon="LogIn"
              :loading="form.processing"
              :disabled="form.processing"
              full-width
              size="lg"
            >
              {{ form.processing ? 'Masuk...' : 'Masuk' }}
            </ActionButton>
          </form>
        </CardContent>
      </Card>
    </div>
  </GuestLayout>
</template>
