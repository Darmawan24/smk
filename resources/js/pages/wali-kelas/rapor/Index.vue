<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Manajemen Rapor
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Generate, submit, dan kelola rapor siswa
          </p>
        </div>
        <button 
          @click="showGenerateModal = true" 
          class="btn btn-primary"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Generate Rapor
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <FormField
            v-model="selectedKelas"
            type="select"
            label="Kelas"
            placeholder="Pilih Kelas"
            :options="kelasOptions"
            option-value="id"
            option-label="nama_kelas"
            @update:model-value="loadRapor"
          />
          <FormField
            v-model="selectedTahunAjaran"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="tahun"
            @update:model-value="loadRapor"
          />
          <FormField
            v-model="selectedStatus"
            type="select"
            label="Status"
            placeholder="Pilih Status"
            :options="statusOptions"
            @update:model-value="loadRapor"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data rapor...</p>
      </div>

      <!-- Rapor List -->
      <div v-else class="bg-white shadow rounded-lg">
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="w-16">No</th>
                <th>Nama Siswa</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th>Tahun Ajaran</th>
                <th class="text-center">Status</th>
                <th class="text-center">Tanggal Rapor</th>
                <th class="w-64">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(rapor, index) in raporList" :key="rapor.id" class="hover:bg-gray-50">
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <div class="flex items-center">
                    <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                      {{ rapor.siswa?.nama_lengkap?.charAt(0) }}
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-gray-900">{{ rapor.siswa?.nama_lengkap }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-sm text-gray-500">{{ rapor.siswa?.nis }}</td>
                <td>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ rapor.kelas?.nama_kelas }}
                  </span>
                </td>
                <td class="text-sm text-gray-500">
                  {{ rapor.tahun_ajaran?.tahun }} - Semester {{ rapor.tahun_ajaran?.semester }}
                </td>
                <td class="text-center">
                  <span :class="getStatusColor(rapor.status)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getStatusLabel(rapor.status) }}
                  </span>
                </td>
                <td class="text-center text-sm text-gray-500">
                  {{ formatDate(rapor.tanggal_rapor) }}
                </td>
                <td>
                  <div class="flex items-center space-x-2">
                    <button 
                      @click="previewRapor(rapor)" 
                      class="text-blue-600 hover:text-blue-900"
                      title="Preview"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                      </svg>
                    </button>
                    <button 
                      v-if="rapor.status === 'draft'"
                      @click="submitRapor(rapor)" 
                      class="text-green-600 hover:text-green-900"
                      title="Submit untuk Approval"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                    </button>
                    <button 
                      v-if="rapor.status === 'approved' || rapor.status === 'published'"
                      @click="downloadRapor(rapor)" 
                      class="text-purple-600 hover:text-purple-900"
                      title="Download PDF"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="raporList.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada rapor</h3>
          <p class="mt-1 text-sm text-gray-500">Klik tombol "Generate Rapor" untuk membuat rapor baru.</p>
        </div>
      </div>

      <!-- Generate Modal -->
      <Modal v-model:show="showGenerateModal" title="Generate Rapor" size="lg">
        <form @submit.prevent="generateRapor" class="space-y-4">
          <FormField
            v-model="generateForm.siswa_id"
            type="select"
            label="Siswa"
            placeholder="Pilih Siswa"
            :options="siswaOptions"
            option-value="id"
            option-label="nama_lengkap"
            required
            :error="errors.siswa_id"
          />
          <FormField
            v-model="generateForm.tahun_ajaran_id"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="tahun"
            required
            :error="errors.tahun_ajaran_id"
          />
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeGenerateModal" class="btn btn-secondary">
              Batal
            </button>
            <button type="submit" :disabled="generating" class="btn btn-primary">
              <svg v-if="generating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ generating ? 'Membuat...' : 'Generate' }}
            </button>
          </div>
        </form>
      </Modal>

      <!-- Preview Modal -->
      <Modal v-model:show="showPreviewModal" title="Preview Rapor" size="xl">
        <div v-if="previewData" class="space-y-6">
          <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-medium text-gray-900">{{ previewData.siswa?.nama_lengkap }}</h3>
            <p class="text-sm text-gray-500">NIS: {{ previewData.siswa?.nis }} | Kelas: {{ previewData.kelas?.nama_kelas }}</p>
          </div>
          
          <!-- Nilai -->
          <div v-if="previewData.nilai && previewData.nilai.length > 0">
            <h4 class="font-medium text-gray-900 mb-2">Nilai Akademik</h4>
            <div class="overflow-x-auto">
              <table class="table">
                <thead>
                  <tr>
                    <th>Mata Pelajaran</th>
                    <th class="text-center">Nilai Rapor</th>
                    <th class="text-center">Predikat</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="nilai in previewData.nilai" :key="nilai.id">
                    <td>{{ nilai.mata_pelajaran?.nama_mapel }}</td>
                    <td class="text-center">{{ nilai.nilai_rapor }}</td>
                    <td class="text-center">{{ nilai.predikat }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Kehadiran -->
          <div v-if="previewData.kehadiran">
            <h4 class="font-medium text-gray-900 mb-2">Kehadiran</h4>
            <div class="grid grid-cols-3 gap-4">
              <div class="bg-red-50 p-3 rounded">
                <div class="text-sm text-red-600">Sakit</div>
                <div class="text-xl font-bold text-red-900">{{ previewData.kehadiran.sakit || 0 }}</div>
              </div>
              <div class="bg-yellow-50 p-3 rounded">
                <div class="text-sm text-yellow-600">Izin</div>
                <div class="text-xl font-bold text-yellow-900">{{ previewData.kehadiran.izin || 0 }}</div>
              </div>
              <div class="bg-orange-50 p-3 rounded">
                <div class="text-sm text-orange-600">Tanpa Keterangan</div>
                <div class="text-xl font-bold text-orange-900">{{ previewData.kehadiran.tanpa_keterangan || 0 }}</div>
              </div>
            </div>
          </div>
        </div>
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
const kelasOptions = ref([])
const tahunAjaranOptions = ref([])
const siswaOptions = ref([])
const raporList = ref([])

// State
const loading = ref(false)
const generating = ref(false)
const selectedKelas = ref('')
const selectedTahunAjaran = ref('')
const selectedStatus = ref('')
const showGenerateModal = ref(false)
const showPreviewModal = ref(false)
const previewData = ref(null)

// Form
const generateForm = ref({
  siswa_id: '',
  tahun_ajaran_id: ''
})

const errors = ref({})

// Options
const statusOptions = [
  { value: '', label: 'Semua' },
  { value: 'draft', label: 'Draft' },
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'published', label: 'Published' }
]

// Methods
const fetchKelas = async () => {
  try {
    const response = await axios.get('/lookup/kelas')
    kelasOptions.value = response.data
  } catch (error) {
    console.error('Failed to fetch kelas:', error)
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
  if (!selectedKelas.value) {
    siswaOptions.value = []
    return
  }
  
  try {
    const response = await axios.get('/admin/siswa', {
      params: {
        kelas_id: selectedKelas.value,
        status: 'aktif',
        per_page: 1000
      }
    })
    siswaOptions.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to fetch siswa:', error)
  }
}

const loadRapor = async () => {
  try {
    loading.value = true
    const params = {}
    if (selectedTahunAjaran.value) {
      params.tahun_ajaran_id = selectedTahunAjaran.value
    }
    if (selectedStatus.value) {
      params.status = selectedStatus.value
    }
    
    const response = await axios.get('/wali-kelas/rapor', { params })
    raporList.value = response.data.data || response.data
  } catch (error) {
    toast.error('Gagal mengambil data rapor')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const generateRapor = async () => {
  errors.value = {}
  
  try {
    generating.value = true
    await axios.post('/wali-kelas/rapor/generate', generateForm.value)
    toast.success('Rapor berhasil dibuat')
    closeGenerateModal()
    await loadRapor()
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Gagal membuat rapor')
    }
  } finally {
    generating.value = false
  }
}

const submitRapor = async (rapor) => {
  if (!confirm('Apakah Anda yakin ingin mengirim rapor ini untuk persetujuan?')) return

  try {
    await axios.post(`/wali-kelas/rapor/${rapor.id}/submit`)
    toast.success('Rapor berhasil dikirim untuk persetujuan')
    await loadRapor()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal mengirim rapor')
  }
}

const previewRapor = async (rapor) => {
  try {
    const response = await axios.get(`/wali-kelas/rapor/${rapor.id}/preview`)
    previewData.value = response.data
    showPreviewModal.value = true
  } catch (error) {
    toast.error('Gagal mengambil preview rapor')
    console.error(error)
  }
}

const downloadRapor = async (rapor) => {
  try {
    const response = await axios.get(`/wali-kelas/rapor/${rapor.id}/download`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `rapor-${rapor.siswa?.nis}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    toast.success('Rapor berhasil diunduh')
  } catch (error) {
    toast.error('Gagal mengunduh rapor')
    console.error(error)
  }
}

const closeGenerateModal = () => {
  showGenerateModal.value = false
  generateForm.value = {
    siswa_id: '',
    tahun_ajaran_id: ''
  }
  errors.value = {}
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Draft',
    pending: 'Menunggu Persetujuan',
    approved: 'Disetujui',
    published: 'Diterbitkan'
  }
  return labels[status] || status
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'bg-gray-100 text-gray-800',
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    published: 'bg-blue-100 text-blue-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID')
}

watch(selectedKelas, () => {
  fetchSiswa()
})

// Lifecycle
onMounted(() => {
  fetchKelas()
  fetchTahunAjaran()
  loadRapor()
})
</script>
