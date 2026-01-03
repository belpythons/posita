<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Button, Input, Label, Card, CardHeader, CardTitle, CardDescription, CardContent, Sonner } from '@/Components/ui'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
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

    <!-- Animated Background -->
    <div class="fixed inset-0 -z-10 overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
      <!-- Floating Shapes -->
      <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-primary/20 rounded-full blur-3xl animate-float" />
      <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-accent/10 rounded-full blur-3xl animate-float" style="animation-delay: 1.5s" />
      <div class="absolute top-1/2 left-1/2 w-48 h-48 bg-primary/15 rounded-full blur-2xl animate-float" style="animation-delay: 0.75s" />

      <!-- Grid Pattern Overlay -->
      <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:50px_50px]" />
    </div>

    <!-- Login Card -->
    <div class="min-h-screen flex items-center justify-center p-4">
      <Card class="w-full max-w-md glass border-white/10 animate-fade-in">
        <CardHeader class="text-center">
          <div class="mx-auto w-16 h-16 bg-gradient-to-br from-primary to-primary/70 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
            <LogIn class="w-8 h-8 text-primary-foreground" />
          </div>
          <CardTitle class="text-2xl text-white">Welcome Back</CardTitle>
          <CardDescription class="text-slate-400">
            Sign in to your Posita account
          </CardDescription>
        </CardHeader>

        <CardContent>
          <div v-if="status" class="mb-4 text-sm font-medium text-emerald-400 bg-emerald-500/10 rounded-lg p-3">
            {{ status }}
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <!-- Email -->
            <div class="space-y-2">
              <Label for="email" :error="form.errors.email" class="text-slate-300">
                Email
              </Label>
              <div class="relative">
                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                <Input
                  id="email"
                  type="email"
                  v-model="form.email"
                  :error="!!form.errors.email"
                  class="pl-10 bg-slate-800/50 border-slate-600 text-slate-100 placeholder:text-slate-500 focus:bg-slate-800/70 focus:border-primary"
                  placeholder="name@example.com"
                  required
                  autofocus
                  autocomplete="username"
                />
              </div>
              <p v-if="form.errors.email" class="text-sm text-destructive">
                {{ form.errors.email }}
              </p>
            </div>

            <!-- Password -->
            <div class="space-y-2">
              <Label for="password" :error="form.errors.password" class="text-slate-300">
                Password
              </Label>
              <div class="relative">
                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                <Input
                  id="password"
                  :type="showPassword ? 'text' : 'password'"
                  v-model="form.password"
                  :error="!!form.errors.password"
                  class="pl-10 pr-10 bg-slate-800/50 border-slate-600 text-slate-100 placeholder:text-slate-500 focus:bg-slate-800/70 focus:border-primary"
                  placeholder="••••••••"
                  required
                  autocomplete="current-password"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 transition-colors"
                >
                  <EyeOff v-if="showPassword" class="w-5 h-5" />
                  <Eye v-else class="w-5 h-5" />
                </button>
              </div>
              <p v-if="form.errors.password" class="text-sm text-destructive">
                {{ form.errors.password }}
              </p>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
              <label class="flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  v-model="form.remember"
                  class="rounded border-white/20 bg-white/5 text-primary focus:ring-primary focus:ring-offset-0"
                />
                <span class="ml-2 text-sm text-slate-400">Remember me</span>
              </label>

              <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-sm text-primary hover:text-primary/80 transition-colors"
              >
                Forgot password?
              </Link>
            </div>

            <!-- Submit -->
            <Button
              type="submit"
              :loading="form.processing"
              :disabled="form.processing"
              class="w-full"
              size="lg"
            >
              <LogIn v-if="!form.processing" class="w-5 h-5 mr-2" />
              {{ form.processing ? 'Signing in...' : 'Sign in' }}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </GuestLayout>
</template>
