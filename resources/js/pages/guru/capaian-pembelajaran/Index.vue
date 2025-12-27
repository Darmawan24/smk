<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Capaian Pembelajaran
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Kelola CP (Capaian Pembelajaran) dan TP (Tujuan Pembelajaran)
          </p>
        </div>
        <button 
          @click="handleAddCP" 
          :disabled="!selectedMapel"
          class="btn btn-primary"
          :class="{ 'opacity-50 cursor-not-allowed': !selectedMapel }"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Tambah CP
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField
            v-model="selectedKelas"
            type="select"
            label="Kelas"
            placeholder="Pilih Kelas"
            :options="kelasOptions"
            option-value="id"
            option-label="nama_kelas"
            @update:model-value="onKelasChange"
          />
          <FormField
            v-model="selectedMapel"
            type="select"
            label="Mata Pelajaran"
            placeholder="Pilih Mata Pelajaran"
            :options="mapelOptions"
            option-value="id"
            option-label="nama_mapel"
            :disabled="!selectedKelas"
            @update:model-value="loadCP"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
      </div>

      <!-- No Selection State -->
      <div v-else-if="!selectedMapel" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih Kelas dan Mata Pelajaran</h3>
        <p class="mt-1 text-sm text-gray-500">Pilih kelas dan mata pelajaran untuk melihat capaian pembelajaran.</p>
      </div>

      <!-- CP List -->
      <div v-else class="space-y-4">
        <div 
          v-for="cp in cpList" 
          :key="cp.id" 
          class="bg-white shadow rounded-lg overflow-hidden"
        >
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-3">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    {{ cp.kode_cp }}
                  </span>
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    Fase {{ cp.fase }}
                  </span>
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    {{ cp.elemen_name }}
                  </span>
                </div>
                <p class="mt-2 text-sm text-gray-700">{{ cp.deskripsi }}</p>
                <p class="mt-1 text-xs text-gray-500">
                  {{ cp.tujuan_pembelajaran?.length || 0 }} Tujuan Pembelajaran
                </p>
              </div>
              <div class="flex space-x-2 ml-4">
                <button 
                  @click="showTPForm(cp)" 
                  class="btn btn-sm btn-secondary"
                >
                  Tambah TP
                </button>
                <button 
                  @click="editCP(cp)" 
                  class="btn btn-sm btn-secondary"
                >
                  Edit
                </button>
                <button 
                  @click="deleteCP(cp)" 
                  class="btn btn-sm btn-danger"
                >
                  Hapus
                </button>
              </div>
            </div>
          </div>

          <!-- TP List -->
          <div v-if="cp.tujuan_pembelajaran && cp.tujuan_pembelajaran.length > 0" class="px-6 py-4 bg-gray-50">
            <div class="space-y-2">
              <div 
                v-for="tp in cp.tujuan_pembelajaran" 
                :key="tp.id"
                class="flex items-start justify-between p-3 bg-white rounded border border-gray-200"
              >
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-900">{{ tp.kode_tp }}</span>
                    <span class="text-xs text-gray-500">({{ tp.full_code }})</span>
                  </div>
                  <p class="mt-1 text-sm text-gray-600">{{ tp.deskripsi }}</p>
                </div>
                <div class="flex space-x-2 ml-4">
                  <button 
                    @click="editTP(tp, cp)" 
                    class="text-blue-600 hover:text-blue-900"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                  </button>
                  <button 
                    @click="deleteTP(tp)" 
                    class="text-red-600 hover:text-red-900"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="px-6 py-4 bg-gray-50 text-center text-sm text-gray-500">
            Belum ada Tujuan Pembelajaran
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="cpList.length === 0" class="bg-white shadow rounded-lg p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada Capaian Pembelajaran</h3>
          <p class="mt-1 text-sm text-gray-500">Mulai dengan menambahkan CP baru.</p>
        </div>
      </div>

      <!-- CP Form Modal -->
      <Modal v-model:show="showForm" :title="isEditing ? 'Edit Capaian Pembelajaran' : 'Tambah Capaian Pembelajaran'" size="lg">
        <form @submit.prevent="submitCPForm" class="space-y-4">
          <FormField
            v-model="cpForm.kode_cp"
            label="Kode CP"
            placeholder="Masukkan kode CP (contoh: CP-1.1)"
            required
            :error="errors.kode_cp"
          />
          <FormField
            v-model="cpForm.deskripsi"
            type="textarea"
            label="Deskripsi"
            placeholder="Masukkan deskripsi capaian pembelajaran"
            :rows="4"
            required
            :error="errors.deskripsi"
          />
          <div class="grid grid-cols-2 gap-4">
            <FormField
              v-model="cpForm.fase"
              type="select"
              label="Fase"
              placeholder="Pilih Fase"
              :options="faseOptions"
              required
              :error="errors.fase"
            />
            <FormField
              v-model="cpForm.elemen"
              type="select"
              label="Elemen"
              placeholder="Pilih Elemen"
              :options="elemenOptions"
              required
              :error="errors.elemen"
            />
          </div>
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeCPForm" class="btn btn-secondary">
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

      <!-- TP Form Modal -->
      <Modal v-model:show="showTPFormModal" :title="isEditingTP ? 'Edit Tujuan Pembelajaran' : 'Tambah Tujuan Pembelajaran'" size="lg">
        <form @submit.prevent="submitTPForm" class="space-y-4">
          <FormField
            v-model="tpForm.kode_tp"
            label="Kode TP"
            placeholder="Masukkan kode TP (contoh: TP-1.1.1)"
            required
            :error="errors.kode_tp"
          />
          <FormField
            v-model="tpForm.deskripsi"
            type="textarea"
            label="Deskripsi"
            placeholder="Masukkan deskripsi tujuan pembelajaran"
            :rows="4"
            required
            :error="errors.deskripsi"
          />
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeTPForm" class="btn btn-secondary">
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
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'
import Modal from '../../../components/ui/Modal.vue'

const toast = useToast()

// Data
const kelasOptions = ref([])
const mapelOptions = ref([])
const cpList = ref([])

// State
const loading = ref(false)
const submitting = ref(false)
const selectedKelas = ref('')
const selectedMapel = ref('')
const showForm = ref(false)
const showTPFormModal = ref(false)
const isEditing = ref(false)
const isEditingTP = ref(false)
const editingCP = ref(null)
const editingTP = ref(null)
const selectedCP = ref(null)

// Forms
const cpForm = ref({
  mata_pelajaran_id: '',
  kode_cp: '',
  deskripsi: '',
  fase: '',
  elemen: ''
})

const tpForm = ref({
  kode_tp: '',
  deskripsi: ''
})

const errors = ref({})

// Options
const faseOptions = [
  { value: 'A', label: 'Fase A' },
  { value: 'B', label: 'Fase B' },
  { value: 'C', label: 'Fase C' },
  { value: 'D', label: 'Fase D' },
  { value: 'E', label: 'Fase E' },
  { value: 'F', label: 'Fase F' }
]

const elemenOptions = [
  { value: 'pemahaman', label: 'Pemahaman' },
  { value: 'keterampilan', label: 'Keterampilan' },
  { value: 'sikap', label: 'Sikap' }
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

const fetchMapel = async () => {
  if (!selectedKelas.value) {
    mapelOptions.value = []
    return
  }
  
  try {
    const params = new URLSearchParams()
    params.append('kelas_id', selectedKelas.value)
    
    const response = await axios.get(`/lookup/mata-pelajaran?${params.toString()}`)
    mapelOptions.value = response.data
    // Reset selected mapel if it's not in the new list
    if (selectedMapel.value) {
      const mapelExists = mapelOptions.value.find(m => m.id == selectedMapel.value)
      if (!mapelExists) {
        selectedMapel.value = ''
      }
    }
  } catch (error) {
    console.error('Failed to fetch mata pelajaran:', error)
  }
}

const onKelasChange = () => {
  // Reset selected mapel when kelas changes
  selectedMapel.value = ''
  // Fetch mapel for the selected kelas
  fetchMapel()
  // Clear CP data
  cpList.value = []
}

const loadCP = async () => {
  if (!selectedMapel.value) {
    cpList.value = []
    return
  }

  try {
    loading.value = true
    const response = await axios.get(`/guru/capaian-pembelajaran/mapel/${selectedMapel.value}`)
    cpList.value = response.data.capaian_pembelajaran || []
  } catch (error) {
    toast.error('Gagal mengambil data CP')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const handleAddCP = () => {
  if (!selectedMapel.value) {
    toast.error('Pilih mata pelajaran terlebih dahulu')
    return
  }
  editingCP.value = null
  isEditing.value = false
  cpForm.value = {
    mata_pelajaran_id: selectedMapel.value,
    kode_cp: '',
    deskripsi: '',
    fase: '',
    elemen: ''
  }
  errors.value = {}
  showForm.value = true
}

const showTPForm = (cp) => {
  selectedCP.value = cp
  isEditingTP.value = false
  editingTP.value = null
  tpForm.value = {
    kode_tp: '',
    deskripsi: ''
  }
  showTPFormModal.value = true
}

const editCP = (cp) => {
  editingCP.value = cp
  cpForm.value = {
    mata_pelajaran_id: cp.mata_pelajaran_id,
    kode_cp: cp.kode_cp,
    deskripsi: cp.deskripsi,
    fase: cp.fase,
    elemen: cp.elemen
  }
  isEditing.value = true
  showForm.value = true
}

const editTP = (tp, cp) => {
  selectedCP.value = cp
  editingTP.value = tp
  tpForm.value = {
    kode_tp: tp.kode_tp,
    deskripsi: tp.deskripsi
  }
  isEditingTP.value = true
  showTPFormModal.value = true
}

const deleteCP = async (cp) => {
  if (!confirm('Apakah Anda yakin ingin menghapus CP ini? Tujuan Pembelajaran yang terkait juga akan terhapus.')) return

  try {
    await axios.delete(`/guru/capaian-pembelajaran/${cp.id}`)
    toast.success('CP berhasil dihapus')
    await loadCP()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus CP')
  }
}

const deleteTP = async (tp) => {
  if (!confirm('Apakah Anda yakin ingin menghapus TP ini?')) return

  try {
    await axios.delete(`/guru/capaian-pembelajaran/${selectedCP.value.id}/tujuan-pembelajaran/${tp.id}`)
    toast.success('TP berhasil dihapus')
    await loadCP()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus TP')
  }
}

const submitCPForm = async () => {
  errors.value = {}
  
  // Validate mata pelajaran is selected
  if (!selectedMapel.value) {
    toast.error('Pilih mata pelajaran terlebih dahulu')
    return
  }
  
  try {
    submitting.value = true
    // Ensure mata_pelajaran_id is set from selectedMapel
    const formData = {
      ...cpForm.value,
      mata_pelajaran_id: selectedMapel.value
    }
    
    if (isEditing.value && editingCP.value) {
      await axios.put(`/guru/capaian-pembelajaran/${editingCP.value.id}`, formData)
      toast.success('CP berhasil diperbarui')
    } else {
      await axios.post('/guru/capaian-pembelajaran', formData)
      toast.success('CP berhasil ditambahkan')
    }
    closeCPForm()
    await loadCP()
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Gagal menyimpan CP')
    }
  } finally {
    submitting.value = false
  }
}

const submitTPForm = async () => {
  errors.value = {}
  
  try {
    submitting.value = true
    
    if (isEditingTP.value && editingTP.value) {
      await axios.put(`/guru/capaian-pembelajaran/${selectedCP.value.id}/tujuan-pembelajaran/${editingTP.value.id}`, tpForm.value)
      toast.success('TP berhasil diperbarui')
    } else {
      await axios.post(`/guru/capaian-pembelajaran/${selectedCP.value.id}/tujuan-pembelajaran`, tpForm.value)
      toast.success('TP berhasil ditambahkan')
    }
    closeTPForm()
    await loadCP()
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Gagal menyimpan TP')
    }
  } finally {
    submitting.value = false
  }
}

const closeCPForm = () => {
  showForm.value = false
  isEditing.value = false
  editingCP.value = null
  cpForm.value = {
    mata_pelajaran_id: '',
    kode_cp: '',
    deskripsi: '',
    fase: '',
    elemen: ''
  }
  errors.value = {}
}

const closeTPForm = () => {
  showTPFormModal.value = false
  isEditingTP.value = false
  editingTP.value = null
  selectedCP.value = null
  tpForm.value = {
    kode_tp: '',
    deskripsi: ''
  }
  errors.value = {}
}

// Lifecycle
onMounted(() => {
  fetchKelas()
})
</script>
