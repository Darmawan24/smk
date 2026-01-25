<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Approve Rapor P5
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Tinjau dan setujui rapor P5 (Projek Penguatan Profil Pelajar Pancasila) siswa
          </p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <FormField
            v-model="filters.tahun_ajaran_id"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="full_description"
            @update:model-value="fetchRapor"
          />
          <FormField
            v-model="filters.kelas_id"
            type="select"
            label="Kelas"
            placeholder="Pilih Kelas"
            :options="kelasOptions"
            option-value="id"
            option-label="nama_kelas"
            @update:model-value="fetchRapor"
          />
          <FormField
            v-model="filters.search"
            type="text"
            label="Cari Siswa"
            placeholder="Cari berdasarkan nama atau NIS"
            @update:model-value="handleSearch"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data rapor P5...</p>
      </div>

      <!-- Rapor List -->
      <div v-else class="bg-white shadow rounded-lg overflow-hidden">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Siswa</th>
              <th>NISN</th>
              <th>NIS</th>
              <th>Kelas</th>
              <th>Projek P5</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(siswa, index) in filteredSiswaList" :key="siswa.id" class="hover:bg-gray-50">
              <td class="text-center">{{ index + 1 }}</td>
              <td>
                <div class="flex items-center">
                  <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                    {{ siswa.nama_lengkap?.charAt(0) }}
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">{{ siswa.nama_lengkap }}</div>
                  </div>
                </div>
              </td>
              <td class="text-sm text-gray-900">{{ siswa.nisn || '-' }}</td>
              <td class="text-sm text-gray-900">{{ siswa.nis || '-' }}</td>
              <td>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ siswa.kelas?.nama_kelas }}
                </span>
              </td>
              <td>
                <div class="text-sm text-gray-900">
                  <span class="font-medium">{{ siswa.total_p5_projects || 0 }}</span> Projek
                </div>
                <div v-if="siswa.p5_projects && siswa.p5_projects.length > 0" class="text-xs text-gray-500 mt-1">
                  <div v-for="(project, idx) in siswa.p5_projects.slice(0, 2)" :key="project.id">
                    • {{ project.tema }}
                  </div>
                  <div v-if="siswa.p5_projects.length > 2" class="text-gray-400">
                    +{{ siswa.p5_projects.length - 2 }} lainnya
                  </div>
                </div>
              </td>
              <td>
                <div class="flex items-center space-x-2">
                  <button @click="viewRapor(siswa)" class="text-blue-600 hover:text-blue-900" title="Lihat Detail">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                  </button>
                  <div class="relative">
                    <button 
                      @click="showActionMenu = showActionMenu === siswa.id ? null : siswa.id"
                      class="text-gray-600 hover:text-gray-900"
                      title="Aksi"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                      </svg>
                    </button>
                    <div v-if="showActionMenu === siswa.id" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                      <div class="py-1">
                        <button @click="approveRapor(siswa)" class="block w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50">
                          Setujui
                        </button>
                        <button @click="rejectRapor(siswa)" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                          Tolak
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="filteredSiswaList.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada rapor P5</h3>
          <p class="mt-1 text-sm text-gray-500">Belum ada rapor P5 yang tersedia untuk ditinjau.</p>
        </div>
      </div>

      <!-- Rapor Detail Modal -->
      <Modal v-model:show="showRaporDetail" title="Detail Rapor P5" size="xl">
        <div v-if="selectedRaporDetail" class="space-y-6">
          <!-- Student Info -->
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-gray-500">Nama Siswa</label>
                <p class="text-sm text-gray-900">{{ selectedRaporDetail.siswa?.nama_lengkap }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">NIS</label>
                <p class="text-sm text-gray-900">{{ selectedRaporDetail.siswa?.nis }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Kelas</label>
                <p class="text-sm text-gray-900">{{ selectedRaporDetail.siswa?.kelas?.nama_kelas }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Jurusan</label>
                <p class="text-sm text-gray-900">{{ selectedRaporDetail.siswa?.kelas?.jurusan?.nama_jurusan }}</p>
              </div>
            </div>
          </div>

          <!-- P5 Projects -->
          <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Projek P5</h4>
            <div v-if="selectedRaporDetail.p5_projects && selectedRaporDetail.p5_projects.length > 0" class="space-y-4">
              <div v-for="project in selectedRaporDetail.p5_projects" :key="project.id" class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-start justify-between mb-3">
                  <div>
                    <h5 class="text-md font-semibold text-gray-900">{{ project.tema }}</h5>
                    <p class="text-sm text-gray-500 mt-1">{{ project.tahun_ajaran?.label }}</p>
                  </div>
                </div>
                <p v-if="project.deskripsi" class="text-sm text-gray-600 mb-3">{{ project.deskripsi }}</p>
                <div v-if="project.koordinator" class="text-sm text-gray-500 mb-3">
                  <span class="font-medium">Koordinator:</span> {{ project.koordinator.nama }}
                </div>
                <div>
                  <h6 class="text-sm font-medium text-gray-700 mb-2">Dimensi yang Dinilai:</h6>
                  <div class="grid grid-cols-2 gap-2">
                    <div v-for="dimensi in project.dimensi" :key="dimensi.id" class="bg-gray-50 rounded p-2">
                      <div class="text-sm font-medium text-gray-900">{{ dimensi.nama_dimensi }}</div>
                      <div class="text-xs text-gray-600 mt-1">
                        <span class="font-medium">Nilai:</span> 
                        <span :class="getNilaiColor(dimensi.nilai)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ml-1">
                          {{ dimensi.nilai }} ({{ dimensi.nilai_description }})
                        </span>
                      </div>
                      <div v-if="dimensi.catatan" class="text-xs text-gray-500 mt-1">
                        <span class="font-medium">Catatan:</span> {{ dimensi.catatan }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              Belum ada projek P5
            </div>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-between">
            <div>
              <button 
                @click="approveRapor(selectedRaporDetail.siswa)" 
                class="btn btn-success mr-3"
              >
                Setujui
              </button>
              <button 
                @click="rejectRapor(selectedRaporDetail.siswa)" 
                class="btn btn-danger"
              >
                Tolak
              </button>
            </div>
            <button @click="showRaporDetail = false" class="btn btn-secondary">Tutup</button>
          </div>
        </template>
      </Modal>

      <!-- Approval Confirmation -->
      <ConfirmDialog
        v-model:show="showApprovalConfirm"
        title="Setujui Rapor P5"
        :message="approvalMessage"
        confirm-text="Ya, Setujui"
        type="success"
        :loading="processing"
        @confirm="confirmApproval"
      />

      <!-- Rejection Modal -->
      <Modal v-model:show="showRejectionModal" title="Tolak Rapor P5" size="md">
        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Berikan alasan penolakan untuk rapor P5 <strong>{{ selectedRaporForAction?.nama_lengkap }}</strong>
          </p>
          <FormField
            v-model="rejectionReason"
            type="textarea"
            label="Alasan Penolakan"
            placeholder="Masukkan alasan penolakan..."
            required
            rows="3"
          />
        </div>

        <template #footer>
          <button @click="confirmRejection" :disabled="!rejectionReason || processing" class="btn btn-danger">
            {{ processing ? 'Memproses...' : 'Tolak Rapor' }}
          </button>
          <button @click="showRejectionModal = false" class="btn btn-secondary mr-3">Batal</button>
        </template>
      </Modal>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'
import Modal from '../../../components/ui/Modal.vue'
import ConfirmDialog from '../../../components/ui/ConfirmDialog.vue'

const toast = useToast()

// Data
const siswaList = ref([])
const tahunAjaranOptions = ref([])
const kelasOptions = ref([])
const selectedRaporDetail = ref(null)
const selectedRaporForAction = ref(null)
const rejectionReason = ref('')
const showActionMenu = ref(null)

// State
const loading = ref(true)
const processing = ref(false)
const showRaporDetail = ref(false)
const showApprovalConfirm = ref(false)
const showRejectionModal = ref(false)

// Filters
const filters = reactive({
  tahun_ajaran_id: '',
  kelas_id: '',
  search: ''
})

// Computed
const filteredSiswaList = computed(() => {
  if (!filters.search) return siswaList.value
  
  const searchLower = filters.search.toLowerCase()
  return siswaList.value.filter(siswa => 
    siswa.nama_lengkap?.toLowerCase().includes(searchLower) ||
    siswa.nis?.toLowerCase().includes(searchLower) ||
    siswa.nisn?.toLowerCase().includes(searchLower)
  )
})

const approvalMessage = computed(() => {
  if (selectedRaporForAction.value) {
    return `Apakah Anda yakin ingin menyetujui rapor P5 ${selectedRaporForAction.value.nama_lengkap}?`
  }
  return 'Apakah Anda yakin ingin menyetujui rapor P5 ini?'
})

// Methods
const fetchTahunAjaran = async () => {
  try {
    const response = await axios.get('/lookup/tahun-ajaran')
    tahunAjaranOptions.value = response.data.map(ta => ({
      ...ta,
      full_description: `${ta.tahun} - Semester ${ta.semester}`
    }))
    
    // Set current active year as default
    const activeYear = tahunAjaranOptions.value.find(t => t.is_active)
    if (activeYear) {
      filters.tahun_ajaran_id = activeYear.id
    }
  } catch (error) {
    console.error('Failed to fetch tahun ajaran:', error)
  }
}

const fetchKelas = async () => {
  try {
    const response = await axios.get('/lookup/kelas')
    kelasOptions.value = response.data
  } catch (error) {
    console.error('Failed to fetch kelas:', error)
  }
}

const fetchRapor = async () => {
  try {
    loading.value = true
    const params = new URLSearchParams()
    if (filters.tahun_ajaran_id) params.append('tahun_ajaran_id', filters.tahun_ajaran_id)
    if (filters.kelas_id) params.append('kelas_id', filters.kelas_id)
    
    const response = await axios.get(`/kepala-sekolah/rapor-approval-p5?${params}`)
    siswaList.value = response.data.data || []
  } catch (error) {
    toast.error('Gagal mengambil data rapor P5')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  // Search is handled by computed property
}

const viewRapor = async (siswa) => {
  try {
    const params = new URLSearchParams()
    if (filters.tahun_ajaran_id) params.append('tahun_ajaran_id', filters.tahun_ajaran_id)
    
    const response = await axios.get(`/kepala-sekolah/rapor-approval-p5/${siswa.id}?${params}`)
    selectedRaporDetail.value = response.data.data
    showRaporDetail.value = true
  } catch (error) {
    toast.error('Gagal mengambil detail rapor P5')
    console.error(error)
  }
}

const approveRapor = (siswa) => {
  selectedRaporForAction.value = siswa
  showActionMenu.value = null
  showApprovalConfirm.value = true
}

const confirmApproval = async () => {
  try {
    processing.value = true
    
    await axios.post(`/kepala-sekolah/rapor-approval-p5/${selectedRaporForAction.value.id}/approve`)
    toast.success('Rapor P5 berhasil disetujui')
    
    showApprovalConfirm.value = false
    showRaporDetail.value = false
    await fetchRapor()
  } catch (error) {
    toast.error('Gagal menyetujui rapor P5')
    console.error(error)
  } finally {
    processing.value = false
  }
}

const rejectRapor = (siswa) => {
  selectedRaporForAction.value = siswa
  rejectionReason.value = ''
  showActionMenu.value = null
  showRejectionModal.value = true
}

const confirmRejection = async () => {
  try {
    processing.value = true
    
    await axios.post(`/kepala-sekolah/rapor-approval-p5/${selectedRaporForAction.value.id}/reject`, {
      reason: rejectionReason.value
    })
    
    toast.success('Rapor P5 berhasil ditolak')
    showRejectionModal.value = false
    showRaporDetail.value = false
    await fetchRapor()
  } catch (error) {
    toast.error('Gagal menolak rapor P5')
    console.error(error)
  } finally {
    processing.value = false
  }
}

const getNilaiColor = (nilai) => {
  const colors = {
    'MB': 'bg-red-100 text-red-800',
    'SB': 'bg-yellow-100 text-yellow-800',
    'BSH': 'bg-blue-100 text-blue-800',
    'SAB': 'bg-green-100 text-green-800'
  }
  return colors[nilai] || 'bg-gray-100 text-gray-800'
}

// Lifecycle
onMounted(async () => {
  await fetchTahunAjaran()
  await fetchKelas()
  await fetchRapor()
})
</script>

