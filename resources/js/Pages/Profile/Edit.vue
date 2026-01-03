<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  Input,
  Label,
  Button,
} from '@/Components/ui'
import { User, Mail, Lock, Trash2, AlertTriangle, Loader2, CheckCircle } from 'lucide-vue-next'

const props = defineProps({
  mustVerifyEmail: Boolean,
  status: String,
})

const page = usePage()
const user = computed(() => page.props.auth?.user)

// Profile Info Form
const profileForm = useForm({
  name: user.value?.name || '',
  email: user.value?.email || '',
})

const updateProfile = () => {
  profileForm.patch(route('profile.update'), {
    preserveScroll: true,
  })
}

// Password Form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const updatePassword = () => {
  passwordForm.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => passwordForm.reset(),
  })
}

// Delete Account Form
const deleteForm = useForm({
  password: '',
})

const showDeleteConfirm = computed(() => deleteForm.password.length > 0)

const deleteAccount = () => {
  deleteForm.delete(route('profile.destroy'), {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Profile Settings" />

  <AdminLayout>
    <template #header>
      <h2 class="text-xl font-semibold text-foreground">Profile Settings</h2>
    </template>

    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Glassmorphic Header with Avatar -->
      <div class="glass rounded-2xl p-6 flex items-center gap-6">
        <!-- Avatar -->
        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary to-primary/60 flex items-center justify-center text-primary-foreground text-3xl font-bold shadow-lg">
          {{ user?.name?.charAt(0)?.toUpperCase() || 'U' }}
        </div>
        <div class="flex-1">
          <h1 class="text-2xl font-bold text-foreground">{{ user?.name }}</h1>
          <p class="text-muted-foreground">{{ user?.email }}</p>
          <p class="text-xs text-muted-foreground mt-1">
            Role: <span class="capitalize font-medium text-primary">{{ user?.role || 'User' }}</span>
          </p>
        </div>
      </div>

      <!-- Profile Information Card -->
      <Card>
        <CardHeader>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
              <User class="w-5 h-5 text-primary" />
            </div>
            <div>
              <CardTitle>Profile Information</CardTitle>
              <CardDescription>Update your account's profile information and email address.</CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="updateProfile" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="name" :error="!!profileForm.errors.name">Name</Label>
                <div class="relative">
                  <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                  <Input
                    id="name"
                    v-model="profileForm.name"
                    :error="!!profileForm.errors.name"
                    class="pl-10"
                    required
                    autocomplete="name"
                  />
                </div>
                <p v-if="profileForm.errors.name" class="text-sm text-destructive">{{ profileForm.errors.name }}</p>
              </div>

              <div class="space-y-2">
                <Label for="email" :error="!!profileForm.errors.email">Email</Label>
                <div class="relative">
                  <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                  <Input
                    id="email"
                    type="email"
                    v-model="profileForm.email"
                    :error="!!profileForm.errors.email"
                    class="pl-10"
                    required
                    autocomplete="username"
                  />
                </div>
                <p v-if="profileForm.errors.email" class="text-sm text-destructive">{{ profileForm.errors.email }}</p>
              </div>
            </div>

            <div v-if="mustVerifyEmail && !user?.email_verified_at" class="p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
              <p class="text-sm text-yellow-800 dark:text-yellow-200">
                Your email address is unverified. 
                <button type="button" class="underline hover:no-underline" @click="$inertia.post(route('verification.send'))">
                  Click here to resend verification.
                </button>
              </p>
            </div>

            <div class="flex items-center gap-4">
              <Button type="submit" :loading="profileForm.processing" :disabled="profileForm.processing">
                <Loader2 v-if="profileForm.processing" class="w-4 h-4 mr-2 animate-spin" />
                Save Changes
              </Button>
              <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
              >
                <span v-if="profileForm.recentlySuccessful" class="text-sm text-accent flex items-center gap-1">
                  <CheckCircle class="w-4 h-4" /> Saved!
                </span>
              </Transition>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Update Password Card -->
      <Card>
        <CardHeader>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
              <Lock class="w-5 h-5 text-primary" />
            </div>
            <div>
              <CardTitle>Update Password</CardTitle>
              <CardDescription>Ensure your account is using a long, random password to stay secure.</CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="updatePassword" class="space-y-4">
            <div class="space-y-2">
              <Label for="current_password" :error="!!passwordForm.errors.current_password">Current Password</Label>
              <Input
                id="current_password"
                type="password"
                v-model="passwordForm.current_password"
                :error="!!passwordForm.errors.current_password"
                autocomplete="current-password"
              />
              <p v-if="passwordForm.errors.current_password" class="text-sm text-destructive">{{ passwordForm.errors.current_password }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="password" :error="!!passwordForm.errors.password">New Password</Label>
                <Input
                  id="password"
                  type="password"
                  v-model="passwordForm.password"
                  :error="!!passwordForm.errors.password"
                  autocomplete="new-password"
                />
                <p v-if="passwordForm.errors.password" class="text-sm text-destructive">{{ passwordForm.errors.password }}</p>
              </div>

              <div class="space-y-2">
                <Label for="password_confirmation">Confirm Password</Label>
                <Input
                  id="password_confirmation"
                  type="password"
                  v-model="passwordForm.password_confirmation"
                  autocomplete="new-password"
                />
              </div>
            </div>

            <div class="flex items-center gap-4">
              <Button type="submit" :loading="passwordForm.processing" :disabled="passwordForm.processing">
                <Loader2 v-if="passwordForm.processing" class="w-4 h-4 mr-2 animate-spin" />
                Update Password
              </Button>
              <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
              >
                <span v-if="passwordForm.recentlySuccessful" class="text-sm text-accent flex items-center gap-1">
                  <CheckCircle class="w-4 h-4" /> Password Updated!
                </span>
              </Transition>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Delete Account Card -->
      <Card class="border-destructive/30">
        <CardHeader>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-destructive/10 flex items-center justify-center">
              <Trash2 class="w-5 h-5 text-destructive" />
            </div>
            <div>
              <CardTitle class="text-destructive">Delete Account</CardTitle>
              <CardDescription>Once your account is deleted, all of its resources and data will be permanently deleted.</CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="p-4 rounded-lg bg-destructive/5 border border-destructive/20 mb-4">
            <div class="flex items-start gap-3">
              <AlertTriangle class="w-5 h-5 text-destructive mt-0.5 flex-shrink-0" />
              <p class="text-sm text-destructive">
                <strong>Warning:</strong> This action cannot be undone. Please enter your password to confirm you want to permanently delete your account.
              </p>
            </div>
          </div>

          <form @submit.prevent="deleteAccount" class="space-y-4">
            <div class="space-y-2 max-w-sm">
              <Label for="delete_password" :error="!!deleteForm.errors.password">Confirm Password</Label>
              <Input
                id="delete_password"
                type="password"
                v-model="deleteForm.password"
                :error="!!deleteForm.errors.password"
                placeholder="Enter your password to confirm"
              />
              <p v-if="deleteForm.errors.password" class="text-sm text-destructive">{{ deleteForm.errors.password }}</p>
            </div>

            <Button
              type="submit"
              variant="destructive"
              :loading="deleteForm.processing"
              :disabled="deleteForm.processing || !showDeleteConfirm"
            >
              <Loader2 v-if="deleteForm.processing" class="w-4 h-4 mr-2 animate-spin" />
              <Trash2 v-else class="w-4 h-4 mr-2" />
              Delete Account
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
