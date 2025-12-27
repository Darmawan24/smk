<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Rapor Saya
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Lihat rapor hasil belajar Anda
          </p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
      <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div 
          v-for="rapor in raporList" 
          :key="rapor.id" 
          class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
        >
          <div class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-2 mb-2">
                  <span :class="getStatusColor(rapor.status)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getStatusLabel(rapor.status) }}
                  </span>
                </div>
                <h3 class="text-lg font-medium text-gray-900">
                  Tahun Ajaran {{ rapor.tahun_ajaran?.tahun }}
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                  Semester {{ rapor.tahun_ajaran?.semester }} | Kelas {{ rapor.kelas?.nama_kelas }}
                </p>
                <p class="text-sm text-gray-500 mt-1">
                  Tanggal: {{ formatDate(rapor.tanggal_rapor) }}
                </p>
                <div v-if="rapor.approver" class="mt-2 text-xs text-gray-500">
                  Disetujui oleh: {{ rapor.approver?.name }}
                </div>
              </div>
            </div>
            <div class="mt-6 flex items-center justify-between">
              <button 
                @click="viewRapor(rapor)" 
                class="btn btn-sm btn-primary"
              >
                Lihat Detail
              </button>
              <button 
                v-if="rapor.status === 'approved' || rapor.status === 'published'"
                @click="downloadRapor(rapor)" 
                class="btn btn-sm btn-secondary"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Download PDF
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && raporList.length === 0" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada rapor</h3>
        <p class="mt-1 text-sm text-gray-500">Rapor akan muncul setelah wali kelas membuat dan menyetujui rapor Anda.</p>
      </div>

      <!-- Detail Modal -->
      <Modal v-model:show="showDetailModal" title="Detail Rapor" size="xl">
        <div v-if="selectedRapor" class="space-y-6">
          <!-- Header -->
          <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-medium text-gray-900">Rapor Hasil Belajar</h3>
            <p class="text-sm text-gray-500 mt-1">
              Tahun Ajaran {{ selectedRapor.tahun_ajaran?.tahun }} - Semester {{ selectedRapor.tahun_ajaran?.semester }}
            </p>
          </div>

          <!-- Nilai Akademik -->
          <div v-if="selectedRapor.nilai && selectedRapor.nilai.length > 0">
            <h4 class="font-medium text-gray-900 mb-3">Nilai Akademik</h4>
            <div class="overflow-x-auto">
              <table class="table">
                <thead>
                  <tr>
                    <th>Mata Pelajaran</th>
                    <th class="text-center">Nilai Rapor</th>
                    <th class="text-center">Predikat</th>
                    <th>Deskripsi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="nilai in selectedRapor.nilai" :key="nilai.id">
                    <td>
                      <div class="font-medium text-gray-900">{{ nilai.mata_pelajaran?.nama_mapel }}</div>
                    </td>
                    <td class="text-center">
                      <span :class="getNilaiColor(nilai.nilai_rapor)"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                        {{ nilai.nilai_rapor }}
                      </span>
                    </td>
                    <td class="text-center">
                      <span :class="getPredikatColor(nilai.predikat)"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                        {{ nilai.predikat }}
                      </span>
                    </td>
                    <td class="text-sm text-gray-500">{{ nilai.deskripsi || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Kehadiran -->
          <div v-if="selectedRapor.kehadiran">
            <h4 class="font-medium text-gray-900 mb-3">Kehadiran</h4>
            <div class="grid grid-cols-3 gap-4">
              <div class="bg-red-50 p-4 rounded-lg">
                <div class="text-sm text-red-600 font-medium">Sakit</div>
                <div class="text-2xl font-bold text-red-900">{{ selectedRapor.kehadiran.sakit || 0 }}</div>
              </div>
              <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-sm text-yellow-600 font-medium">Izin</div>
                <div class="text-2xl font-bold text-yellow-900">{{ selectedRapor.kehadiran.izin || 0 }}</div>
              </div>
              <div class="bg-orange-50 p-4 rounded-lg">
                <div class="text-sm text-orange-600 font-medium">Tanpa Keterangan</div>
                <div class="text-2xl font-bold text-orange-900">{{ selectedRapor.kehadiran.tanpa_keterangan || 0 }}</div>
              </div>
            </div>
          </div>

          <!-- Catatan Akademik -->
          <div v-if="selectedRapor.catatan_akademik">
            <h4 class="font-medium text-gray-900 mb-3">Catatan Akademik</h4>
            <div class="bg-gray-50 p-4 rounded-lg">
              <p class="text-sm text-gray-700">{{ selectedRapor.catatan_akademik.catatan }}</p>
            </div>
          </div>
        </div>
      </Modal>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'
import Modal from '../../../components/ui/Modal.vue'

const toast = useToast()

// Data
const tahunAjaranOptions = ref([])
const raporList = ref([])
const selectedRapor = ref(null)

// State
const loading = ref(false)
const selectedTahunAjaran = ref('')
const selectedStatus = ref('')
const showDetailModal = ref(false)

// Options
const statusOptions = [
  { value: '', label: 'Semua' },
  { value: 'draft', label: 'Draft' },
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Disetujui' },
  { value: 'published', label: 'Diterbitkan' }
]

// Methods
const fetchTahunAjaran = async () => {
  try {
    const response = await axios.get('/lookup/tahun-ajaran')
    tahunAjaranOptions.value = response.data
  } catch (error) {
    console.error('Failed to fetch tahun ajaran:', error)
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
    
    const response = await axios.get('/siswa/rapor', { params })
    raporList.value = response.data.data || response.data
  } catch (error) {
    toast.error('Gagal mengambil data rapor')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const viewRapor = async (rapor) => {
  try {
    const response = await axios.get(`/siswa/rapor/${rapor.id}`)
    selectedRapor.value = response.data
    showDetailModal.value = true
  } catch (error) {
    toast.error('Gagal mengambil detail rapor')
    console.error(error)
  }
}

const downloadRapor = async (rapor) => {
  try {
    const response = await axios.get(`/siswa/rapor/${rapor.id}/download`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `rapor-${rapor.tahun_ajaran?.tahun}-semester-${rapor.tahun_ajaran?.semester}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    toast.success('Rapor berhasil diunduh')
  } catch (error) {
    toast.error('Gagal mengunduh rapor')
    console.error(error)
  }
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

const getNilaiColor = (nilai) => {
  if (!nilai) return 'bg-gray-100 text-gray-800'
  const num = parseFloat(nilai)
  if (num >= 90) return 'bg-green-100 text-green-800'
  if (num >= 80) return 'bg-blue-100 text-blue-800'
  if (num >= 70) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
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

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID')
}

// Lifecycle
onMounted(() => {
  fetchTahunAjaran()
  loadRapor()
})
</script>
