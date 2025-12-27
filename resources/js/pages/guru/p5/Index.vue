<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Projek P5
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Kelola projek penguatan profil pelajar Pancasila
          </p>
        </div>
        <button @click="showForm = true" class="btn btn-primary">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Buat Projek
        </button>
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
            @update:model-value="fetchP5"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
      </div>

      <!-- P5 List -->
      <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div 
          v-for="p5 in p5List" 
          :key="p5.id" 
          class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
        >
          <div class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h3 class="text-lg font-medium text-gray-900">{{ p5.tema }}</h3>
                <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ p5.deskripsi }}</p>
                <div class="mt-4 flex items-center space-x-4 text-sm text-gray-500">
                  <span>Tahun Ajaran: {{ p5.tahun_ajaran?.tahun }} - Semester {{ p5.tahun_ajaran?.semester }}</span>
                </div>
              </div>
            </div>
            <div class="mt-6 flex items-center justify-between">
              <div class="flex space-x-2">
                <button 
                  @click="viewNilai(p5)" 
                  class="btn btn-sm btn-primary"
                >
                  Input Nilai
                </button>
                <button 
                  @click="editP5(p5)" 
                  class="btn btn-sm btn-secondary"
                >
                  Edit
                </button>
                <button 
                  @click="deleteP5(p5)" 
                  class="btn btn-sm btn-danger"
                >
                  Hapus
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && p5List.length === 0" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada projek P5</h3>
        <p class="mt-1 text-sm text-gray-500">Mulai dengan membuat projek P5 baru.</p>
      </div>

      <!-- Form Modal -->
      <Modal v-model:show="showForm" :title="isEditing ? 'Edit Projek P5' : 'Buat Projek P5'" size="lg">
        <form @submit.prevent="submitForm" class="space-y-4">
          <FormField
            v-model="form.tema"
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
            :rows="4"
            required
            :error="errors.deskripsi"
          />
          <FormField
            v-model="form.tahun_ajaran_id"
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
            <button type="button" @click="closeForm" class="btn btn-secondary">
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

      <!-- Input Nilai Modal -->
      <Modal v-model:show="showNilaiModal" title="Input Nilai P5" size="xl">
        <div v-if="selectedP5" class="space-y-6">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ selectedP5.tema }}</h3>
            <p class="text-sm text-gray-500">{{ selectedP5.deskripsi }}</p>
          </div>

          <!-- Dimensi Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Dimensi</label>
            <FormField
              v-model="selectedDimensi"
              type="select"
              placeholder="Pilih Dimensi"
              :options="dimensiOptions"
              option-value="id"
              option-label="nama"
              @update:model-value="loadNilaiP5"
            />
          </div>

          <!-- Nilai Table -->
          <div v-if="selectedDimensi" class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th class="w-16">No</th>
                  <th>Nama Siswa</th>
                  <th class="w-48">Nilai</th>
                  <th class="w-64">Catatan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(siswa, index) in siswaList" :key="siswa.id">
                  <td class="text-center">{{ index + 1 }}</td>
                  <td>
                    <div class="flex items-center">
                      <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                        {{ siswa.nama_lengkap?.charAt(0) }}
                      </div>
                      <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900">{{ siswa.nama_lengkap }}</div>
                        <div class="text-sm text-gray-500">{{ siswa.nis }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <FormField
                      :model-value="nilaiForm[siswa.id]?.nilai || ''"
                      @update:model-value="(value) => { if (!nilaiForm[siswa.id]) nilaiForm[siswa.id] = {}; nilaiForm[siswa.id].nilai = value; markChanged(siswa.id); }"
                      type="select"
                      :options="nilaiOptions"
                    />
                  </td>
                  <td>
                    <input
                      :value="nilaiForm[siswa.id]?.catatan || ''"
                      @input="(e) => { if (!nilaiForm[siswa.id]) nilaiForm[siswa.id] = {}; nilaiForm[siswa.id].catatan = e.target.value; markChanged(siswa.id); }"
                      type="text"
                      class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Catatan..."
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeNilaiModal" class="btn btn-secondary">
              Batal
            </button>
            <button 
              @click="saveNilai" 
              :disabled="!hasNilaiChanges || savingNilai"
              class="btn btn-primary"
            >
              <svg v-if="savingNilai" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ savingNilai ? 'Menyimpan...' : 'Simpan Nilai' }}
            </button>
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
import ConfirmDialog from '../../../components/ui/ConfirmDialog.vue'

const toast = useToast()

// Data
const p5List = ref([])
const tahunAjaranOptions = ref([])
const dimensiOptions = ref([])
const siswaList = ref([])

// State
const loading = ref(false)
const submitting = ref(false)
const savingNilai = ref(false)
const selectedTahunAjaran = ref('')
const showForm = ref(false)
const showNilaiModal = ref(false)
const isEditing = ref(false)
const editingP5 = ref(null)
const selectedP5 = ref(null)
const selectedDimensi = ref('')
const nilaiForm = ref({})
const changedNilai = ref(new Set())

// Form
const form = ref({
  tema: '',
  deskripsi: '',
  tahun_ajaran_id: ''
})

const errors = ref({})

// Options
const nilaiOptions = [
  { value: 'MB', label: 'MB - Mulai Berkembang' },
  { value: 'SB', label: 'SB - Sedang Berkembang' },
  { value: 'BSH', label: 'BSH - Berkembang Sesuai Harapan' },
  { value: 'SAB', label: 'SAB - Sangat Berkembang' }
]

// Computed
const hasNilaiChanges = computed(() => changedNilai.value.size > 0)

// Methods
const fetchTahunAjaran = async () => {
  try {
    const response = await axios.get('/lookup/tahun-ajaran')
    tahunAjaranOptions.value = response.data
  } catch (error) {
    console.error('Failed to fetch tahun ajaran:', error)
  }
}

const fetchDimensi = async () => {
  try {
    const response = await axios.get('/lookup/dimensi-p5')
    dimensiOptions.value = response.data
  } catch (error) {
    console.error('Failed to fetch dimensi:', error)
  }
}

const fetchP5 = async () => {
  try {
    loading.value = true
    const params = {}
    if (selectedTahunAjaran.value) {
      params.tahun_ajaran_id = selectedTahunAjaran.value
    }
    const response = await axios.get('/guru/p5', { params })
    p5List.value = response.data.data || response.data
  } catch (error) {
    toast.error('Gagal mengambil data projek P5')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const fetchSiswa = async () => {
  try {
    const response = await axios.get('/admin/siswa', {
      params: { status: 'aktif', per_page: 1000 }
    })
    siswaList.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to fetch siswa:', error)
  }
}

const viewNilai = async (p5) => {
  selectedP5.value = p5
  selectedDimensi.value = ''
  nilaiForm.value = {}
  changedNilai.value.clear()
  showNilaiModal.value = true
  await fetchSiswa()
  // Initialize form for all siswa
  siswaList.value.forEach(siswa => {
    nilaiForm.value[siswa.id] = {
      siswa_id: siswa.id,
      dimensi_id: '',
      nilai: '',
      catatan: ''
    }
  })
}

const loadNilaiP5 = async () => {
  if (!selectedP5.value || !selectedDimensi.value) return

  try {
    // Initialize form for all siswa first
    siswaList.value.forEach(siswa => {
      if (!nilaiForm.value[siswa.id]) {
        nilaiForm.value[siswa.id] = {
          siswa_id: siswa.id,
          dimensi_id: selectedDimensi.value,
          nilai: '',
          catatan: ''
        }
      }
    })

    const response = await axios.get(`/guru/p5/${selectedP5.value.id}/nilai`)
    const existingNilai = response.data.nilai || {}
    
    // Update form with existing nilai
    siswaList.value.forEach(siswa => {
      const siswaNilai = existingNilai[siswa.id]?.find(n => n.dimensi_id == selectedDimensi.value)
      if (siswaNilai) {
        nilaiForm.value[siswa.id] = {
          siswa_id: siswa.id,
          dimensi_id: selectedDimensi.value,
          nilai: siswaNilai.nilai || '',
          catatan: siswaNilai.catatan || ''
        }
      } else {
        nilaiForm.value[siswa.id] = {
          siswa_id: siswa.id,
          dimensi_id: selectedDimensi.value,
          nilai: '',
          catatan: ''
        }
      }
    })
  } catch (error) {
    console.error('Failed to load nilai:', error)
  }
}

const markChanged = (siswaId) => {
  changedNilai.value.add(siswaId)
}

const saveNilai = async () => {
  try {
    savingNilai.value = true
    const nilaiData = Object.values(nilaiForm.value)
      .filter(n => n.nilai)
      .map(n => ({
        siswa_id: n.siswa_id,
        dimensi_id: n.dimensi_id,
        nilai: n.nilai,
        catatan: n.catatan || ''
      }))

    await axios.post(`/guru/p5/${selectedP5.value.id}/nilai`, {
      nilai: nilaiData
    })

    toast.success('Nilai berhasil disimpan')
    changedNilai.value.clear()
    await loadNilaiP5()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan nilai')
  } finally {
    savingNilai.value = false
  }
}

const editP5 = (p5) => {
  editingP5.value = p5
  form.value = {
    tema: p5.tema,
    deskripsi: p5.deskripsi,
    tahun_ajaran_id: p5.tahun_ajaran_id
  }
  isEditing.value = true
  showForm.value = true
}

const deleteP5 = async (p5) => {
  if (!confirm('Apakah Anda yakin ingin menghapus projek ini?')) return

  try {
    await axios.delete(`/guru/p5/${p5.id}`)
    toast.success('Projek berhasil dihapus')
    await fetchP5()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menghapus projek')
  }
}

const closeForm = () => {
  showForm.value = false
  isEditing.value = false
  editingP5.value = null
  form.value = {
    tema: '',
    deskripsi: '',
    tahun_ajaran_id: ''
  }
  errors.value = {}
}

const closeNilaiModal = () => {
  showNilaiModal.value = false
  selectedP5.value = null
  selectedDimensi.value = ''
  nilaiForm.value = {}
  changedNilai.value.clear()
}

const submitForm = async () => {
  errors.value = {}
  
  try {
    submitting.value = true
    if (isEditing.value && editingP5.value) {
      await axios.put(`/guru/p5/${editingP5.value.id}`, form.value)
      toast.success('Projek berhasil diperbarui')
    } else {
      await axios.post('/guru/p5', form.value)
      toast.success('Projek berhasil dibuat')
    }
    closeForm()
    await fetchP5()
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Gagal menyimpan projek')
    }
  } finally {
    submitting.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchTahunAjaran()
  fetchDimensi()
  fetchP5()
})
</script>

