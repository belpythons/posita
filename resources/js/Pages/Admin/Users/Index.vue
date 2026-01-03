<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, h, computed } from 'vue'
import {
  Button,
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  Dialog,
  Input,
  Label,
  Badge,
  DataTable,
  DataTablePagination,
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from '@/Components/ui'
import {
  Plus,
  MoreHorizontal,
  Pencil,
  Trash2,
  Users,
  UserCheck,
  UserX,
  Shield,
  User
} from 'lucide-vue-next'

const props = defineProps({
  users: {
    type: Object, // Paginated object with data, links, meta
    default: () => ({ data: [], links: [], meta: {} }),
  },
})

// Normalize data - handle both paginated object and plain array
const usersData = computed(() => {
  if (Array.isArray(props.users)) {
    return props.users
  }
  return props.users?.data || []
})

const usersLinks = computed(() => props.users?.links || [])
const usersMeta = computed(() => props.users?.meta || {})

const showModal = ref(false)
const editingUser = ref(null)

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'employee',
  is_active: true,
})

const openCreateModal = () => {
  editingUser.value = null
  form.reset()
  showModal.value = true
}

const openEditModal = (user) => {
  editingUser.value = user
  form.name = user.name
  form.email = user.email
  form.password = ''
  form.password_confirmation = ''
  form.role = user.role
  form.is_active = user.is_active
  showModal.value = true
}

const submit = () => {
  if (editingUser.value) {
    form.put(`/admin/users/${editingUser.value.id}`, {
      onSuccess: () => {
        showModal.value = false
        form.reset()
      },
    })
  } else {
    form.post('/admin/users', {
      onSuccess: () => {
        showModal.value = false
        form.reset()
      },
    })
  }
}

const deleteUser = (user) => {
  if (confirm(`Hapus user "${user.name}"?`)) {
    router.delete(`/admin/users/${user.id}`)
  }
}

// Column definitions for DataTable
const columns = [
  {
    accessorKey: 'name',
    header: 'Nama',
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.original.name),
  },
  {
    accessorKey: 'email',
    header: 'Email',
    cell: ({ row }) => h('span', { class: 'text-muted-foreground' }, row.original.email),
  },
  {
    accessorKey: 'role',
    header: 'Role',
    cell: ({ row }) => {
      const role = row.original.role
      return h(Badge, { 
        variant: role === 'admin' ? 'default' : 'secondary',
        class: role === 'admin' ? 'bg-purple-100 text-purple-800 hover:bg-purple-100' : ''
      }, () => [
        h(role === 'admin' ? Shield : User, { class: 'w-3 h-3 mr-1' }),
        role === 'admin' ? 'Admin' : 'Karyawan'
      ])
    },
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) => {
      const active = row.original.is_active
      return h(Badge, { 
        variant: active ? 'success' : 'destructive',
        class: active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-100' : 'bg-red-100 text-red-800 hover:bg-red-100'
      }, () => [
        h(active ? UserCheck : UserX, { class: 'w-3 h-3 mr-1' }),
        active ? 'Aktif' : 'Nonaktif'
      ])
    },
  },
  {
    id: 'actions',
    header: '',
    cell: ({ row }) => {
      const user = row.original
      return h(DropdownMenu, null, {
        trigger: () => h(Button, { variant: 'ghost', size: 'icon', class: 'active-press' }, () => h(MoreHorizontal, { class: 'w-4 h-4' })),
        default: () => [
          h(DropdownMenuItem, { onClick: () => openEditModal(user) }, () => [
            h(Pencil, { class: 'w-4 h-4 mr-2' }),
            'Edit'
          ]),
          h(DropdownMenuSeparator),
          h(DropdownMenuItem, { destructive: true, onClick: () => deleteUser(user) }, () => [
            h(Trash2, { class: 'w-4 h-4 mr-2' }),
            'Hapus'
          ]),
        ]
      })
    },
  },
]
</script>

<template>
  <Head title="User Management" />

  <AdminLayout>
    <template #header>
      <h2 class="text-xl font-semibold text-foreground">User Management</h2>
    </template>

    <div class="space-y-6">
      <!-- Header Actions -->
      <div class="flex justify-between items-center">
        <p class="text-muted-foreground">
          Total: {{ usersData.length }} user{{ usersData.length !== 1 ? 's' : '' }}
        </p>
        <Button @click="openCreateModal" class="active-press">
          <Plus class="w-4 h-4 mr-2" />
          Tambah User
        </Button>
      </div>

      <!-- Users Table Card -->
      <Card class="overflow-hidden">
        <CardHeader class="border-b">
          <CardTitle class="flex items-center gap-2">
            <Users class="w-5 h-5" />
            Daftar User
          </CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <!-- Empty State -->
          <div v-if="usersData.length === 0" class="flex flex-col items-center justify-center py-16 px-4">
            <div class="w-20 h-20 rounded-full bg-muted flex items-center justify-center mb-4">
              <Users class="w-10 h-10 text-muted-foreground" />
            </div>
            <h3 class="text-lg font-medium text-foreground mb-1">Belum ada user</h3>
            <p class="text-sm text-muted-foreground mb-4">Tambahkan user baru untuk memulai</p>
            <Button @click="openCreateModal" variant="outline" class="active-press">
              <Plus class="w-4 h-4 mr-2" />
              Tambah User Pertama
            </Button>
          </div>

          <!-- Data Table -->
          <DataTable v-else :columns="columns" :data="usersData" />
        </CardContent>
      </Card>

      <!-- Pagination -->
      <DataTablePagination
        v-if="usersLinks.length > 0"
        :links="usersLinks"
        :meta="usersMeta"
      />
    </div>

    <!-- Create/Edit Modal -->
    <Dialog
      v-model:open="showModal"
      :title="editingUser ? 'Edit User' : 'Tambah User'"
      :description="editingUser ? 'Perbarui informasi user' : 'Isi form untuk menambah user baru'"
    >
      <form @submit.prevent="submit" class="space-y-4">
        <div class="space-y-2">
          <Label for="name" :error="!!form.errors.name">Nama</Label>
          <Input
            id="name"
            v-model="form.name"
            :error="!!form.errors.name"
            placeholder="Masukkan nama"
            required
          />
          <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
        </div>

        <div class="space-y-2">
          <Label for="email" :error="!!form.errors.email">Email</Label>
          <Input
            id="email"
            v-model="form.email"
            type="email"
            :error="!!form.errors.email"
            placeholder="email@example.com"
            required
          />
          <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
        </div>

        <div class="space-y-2">
          <Label for="password" :error="!!form.errors.password">
            Password {{ editingUser ? '(kosongkan jika tidak diubah)' : '' }}
          </Label>
          <Input
            id="password"
            v-model="form.password"
            type="password"
            :error="!!form.errors.password"
            placeholder="••••••••"
            :required="!editingUser"
          />
          <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
        </div>

        <div class="space-y-2">
          <Label for="password_confirmation">Konfirmasi Password</Label>
          <Input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            placeholder="••••••••"
            :required="!!form.password"
          />
        </div>

        <div class="space-y-2">
          <Label for="role">Role</Label>
          <select
            id="role"
            v-model="form.role"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          >
            <option value="admin">Admin</option>
            <option value="employee">Karyawan</option>
          </select>
        </div>

        <div class="flex items-center space-x-2">
          <input
            id="is_active"
            v-model="form.is_active"
            type="checkbox"
            class="rounded border-input text-primary focus:ring-primary"
          />
          <Label for="is_active" class="cursor-pointer">Aktif</Label>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <Button type="button" variant="outline" @click="showModal = false" class="active-press">
            Batal
          </Button>
          <Button type="submit" :loading="form.processing" class="active-press">
            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
          </Button>
        </div>
      </form>
    </Dialog>
  </AdminLayout>
</template>
