<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Nilai Ekstrakurikuler
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Input dan kelola nilai ekstrakurikuler siswa
          </p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <FormField
            v-model="selectedEkstrakurikuler"
            type="select"
            label="Ekstrakurikuler"
            placeholder="Pilih Ekstrakurikuler"
            :options="ekstrakurikulerOptions"
            option-value="id"
            option-label="nama"
            @update:model-value="loadSiswa"
          />
          <FormField
            v-model="selectedTahunAjaran"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="tahun"
            @update:model-value="loadSiswa"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
      </div>

      <!-- No Selection State -->
      <div v-else-if="!selectedEkstrakurikuler || !selectedTahunAjaran" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih Ekstrakurikuler dan Tahun Ajaran</h3>
        <p class="mt-1 text-sm text-gray-500">Pilih ekstrakurikuler dan tahun ajaran untuk mulai input nilai.</p>
      </div>

      <!-- Nilai Table -->
      <div v-else class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">
                Nilai {{ selectedEkstrakurikulerName }}
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Tahun Ajaran: {{ selectedTahunAjaranName }}
              </p>
            </div>
            <button 
              @click="showAddModal = true" 
              class="btn btn-primary"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Tambah Nilai
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="w-16">No</th>
                <th>Nama Siswa</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th class="w-32">Predikat</th>
                <th class="w-64">Keterangan</th>
                <th class="w-32">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(nilai, index) in nilaiData" :key="nilai.id" class="hover:bg-gray-50">
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <div class="flex items-center">
                    <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                      {{ nilai.siswa?.nama_lengkap?.charAt(0) }}
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-gray-900">{{ nilai.siswa?.nama_lengkap }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-sm text-gray-500">{{ nilai.siswa?.nis }}</td>
                <td>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ nilai.siswa?.kelas?.nama_kelas }}
                  </span>
                </td>
                <td>
                  <span :class="getPredikatColor(nilai.predikat)" 
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ nilai.predikat }} - {{ getPredikatDescription(nilai.predikat) }}
                  </span>
                </td>
                <td class="text-sm text-gray-500">{{ nilai.keterangan || '-' }}</td>
                <td>
                  <div class="flex items-center space-x-2">
                    <button @click="editNilai(nilai)" class="text-blue-600 hover:text-blue-900">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="nilaiData.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada nilai</h3>
          <p class="mt-1 text-sm text-gray-500">Klik tombol "Tambah Nilai" untuk menambahkan nilai ekstrakurikuler.</p>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <Modal :show="showModal" @update:show="handleModalUpdate" :title="showEditModal ? 'Edit Nilai' : 'Tambah Nilai'" size="lg">
        <form @submit.prevent="submitForm" class="space-y-4">
          <FormField
            v-model="form.siswa_id"
            type="select"
            label="Siswa"
            placeholder="Pilih Siswa"
            :options="siswaOptions"
            option-value="id"
            option-label="nama_lengkap"
            required
            :error="errors.siswa_id"
            :disabled="showEditModal"
          />
          <FormField
            v-model="form.predikat"
            type="select"
            label="Predikat"
            placeholder="Pilih Predikat"
            :options="predikatOptions"
            required
            :error="errors.predikat"
          />
          <FormField
            v-model="form.keterangan"
            type="textarea"
            label="Keterangan"
            placeholder="Masukkan keterangan (opsional)"
            :rows="3"
            :error="errors.keterangan"
          />
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeModal" class="btn btn-secondary">
              Batal
            </button>
            <button type="submit" :disabled="submitting" class="btn btn-primary">
              <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'
import Modal from '../../../components/ui/Modal.vue'

const toast = useToast()

// Data
const ekstrakurikulerOptions = ref([])
const tahunAjaranOptions = ref([])
const siswaOptions = ref([])
const nilaiData = ref([])

// State
const loading = ref(false)
const submitting = ref(false)
const selectedEkstrakurikuler = ref('')
const selectedTahunAjaran = ref('')
const showAddModal = ref(false)
const showEditModal = ref(false)
const editingNilai = ref(null)

// Form
const form = ref({
  siswa_id: '',
  ekstrakurikuler_id: '',
  tahun_ajaran_id: '',
  predikat: '',
  keterangan: ''
})

const errors = ref({})

// Options
const predikatOptions = [
  { value: 'A', label: 'A - Sangat Baik' },
  { value: 'B', label: 'B - Baik' },
  { value: 'C', label: 'C - Cukup' },
  { value: 'D', label: 'D - Perlu Bimbingan' }
]

// Computed
const selectedEkstrakurikulerName = computed(() => {
  const ekskul = ekstrakurikulerOptions.value.find(e => e.id == selectedEkstrakurikuler.value)
  return ekskul?.nama || ''
})

const selectedTahunAjaranName = computed(() => {
  const ta = tahunAjaranOptions.value.find(t => t.id == selectedTahunAjaran.value)
  return ta ? `${ta.tahun} - Semester ${ta.semester}` : ''
})

const showModal = computed(() => {
  return showAddModal.value || showEditModal.value
})

// Methods
const fetchEkstrakurikuler = async () => {
  try {
    const response = await axios.get('/admin/ekstrakurikuler')
    ekstrakurikulerOptions.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to fetch ekstrakurikuler:', error)
  }
}

const fetchTahunAjaran = async () => {
  try {
    const response = await axios.get('/lookup/tahun-ajaran')
    tahunAjaranOptions.value = response.data
  } catch (error) {
    console.error('Failed to fetch tahun ajaran:', error)
  }
}

const fetchSiswa = async () => {
  try {
    const response = await axios.get('/admin/siswa', {
      params: { status: 'aktif', per_page: 1000 }
    })
    siswaOptions.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to fetch siswa:', error)
  }
}

const loadSiswa = async () => {
  if (!selectedEkstrakurikuler.value || !selectedTahunAjaran.value) {
    nilaiData.value = []
    return
  }

  try {
    loading.value = true
    // Get all siswa with nilai for this ekstrakurikuler
    const response = await axios.get('/admin/siswa', {
      params: { status: 'aktif', per_page: 1000 }
    })
    const allSiswa = response.data.data || response.data
    
    // Fetch nilai for each siswa
    const nilaiPromises = allSiswa.map(async (siswa) => {
      try {
        const nilaiRes = await axios.get(`/nilai-ekstrakurikuler/siswa/${siswa.id}`, {
          params: { tahun_ajaran_id: selectedTahunAjaran.value }
        })
        const nilai = nilaiRes.data.nilai_ekstrakurikuler?.find(
          n => n.ekstrakurikuler_id == selectedEkstrakurikuler.value
        )
        return nilai ? { ...nilai, siswa } : null
      } catch {
        return null
      }
    })
    
    const results = await Promise.all(nilaiPromises)
    nilaiData.value = results.filter(n => n !== null)
  } catch (error) {
    toast.error('Gagal mengambil data nilai')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const getPredikatColor = (predikat) => {
  const colors = {
    A: 'bg-green-100 text-green-800',
    B: 'bg-blue-100 text-blue-800',
    C: 'bg-yellow-100 text-yellow-800',
    D: 'bg-red-100 text-red-800'
  }
  return colors[predikat] || 'bg-gray-100 text-gray-800'
}

const getPredikatDescription = (predikat) => {
  const descriptions = {
    A: 'Sangat Baik',
    B: 'Baik',
    C: 'Cukup',
    D: 'Perlu Bimbingan'
  }
  return descriptions[predikat] || predikat
}

const editNilai = (nilai) => {
  editingNilai.value = nilai
  form.value = {
    siswa_id: nilai.siswa_id,
    ekstrakurikuler_id: nilai.ekstrakurikuler_id,
    tahun_ajaran_id: nilai.tahun_ajaran_id,
    predikat: nilai.predikat,
    keterangan: nilai.keterangan || ''
  }
  showEditModal.value = true
}

const handleModalUpdate = (value) => {
  if (value === false) {
    closeModal()
  }
}

const closeModal = () => {
  showAddModal.value = false
  showEditModal.value = false
  editingNilai.value = null
  form.value = {
    siswa_id: '',
    ekstrakurikuler_id: '',
    tahun_ajaran_id: '',
    predikat: '',
    keterangan: ''
  }
  errors.value = {}
}

const submitForm = async () => {
  errors.value = {}
  
  if (showEditModal.value && editingNilai.value) {
    // Update
    try {
      submitting.value = true
      await axios.put(`/nilai-ekstrakurikuler/${editingNilai.value.id}`, {
        predikat: form.value.predikat,
        keterangan: form.value.keterangan
      })
      toast.success('Nilai berhasil diperbarui')
      closeModal()
      await loadSiswa()
    } catch (error) {
      if (error.response?.data?.errors) {
        errors.value = error.response.data.errors
      } else {
        toast.error(error.response?.data?.message || 'Gagal memperbarui nilai')
      }
    } finally {
      submitting.value = false
    }
  } else {
    // Create
    try {
      submitting.value = true
      form.value.ekstrakurikuler_id = selectedEkstrakurikuler.value
      form.value.tahun_ajaran_id = selectedTahunAjaran.value
      
      await axios.post('/nilai-ekstrakurikuler', form.value)
      toast.success('Nilai berhasil ditambahkan')
      closeModal()
      await loadSiswa()
    } catch (error) {
      if (error.response?.data?.errors) {
        errors.value = error.response.data.errors
      } else {
        toast.error(error.response?.data?.message || 'Gagal menambahkan nilai')
      }
    } finally {
      submitting.value = false
    }
  }
}

// Lifecycle
onMounted(() => {
  fetchEkstrakurikuler()
  fetchTahunAjaran()
  fetchSiswa()
})
</script>

