<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <DataTable
        title="Data P5 (Projek Penguatan Profil Pelajar Pancasila)"
        description="Kelola data projek P5"
        :data="p5"
        :columns="columns"
        :loading="loading"
        empty-message="Belum ada data P5"
        empty-description="Mulai dengan menambahkan data P5 baru."
        :searchable="true"
        @search="handleSearch"
      >
        <template #actions>
          <button @click="showForm = true" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah P5
          </button>
        </template>

        <template #filters>
          <FormField
            v-model="filters.tahun_ajaran_id"
            type="select"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranFilterOptions"
            option-value="id"
            option-label="label"
            @update:model-value="fetchP5"
          />
          <FormField
            v-model="filters.koordinator_id"
            type="select"
            placeholder="Pilih Koordinator"
            :options="guruFilterOptions"
            option-value="id"
            option-label="nama_lengkap"
            @update:model-value="fetchP5"
          />
        </template>

        <template #cell-tema="{ item }">
          <div class="text-sm">
            <div class="font-medium text-gray-900">{{ item.tema }}</div>
            <div class="text-gray-500 text-xs line-clamp-2 mt-1">{{ item.deskripsi }}</div>
          </div>
        </template>

        <template #cell-koordinator="{ item }">
          <div class="text-sm text-gray-900">
            {{ item.koordinator?.nama_lengkap || '-' }}
          </div>
        </template>

        <template #cell-tahun_ajaran="{ item }">
          <div class="text-sm text-gray-900">
            {{ item.tahun_ajaran?.tahun || '-' }}
          </div>
        </template>

        <template #row-actions="{ item }">
          <div class="flex items-center space-x-2">
            <button @click="editP5(item)" class="text-blue-600 hover:text-blue-900" title="Edit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </button>
            <button @click="deleteP5(item)" class="text-red-600 hover:text-red-900" title="Hapus">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
        </template>
      </DataTable>

      <!-- Form Modal -->
      <Modal v-model:show="showForm" :title="isEditing ? 'Edit P5' : 'Tambah P5'" size="lg">
        <form @submit.prevent="submitForm" id="p5-form" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField
              v-model="form.tahun_ajaran_id"
              type="select"
              label="Tahun Ajaran"
              placeholder="Pilih tahun ajaran"
              :options="tahunAjaranOptions"
              required
              :error="errors.tahun_ajaran_id"
            />
            <FormField
              v-model="form.koordinator_id"
              type="select"
              label="Koordinator"
              placeholder="Pilih koordinator"
              :options="guruOptions"
              option-value="id"
              option-label="nama_lengkap"
              required
              :error="errors.koordinator_id"
            />
          </div>

          <FormField
            v-model="form.tema"
            type="text"
            label="Tema"
            placeholder="Masukkan tema projek"
            required
            :error="errors.tema"
          />

          <FormField
            v-model="form.deskripsi"
            type="textarea"
            label="Deskripsi"
            placeholder="Masukkan deskripsi projek"
            required
            :error="errors.deskripsi"
            rows="4"
          />

          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeForm" class="btn btn-secondary">
              Batal
            </button>
            <button type="submit" :disabled="submitting" class="btn btn-primary">
              {{ submitting ? 'Menyimpan...' : (isEditing ? 'Perbarui' : 'Simpan') }}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import DataTable from '../../../components/ui/DataTable.vue'
import Modal from '../../../components/ui/Modal.vue'
import FormField from '../../../components/ui/FormField.vue'

const toast = useToast()

const p5 = ref([])
const loading = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const errors = ref({})

const filters = ref({
  tahun_ajaran_id: '',
  koordinator_id: ''
})

const form = ref({
  id: null,
  tema: '',
  deskripsi: '',
  koordinator_id: '',
  tahun_ajaran_id: ''
})

const guruOptions = ref([])
const tahunAjaranOptions = ref([])

const columns = [
  { key: 'tema', label: 'Tema' },
  { key: 'koordinator', label: 'Koordinator' },
  { key: 'tahun_ajaran', label: 'Tahun Ajaran' },
  { key: 'actions', label: 'Aksi' }
]

const tahunAjaranFilterOptions = computed(() => [
  { id: '', label: 'Semua Tahun Ajaran' },
  ...tahunAjaranOptions.value.map(ta => ({
    id: ta.id,
    label: ta.tahun
  }))
])

const guruFilterOptions = computed(() => [
  { id: '', nama_lengkap: 'Semua Koordinator' },
  ...guruOptions.value
])

const fetchP5 = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.tahun_ajaran_id) params.append('tahun_ajaran_id', filters.value.tahun_ajaran_id)
    if (filters.value.koordinator_id) params.append('koordinator_id', filters.value.koordinator_id)
    if (filters.value.search) params.append('search', filters.value.search)

    const response = await axios.get(`/admin/p5?${params.toString()}`)
    // Handle paginated response
    if (response.data.data) {
      p5.value = response.data
    } else if (Array.isArray(response.data)) {
      p5.value = { data: response.data }
    } else {
      p5.value = response.data
    }
  } catch (error) {
    console.error('Error fetching P5:', error)
    toast.error('Gagal mengambil data P5')
  } finally {
    loading.value = false
  }
}

const fetchGuru = async () => {
  try {
    const response = await axios.get('/lookup/guru')
    guruOptions.value = response.data
  } catch (error) {
    console.error('Error fetching guru:', error)
  }
}

const fetchTahunAjaran = async () => {
  try {
    const response = await axios.get('/lookup/tahun-ajaran')
    tahunAjaranOptions.value = response.data
  } catch (error) {
    console.error('Error fetching tahun ajaran:', error)
  }
}

const handleSearch = (searchTerm) => {
  filters.value.search = searchTerm
  fetchP5()
}

const editP5 = (item) => {
  isEditing.value = true
  form.value = {
    id: item.id,
    tema: item.tema,
    deskripsi: item.deskripsi,
    koordinator_id: item.koordinator_id,
    tahun_ajaran_id: item.tahun_ajaran_id
  }
  showForm.value = true
}

const deleteP5 = async (item) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus projek P5 "${item.tema}"?`)) {
    return
  }

  try {
    await axios.delete(`/admin/p5/${item.id}`)
    toast.success('Projek P5 berhasil dihapus')
    fetchP5()
  } catch (error) {
    console.error('Error deleting P5:', error)
    toast.error(error.response?.data?.message || 'Gagal menghapus projek P5')
  }
}

const closeForm = () => {
  showForm.value = false
  isEditing.value = false
  errors.value = {}
  form.value = {
    id: null,
    tema: '',
    deskripsi: '',
    koordinator_id: '',
    tahun_ajaran_id: ''
  }
}

const submitForm = async () => {
  errors.value = {}
  submitting.value = true

  try {
    if (isEditing.value) {
      await axios.put(`/admin/p5/${form.value.id}`, form.value)
      toast.success('Projek P5 berhasil diperbarui')
    } else {
      await axios.post('/admin/p5', form.value)
      toast.success('Projek P5 berhasil ditambahkan')
    }

    closeForm()
    fetchP5()
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Gagal menyimpan projek P5')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchP5()
  fetchGuru()
  fetchTahunAjaran()
})
</script>

