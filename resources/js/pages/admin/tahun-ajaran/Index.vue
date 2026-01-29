<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <DataTable
        title="Data Tahun Ajaran"
        description="Kelola data tahun ajaran"
        :data="tahunAjaran"
        :columns="columns"
        :loading="loading"
        empty-message="Belum ada data tahun ajaran"
        empty-description="Mulai dengan menambahkan tahun ajaran baru."
        :searchable="true"
        @search="handleSearch"
      >
        <template #actions>
          <button @click="showForm = true" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Tahun Ajaran
          </button>
        </template>

        <template #filters>
          <FormField
            v-model="filters.is_active"
            type="select"
            placeholder="Status Aktif"
            :options="statusOptions"
            @update:model-value="fetchTahunAjaran"
          />
        </template>

        <template #cell-tahun="{ item }">
          <div class="flex items-center">
            <div class="h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-medium">
              {{ item.tahun.charAt(0) }}
            </div>
            <div class="ml-4">
              <div class="text-sm font-medium text-gray-900">{{ item.tahun }}</div>
              <div class="text-sm text-gray-500">Semester {{ item.semester }}</div>
            </div>
          </div>
        </template>

        <template #cell-status="{ item }">
          <span :class="getStatusBadge(item.is_active)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
            {{ item.is_active ? 'Aktif' : 'Tidak Aktif' }}
          </span>
        </template>

        <template #row-actions="{ item }">
          <div class="flex items-center space-x-2">
            <button @click="editTahunAjaran(item)" class="text-blue-600 hover:text-blue-900" title="Edit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </button>
            <button
              v-if="!item.is_active"
              @click="deleteTahunAjaran(item)"
              class="text-red-600 hover:text-red-900"
              title="Hapus"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
        </template>
      </DataTable>

      <!-- Form Modal -->
      <Modal v-model:show="showForm" :title="isEditing ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran'" size="lg">
        <form @submit.prevent="submitForm" id="tahun-ajaran-form" class="space-y-4">
          <FormField
            v-model="form.tahun"
            label="Tahun Ajaran"
            placeholder="Contoh: 2024/2025"
            required
            :error="errors.tahun"
            :disabled="isEditing"
          />
          
          <!-- Active Semester Selection -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Semester Aktif *</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  v-model="form.active_semester"
                  type="radio"
                  value="1"
                  class="border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">Semester 1</span>
              </label>
              <label class="flex items-center">
                <input
                  v-model="form.active_semester"
                  type="radio"
                  value="2"
                  class="border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="ml-2 text-sm text-gray-700">Semester 2</span>
              </label>
            </div>
            <p class="mt-1 text-xs text-gray-500">
              <span v-if="!isEditing">Pilih semester yang akan diaktifkan. Sistem hanya membuat semester yang belum ada (jika sudah ada Semester 1, yang ditambah hanya Semester 2).</span>
              <span v-if="isEditing">Pilih semester yang akan diaktifkan untuk tahun ajaran ini.</span>
              Jika diaktifkan, tahun ajaran lain akan otomatis dinonaktifkan.
            </p>
            <p v-if="errors.active_semester" class="mt-1 text-sm text-red-600">{{ errors.active_semester }}</p>
          </div>
        </form>

        <template #footer>
          <button type="submit" form="tahun-ajaran-form" :disabled="submitting" class="btn btn-primary">
            <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ submitting ? 'Menyimpan...' : 'Simpan' }}
          </button>
          <button type="button" @click="closeForm" class="btn btn-secondary mr-3">Batal</button>
        </template>
      </Modal>

      <!-- Confirmation Dialogs -->
      <ConfirmDialog
        v-model:show="showDeleteConfirm"
        title="Hapus Tahun Ajaran"
        :message="`Apakah Anda yakin ingin menghapus tahun ajaran ${selectedTahunAjaran?.tahun}?`"
        confirm-text="Ya, Hapus"
        type="error"
        :loading="deleting"
        @confirm="confirmDelete"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import DataTable from '../../../components/ui/DataTable.vue'
import Modal from '../../../components/ui/Modal.vue'
import FormField from '../../../components/ui/FormField.vue'
import ConfirmDialog from '../../../components/ui/ConfirmDialog.vue'

const toast = useToast()

// Data
const tahunAjaran = ref([])
const loading = ref(true)
const submitting = ref(false)
const deleting = ref(false)

// Form state
const showForm = ref(false)
const showDeleteConfirm = ref(false)
const isEditing = ref(false)
const selectedTahunAjaran = ref(null)

// Form data
const form = reactive({
  tahun: '',
  is_active: false,
  active_semester: '1'
})

const errors = ref({})

// Filters
const filters = reactive({
  search: '',
  is_active: ''
})

// Options
const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'true', label: 'Aktif' },
  { value: 'false', label: 'Tidak Aktif' }
]

// Table columns
const columns = [
  { key: 'tahun', label: 'Tahun Ajaran', sortable: true },
  { key: 'status', label: 'Status' }
]

// Methods
const fetchTahunAjaran = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    if (filters.search) params.append('search', filters.search)
    if (filters.is_active) params.append('is_active', filters.is_active)
    params.append('per_page', 100)

    const response = await axios.get(`/admin/tahun-ajaran?${params}`)
    if (response.data.data) {
      tahunAjaran.value = response.data.data
    } else if (Array.isArray(response.data)) {
      tahunAjaran.value = response.data
    } else {
      tahunAjaran.value = []
    }
  } catch (error) {
    toast.error('Gagal mengambil data tahun ajaran')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  form.tahun = ''
  form.is_active = false
  form.active_semester = '1'
  errors.value = {}
  isEditing.value = false
  selectedTahunAjaran.value = null
}

const closeForm = () => {
  showForm.value = false
  resetForm()
}

const editTahunAjaran = (item) => {
  isEditing.value = true
  selectedTahunAjaran.value = item
  form.tahun = item.tahun || ''
  form.active_semester = item.semester || '1'
  showForm.value = true
}

const submitForm = async () => {
  try {
    submitting.value = true
    errors.value = {}

    // Validation
    if (!form.active_semester) {
      errors.value.active_semester = 'Pilih semester yang aktif'
      toast.error('Pilih semester yang aktif')
      submitting.value = false
      return
    }

    if (isEditing.value) {
      // Edit mode - update active semester
      const url = `/admin/tahun-ajaran/${selectedTahunAjaran.value.id}`
      await axios.put(url, {
        active_semester: form.active_semester
      })
    } else {
      // Create mode - always create both semesters, only one is active
      await axios.post('/admin/tahun-ajaran', {
        tahun: form.tahun,
        active_semester: form.active_semester
      })
    }

    toast.success(`Tahun ajaran berhasil ${isEditing.value ? 'diperbarui' : 'ditambahkan'}`)
    closeForm()
    fetchTahunAjaran()
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      toast.error('Validasi gagal. Periksa kembali data yang diinput.')
    } else {
      toast.error(error.response?.data?.message || 'Terjadi kesalahan saat menyimpan data')
    }
  } finally {
    submitting.value = false
  }
}

const deleteTahunAjaran = (item) => {
  selectedTahunAjaran.value = item
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  try {
    deleting.value = true
    await axios.delete(`/admin/tahun-ajaran/${selectedTahunAjaran.value.id}`)
    toast.success('Tahun ajaran berhasil dihapus')
    showDeleteConfirm.value = false
    fetchTahunAjaran()
  } catch (error) {
    const message = error.response?.data?.message || 'Gagal menghapus tahun ajaran'
    toast.error(message)
  } finally {
    deleting.value = false
  }
}


const getStatusBadge = (isActive) => {
  return isActive
    ? 'bg-green-100 text-green-800'
    : 'bg-gray-100 text-gray-800'
}

const handleSearch = (searchTerm) => {
  filters.search = searchTerm
  fetchTahunAjaran()
}

// Lifecycle
onMounted(() => {
  fetchTahunAjaran()
})
</script>

