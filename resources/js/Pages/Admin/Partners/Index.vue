<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, h, computed } from 'vue'
import { formatMoney } from '@/utils/formatMoney'
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
  Phone,
  Package,
  CheckCircle,
  XCircle,
  Loader2
} from 'lucide-vue-next'

const props = defineProps({
  partners: {
    type: Object, // Paginated or array
    default: () => ({ data: [], links: [], meta: {} }),
  },
})

// Normalize data
const partnersData = computed(() => {
  if (Array.isArray(props.partners)) return props.partners
  return props.partners?.data || []
})

const partnersLinks = computed(() => props.partners?.links || [])
const partnersMeta = computed(() => props.partners?.meta || {})

const showModal = ref(false)
const editingPartner = ref(null)

const form = useForm({
  name: '',
  phone: '',
  address: '',
  is_active: true,
  product_templates: [],
})

const openCreateModal = () => {
  editingPartner.value = null
  form.reset()
  form.product_templates = []
  showModal.value = true
}

const openEditModal = (partner) => {
  editingPartner.value = partner
  form.name = partner.name
  form.phone = partner.phone || ''
  form.address = partner.address || ''
  form.is_active = partner.is_active
  form.product_templates = partner.product_templates?.map(t => ({
    id: t.id,
    name: t.name,
    base_price: parseFloat(t.base_price),
    default_selling_price: parseFloat(t.default_selling_price),
  })) || []
  showModal.value = true
}

const addTemplate = () => {
  form.product_templates.push({
    id: null,
    name: '',
    base_price: '',
    default_selling_price: '',
  })
}

const removeTemplate = (index) => {
  form.product_templates.splice(index, 1)
}

const submit = () => {
  if (editingPartner.value) {
    form.put(`/admin/partners/${editingPartner.value.id}`, {
      onSuccess: () => {
        showModal.value = false
        form.reset()
      },
    })
  } else {
    form.post('/admin/partners', {
      onSuccess: () => {
        showModal.value = false
        form.reset()
      },
    })
  }
}

const deletePartner = (partner) => {
  if (confirm(`Hapus partner "${partner.name}"?`)) {
    router.delete(`/admin/partners/${partner.id}`)
  }
}

// Column definitions
const columns = [
  {
    accessorKey: 'name',
    header: 'Nama',
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.original.name),
  },
  {
    accessorKey: 'phone',
    header: 'Telepon',
    cell: ({ row }) => h('div', { class: 'flex items-center gap-1.5 text-muted-foreground' }, [
      h(Phone, { class: 'w-3.5 h-3.5' }),
      row.original.phone || '-'
    ]),
  },
  {
    accessorKey: 'product_templates',
    header: 'Template',
    cell: ({ row }) => {
      const count = row.original.product_templates?.length || 0
      return h(Badge, { variant: 'secondary' }, () => [
        h(Package, { class: 'w-3 h-3 mr-1' }),
        `${count} produk`
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
        h(active ? CheckCircle : XCircle, { class: 'w-3 h-3 mr-1' }),
        active ? 'Aktif' : 'Nonaktif'
      ])
    },
  },
  {
    id: 'actions',
    header: '',
    cell: ({ row }) => {
      const partner = row.original
      return h(DropdownMenu, null, {
        trigger: () => h(Button, { variant: 'ghost', size: 'icon', class: 'active-press' }, () => h(MoreHorizontal, { class: 'w-4 h-4' })),
        default: () => [
          h(DropdownMenuItem, { onClick: () => openEditModal(partner) }, () => [
            h(Pencil, { class: 'w-4 h-4 mr-2' }),
            'Edit'
          ]),
          h(DropdownMenuSeparator),
          h(DropdownMenuItem, { destructive: true, onClick: () => deletePartner(partner) }, () => [
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
  <Head title="Partners" />

  <AdminLayout>
    <template #header>
      <h2 class="text-xl font-semibold text-foreground">Partners (Penyetok)</h2>
    </template>

    <div class="space-y-6">
      <!-- Header Actions -->
      <div class="flex justify-between items-center">
        <p class="text-muted-foreground">
          Total: {{ partnersData.length }} partner{{ partnersData.length !== 1 ? 's' : '' }}
        </p>
        <Button @click="openCreateModal" class="active-press">
          <Plus class="w-4 h-4 mr-2" />
          Tambah Partner
        </Button>
      </div>

      <!-- Partners Table Card -->
      <Card class="overflow-hidden">
        <CardHeader class="border-b">
          <CardTitle class="flex items-center gap-2">
            <Users class="w-5 h-5" />
            Daftar Partner
          </CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <!-- Empty State -->
          <div v-if="partnersData.length === 0" class="flex flex-col items-center justify-center py-16 px-4">
            <div class="w-20 h-20 rounded-full bg-muted flex items-center justify-center mb-4">
              <Users class="w-10 h-10 text-muted-foreground" />
            </div>
            <h3 class="text-lg font-medium text-foreground mb-1">Belum ada partner</h3>
            <p class="text-sm text-muted-foreground mb-4">Tambahkan partner baru untuk memulai</p>
            <Button @click="openCreateModal" variant="outline" class="active-press">
              <Plus class="w-4 h-4 mr-2" />
              Tambah Partner Pertama
            </Button>
          </div>

          <!-- Data Table -->
          <DataTable v-else :columns="columns" :data="partnersData" />
        </CardContent>
      </Card>

      <!-- Pagination -->
      <DataTablePagination
        v-if="partnersLinks.length > 0"
        :links="partnersLinks"
        :meta="partnersMeta"
      />
    </div>

    <!-- Create/Edit Modal -->
    <Dialog
      v-model:open="showModal"
      :title="editingPartner ? 'Edit Partner' : 'Tambah Partner'"
      :description="editingPartner ? 'Perbarui informasi partner' : 'Isi form untuk menambah partner baru'"
    >
      <form @submit.prevent="submit" class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 space-y-2">
            <Label for="name" :error="!!form.errors.name">Nama</Label>
            <Input
              id="name"
              v-model="form.name"
              :error="!!form.errors.name"
              placeholder="Nama partner"
              required
            />
            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
          </div>

          <div class="space-y-2">
            <Label for="phone">Telepon</Label>
            <Input id="phone" v-model="form.phone" placeholder="08xxxxxxxxxx" />
          </div>

          <div class="flex items-end">
            <div class="flex items-center space-x-2">
              <input
                id="is_active"
                v-model="form.is_active"
                type="checkbox"
                class="rounded border-input text-primary focus:ring-primary"
              />
              <Label for="is_active" class="cursor-pointer">Aktif</Label>
            </div>
          </div>
        </div>

        <div class="space-y-2">
          <Label for="address">Alamat</Label>
          <textarea
            id="address"
            v-model="form.address"
            rows="2"
            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            placeholder="Alamat lengkap"
          ></textarea>
        </div>

        <!-- Product Templates Section -->
        <div class="border-t pt-4">
          <div class="flex justify-between items-center mb-4">
            <h4 class="font-medium text-foreground">Template Produk</h4>
            <Button type="button" variant="outline" size="sm" @click="addTemplate" class="active-press">
              <Plus class="w-4 h-4 mr-1" />
              Tambah
            </Button>
          </div>

          <div v-if="form.product_templates.length === 0" class="text-center py-4 text-muted-foreground text-sm">
            Belum ada template produk
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(template, index) in form.product_templates"
              :key="index"
              class="bg-muted/50 rounded-lg p-3"
            >
              <div class="flex items-start gap-3">
                <div class="flex-1 grid grid-cols-3 gap-2">
                  <div>
                    <label class="block text-xs text-muted-foreground mb-1">Nama Produk</label>
                    <Input
                      v-model="template.name"
                      placeholder="Risoles"
                      class="h-8 text-sm"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-muted-foreground mb-1">Harga Beli</label>
                    <Input
                      v-model="template.base_price"
                      type="number"
                      min="0"
                      placeholder="0"
                      class="h-8 text-sm"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-muted-foreground mb-1">Harga Jual</label>
                    <Input
                      v-model="template.default_selling_price"
                      type="number"
                      min="0"
                      placeholder="0"
                      class="h-8 text-sm"
                      required
                    />
                  </div>
                </div>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  class="mt-5 text-destructive hover:text-destructive"
                  @click="removeTemplate(index)"
                >
                  <Trash2 class="w-4 h-4" />
                </Button>
              </div>
              <div v-if="template.base_price && template.default_selling_price" class="mt-2 text-xs text-muted-foreground">
                Profit: {{ formatMoney(template.default_selling_price - template.base_price) }}
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
          <Button type="button" variant="outline" @click="showModal = false" class="active-press">
            Batal
          </Button>
          <Button type="submit" :disabled="form.processing" class="active-press">
            <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
          </Button>
        </div>
      </form>
    </Dialog>
  </AdminLayout>
</template>
