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
        <div class="mt-4 md:mt-0 md:ml-4">
          <button 
            @click="openBatchAddModal" 
            class="btn btn-primary"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Nilai
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
      </div>

      <!-- No Selection State -->
      <div v-else-if="!selectedEkstrakurikuler" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih Ekstrakurikuler</h3>
        <p class="mt-1 text-sm text-gray-500">Pilih ekstrakurikuler untuk mulai input nilai.</p>
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
                Tahun Ajaran: {{ tahunAjaranAktifName }}
              </p>
            </div>
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
                    <button @click="confirmDeleteNilai(nilai)" class="text-red-600 hover:text-red-900">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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

      <!-- Batch Add Nilai Modal -->
      <Modal :show="showBatchAddModal" @update:show="handleBatchModalUpdate" title="Tambah Nilai Ekstrakurikuler" size="lg">
        <form @submit.prevent="submitBatchForm" class="space-y-4">
          <FormField
            v-model="batchForm.ekstrakurikuler_id"
            type="select"
            label="Ekstrakurikuler"
            placeholder="Pilih Ekstrakurikuler"
            :options="ekstrakurikulerOptions"
            option-value="id"
            option-label="nama"
            required
            :error="errors.ekstrakurikuler_id"
            @update:model-value="onBatchEkstrakurikulerChange"
          />
          <FormField
            v-model="batchForm.kelas_id"
            type="select"
            label="Kelas"
            placeholder="Pilih Kelas"
            :options="kelasOptions"
            option-value="id"
            option-label="nama_kelas"
            required
            :error="errors.kelas_id"
            @update:model-value="onBatchKelasChange"
          />
          <MultiSelect
            v-model="batchForm.siswa_ids"
            :options="filteredSiswaOptions"
            option-value="id"
            option-label="nama_lengkap"
            label="Pilih Siswa"
            :placeholder="batchForm.kelas_id ? 'Pilih siswa yang akan ditambahkan' : 'Pilih kelas terlebih dahulu'"
            :required="true"
            :disabled="!batchForm.kelas_id"
            :error="errors.siswa_ids"
          />
          <FormField
            v-model="batchForm.predikat"
            type="select"
            label="Predikat"
            placeholder="Pilih Predikat"
            :options="predikatOptions"
            required
            :error="errors.predikat"
            @update:model-value="updateKeteranganFromPredikat"
          />
          <FormField
            v-model="batchForm.keterangan"
            type="textarea"
            label="Keterangan"
            placeholder="Keterangan akan diisi otomatis sesuai predikat"
            :rows="3"
            :readonly="true"
            :error="errors.keterangan"
          />
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeBatchModal" class="btn btn-secondary">
              Batal
            </button>
            <button type="submit" :disabled="batchSubmitting" class="btn btn-primary">
              <svg v-if="batchSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ batchSubmitting ? 'Menambahkan...' : 'Tambah Nilai' }}
            </button>
          </div>
        </form>
      </Modal>

      <!-- Delete Confirmation Modal -->
      <Modal :show="showDeleteConfirm" @update:show="handleDeleteModalUpdate" title="Hapus Nilai" size="md">
        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Apakah Anda yakin ingin menghapus nilai ekstrakurikuler untuk siswa <strong>{{ deletingNilai?.siswa?.nama_lengkap }}</strong>?
          </p>
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeDeleteModal" class="btn btn-secondary">
              Batal
            </button>
            <button type="button" @click="deleteNilai" :disabled="deleting" class="btn btn-danger">
              <svg v-if="deleting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ deleting ? 'Menghapus...' : 'Hapus' }}
            </button>
          </div>
        </div>
      </Modal>

      <!-- Edit Modal -->
      <Modal :show="showEditModal" @update:show="handleEditModalUpdate" title="Edit Nilai" size="lg">
        <form @submit.prevent="submitEditForm" class="space-y-4">
          <FormField
            v-model="editForm.predikat"
            type="select"
            label="Predikat"
            placeholder="Pilih Predikat"
            :options="predikatOptions"
            required
            :error="errors.predikat"
            @update:model-value="updateEditKeteranganFromPredikat"
          />
          <FormField
            v-model="editForm.keterangan"
            type="textarea"
            label="Keterangan"
            placeholder="Keterangan akan diisi otomatis sesuai predikat"
            :rows="3"
            :readonly="true"
            :error="errors.keterangan"
          />
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeEditModal" class="btn btn-secondary">
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
import MultiSelect from '../../../components/ui/MultiSelect.vue'

const toast = useToast()

// Data
const ekstrakurikulerOptions = ref([])
const siswaOptions = ref([])
const allSiswaOptions = ref([])
const kelasOptions = ref([])
const nilaiData = ref([])
const tahunAjaranAktif = ref(null)

// State
const loading = ref(false)
const submitting = ref(false)
const batchSubmitting = ref(false)
const deleting = ref(false)
const selectedEkstrakurikuler = ref('')
const showEditModal = ref(false)
const showBatchAddModal = ref(false)
const showDeleteConfirm = ref(false)
const editingNilai = ref(null)
const deletingNilai = ref(null)

// Form
const batchForm = ref({
  kelas_id: '',
  siswa_ids: [],
  predikat: '',
  keterangan: ''
})

const editForm = ref({
  predikat: '',
  keterangan: ''
})

const errors = ref({})

// Options
const predikatOptions = [
  { value: 'A', label: 'A' },
  { value: 'B', label: 'B' },
  { value: 'C', label: 'C' },
  { value: 'D', label: 'D' }
]

// Computed
const selectedEkstrakurikulerName = computed(() => {
  const ekskul = ekstrakurikulerOptions.value.find(e => e.id == selectedEkstrakurikuler.value)
  return ekskul?.nama || ''
})

const tahunAjaranAktifName = computed(() => {
  if (tahunAjaranAktif.value) {
    return `${tahunAjaranAktif.value.tahun} - Semester ${tahunAjaranAktif.value.semester}`
  }
  return 'Memuat...'
})

// Helper function to get keterangan from predikat
const getKeteranganFromPredikat = (predikat, namaEkstrakurikuler = '') => {
  const keteranganMap = {
    'A': 'Sangat Baik',
    'B': 'Baik',
    'C': 'Cukup',
    'D': 'Perlu Bimbingan'
  }
  const predikatDesc = keteranganMap[predikat] || ''
  if (!predikatDesc) return ''
  if (namaEkstrakurikuler) {
    return `Telah mengikut Ekstrakurikuler ${namaEkstrakurikuler} dengan ${predikatDesc}`
  }
  return `Telah mengikut Ekstrakurikuler dengan ${predikatDesc}`
}

const filteredSiswaOptions = computed(() => {
  if (!batchForm.value.kelas_id) {
    return []
  }
  
  // Filter siswa berdasarkan kelas yang dipilih
  // Handle both kelas_id (direct) and kelas.id (relation)
  let filtered = allSiswaOptions.value.filter(siswa => {
    const siswaKelasId = siswa.kelas_id || siswa.kelas?.id
    // Convert to string for comparison to handle both string and number IDs
    return String(siswaKelasId) === String(batchForm.value.kelas_id)
  })
  
  // Filter out siswa yang sudah terdaftar di ekskul ini
  const ekskulId = batchForm.value.ekstrakurikuler_id || selectedEkstrakurikuler.value
  if (ekskulId && tahunAjaranAktif.value) {
    const registeredSiswaIds = nilaiData.value.map(n => n.siswa_id)
    filtered = filtered.filter(siswa => 
      !registeredSiswaIds.includes(siswa.id)
    )
  }
  
  return filtered
})

// Methods
const fetchEkstrakurikuler = async () => {
  try {
    const response = await axios.get('/guru/nilai-ekstrakurikuler/my-ekstrakurikuler')
    ekstrakurikulerOptions.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch ekstrakurikuler:', error)
    toast.error('Gagal mengambil data ekstrakurikuler')
  }
}

const fetchTahunAjaranAktif = async () => {
  try {
    const response = await axios.get('/lookup/tahun-ajaran-aktif')
    tahunAjaranAktif.value = response.data
  } catch (error) {
    console.error('Failed to fetch tahun ajaran aktif:', error)
    toast.error('Gagal mengambil tahun ajaran aktif')
  }
}

const fetchKelas = async () => {
  try {
    const response = await axios.get('/lookup/kelas')
    kelasOptions.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch kelas:', error)
    toast.error('Gagal mengambil data kelas')
  }
}

const fetchSiswa = async () => {
  try {
    const response = await axios.get('/guru/nilai-ekstrakurikuler/siswa')
    allSiswaOptions.value = response.data || []
    updateSiswaOptions()
  } catch (error) {
    console.error('Failed to fetch siswa:', error)
    toast.error('Gagal mengambil data siswa')
  }
}

const onBatchKelasChange = () => {
  // Reset siswa selection when kelas changes
  batchForm.value.siswa_ids = []
}

const onBatchEkstrakurikulerChange = () => {
  // Reset siswa selection and kelas when ekstrakurikuler changes
  batchForm.value.siswa_ids = []
  batchForm.value.kelas_id = ''
  // Reset keterangan if predikat is set
  if (batchForm.value.predikat) {
    const ekskul = ekstrakurikulerOptions.value.find(e => e.id == batchForm.value.ekstrakurikuler_id)
    const namaEkskul = ekskul?.nama || ''
    batchForm.value.keterangan = getKeteranganFromPredikat(batchForm.value.predikat, namaEkskul)
  }
}

const updateSiswaOptions = () => {
  if (!selectedEkstrakurikuler.value || !tahunAjaranAktif.value) {
    siswaOptions.value = allSiswaOptions.value
    return
  }
  
  // Filter out siswa yang sudah terdaftar di ekskul ini
  const registeredSiswaIds = nilaiData.value.map(n => n.siswa_id)
  siswaOptions.value = allSiswaOptions.value.filter(siswa => 
    !registeredSiswaIds.includes(siswa.id)
  )
}

const loadSiswa = async () => {
  if (!selectedEkstrakurikuler.value || !tahunAjaranAktif.value) {
    nilaiData.value = []
    return
  }

  try {
    loading.value = true
    // Get all siswa with nilai for this ekstrakurikuler
    const response = await axios.get('/guru/nilai-ekstrakurikuler/siswa')
    const allSiswa = response.data || []
    
    // Fetch nilai for each siswa
    const nilaiPromises = allSiswa.map(async (siswa) => {
      try {
        const nilaiRes = await axios.get(`/nilai-ekstrakurikuler/siswa/${siswa.id}`, {
          params: { tahun_ajaran_id: tahunAjaranAktif.value.id }
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
    updateSiswaOptions()
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

const updateKeteranganFromPredikat = () => {
  if (batchForm.value.predikat) {
    const ekskul = ekstrakurikulerOptions.value.find(e => e.id == batchForm.value.ekstrakurikuler_id)
    const namaEkskul = ekskul?.nama || selectedEkstrakurikulerName.value
    batchForm.value.keterangan = getKeteranganFromPredikat(batchForm.value.predikat, namaEkskul)
  }
}

const updateEditKeteranganFromPredikat = () => {
  if (editForm.value.predikat) {
    const namaEkskul = editingNilai.value?.ekstrakurikuler?.nama || selectedEkstrakurikulerName.value
    editForm.value.keterangan = getKeteranganFromPredikat(editForm.value.predikat, namaEkskul)
  }
}

const openBatchAddModal = () => {
  batchForm.value = {
    ekstrakurikuler_id: selectedEkstrakurikuler.value || '',
    kelas_id: '',
    siswa_ids: [],
    predikat: '',
    keterangan: ''
  }
  errors.value = {}
  showBatchAddModal.value = true
}

const handleBatchModalUpdate = (value) => {
  if (value === false) {
    closeBatchModal()
  }
}

const closeBatchModal = () => {
  showBatchAddModal.value = false
  batchForm.value = {
    ekstrakurikuler_id: '',
    kelas_id: '',
    siswa_ids: [],
    predikat: '',
    keterangan: ''
  }
  errors.value = {}
}

const submitBatchForm = async () => {
  errors.value = {}
  
  if (!batchForm.value.ekstrakurikuler_id) {
    errors.value.ekstrakurikuler_id = 'Pilih ekstrakurikuler terlebih dahulu'
    toast.error('Pilih ekstrakurikuler terlebih dahulu')
    return
  }
  
  if (!batchForm.value.kelas_id) {
    errors.value.kelas_id = 'Pilih kelas terlebih dahulu'
    toast.error('Pilih kelas terlebih dahulu')
    return
  }
  
  if (!batchForm.value.siswa_ids || batchForm.value.siswa_ids.length === 0) {
    errors.value.siswa_ids = 'Pilih minimal satu siswa'
    toast.error('Pilih minimal satu siswa')
    return
  }
  
  if (!batchForm.value.predikat) {
    errors.value.predikat = 'Pilih predikat terlebih dahulu'
    toast.error('Pilih predikat terlebih dahulu')
    return
  }
  
  if (!tahunAjaranAktif.value?.id) {
    toast.error('Tahun ajaran aktif tidak ditemukan')
    return
  }
  
  // Ensure keterangan is set from predikat if not already set
  if (!batchForm.value.keterangan && batchForm.value.predikat) {
    const ekskul = ekstrakurikulerOptions.value.find(e => e.id == batchForm.value.ekstrakurikuler_id)
    const namaEkskul = ekskul?.nama || ''
    batchForm.value.keterangan = getKeteranganFromPredikat(batchForm.value.predikat, namaEkskul)
  }
  
  try {
    batchSubmitting.value = true
    const response = await axios.post('/guru/nilai-ekstrakurikuler/batch-store', {
      siswa_ids: batchForm.value.siswa_ids,
      ekstrakurikuler_id: batchForm.value.ekstrakurikuler_id,
      tahun_ajaran_id: tahunAjaranAktif.value.id,
      predikat: batchForm.value.predikat,
      keterangan: batchForm.value.keterangan
    })
    
    toast.success(response.data.message || 'Siswa berhasil ditambahkan')
    closeBatchModal()
    
    // If the selected ekstrakurikuler matches, reload the data
    if (selectedEkstrakurikuler.value == batchForm.value.ekstrakurikuler_id) {
      await loadSiswa()
    } else {
      // If different, update selected ekstrakurikuler and reload
      selectedEkstrakurikuler.value = batchForm.value.ekstrakurikuler_id
      await loadSiswa()
    }
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Gagal menambahkan siswa')
    }
  } finally {
    batchSubmitting.value = false
  }
}

const editNilai = (nilai) => {
  editingNilai.value = nilai
  const namaEkskul = nilai.ekstrakurikuler?.nama || selectedEkstrakurikulerName.value
  editForm.value = {
    predikat: nilai.predikat,
    keterangan: nilai.keterangan || getKeteranganFromPredikat(nilai.predikat, namaEkskul)
  }
  showEditModal.value = true
}

const handleEditModalUpdate = (value) => {
  if (value === false) {
    closeEditModal()
  }
}

const closeEditModal = () => {
  showEditModal.value = false
  editingNilai.value = null
  editForm.value = {
    predikat: '',
    keterangan: ''
  }
  errors.value = {}
}

const submitEditForm = async () => {
  errors.value = {}
  
  if (!editingNilai.value) {
    return
  }
  
  try {
    submitting.value = true
    await axios.put(`/nilai-ekstrakurikuler/${editingNilai.value.id}`, {
      predikat: editForm.value.predikat,
      keterangan: editForm.value.keterangan
    })
    toast.success('Nilai berhasil diperbarui')
    closeEditModal()
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
}

const confirmDeleteNilai = (nilai) => {
  deletingNilai.value = nilai
  showDeleteConfirm.value = true
}

const handleDeleteModalUpdate = (value) => {
  if (value === false) {
    closeDeleteModal()
  }
}

const closeDeleteModal = () => {
  showDeleteConfirm.value = false
  deletingNilai.value = null
}

const deleteNilai = async () => {
  if (!deletingNilai.value) {
    return
  }
  
  try {
    deleting.value = true
    await axios.delete(`/nilai-ekstrakurikuler/${deletingNilai.value.id}`)
    toast.success('Nilai berhasil dihapus')
    closeDeleteModal()
    await loadSiswa()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus nilai')
    console.error(error)
  } finally {
    deleting.value = false
  }
}

// Lifecycle
onMounted(async () => {
  await fetchTahunAjaranAktif()
  await fetchEkstrakurikuler()
  await fetchKelas()
  fetchSiswa()
})
</script>

