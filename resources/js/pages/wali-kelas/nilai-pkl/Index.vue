<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Input Nilai PKL
      </h2>
      <p class="mt-1 text-sm text-gray-500">
        Input nilai PKL (Praktik Kerja Lapangan) siswa tingkat 12
      </p>

      <!-- Access Check -->
      <div v-if="!canAccess" class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">
              Informasi
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
              <p>{{ accessMessage }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div v-if="canAccess" class="mt-6 bg-white shadow rounded-lg p-6">
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
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading && canAccess" class="mt-6 bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
      </div>

      <!-- Table -->
      <div v-else-if="canAccess && selectedKelas" class="mt-6 bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <table class="w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                    No
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nama Siswa
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    NISN
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Data Du/Di
                  </th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Lamanya (Bulan)
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Keterangan
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(siswa, index) in siswaList" :key="siswa.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm text-center text-gray-500">
                    {{ index + 1 }}
                  </td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">
                    {{ siswa.nama_lengkap }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-900">
                    {{ siswa.nisn }}
                  </td>
                  <td class="px-4 py-3">
                    <div class="relative" v-if="pklOptions.length > 0" :ref="el => setInputRef(el, siswa)">
                      <input
                        type="text"
                        :value="siswa.pklSearchQuery || getPklLabel(siswa.nilai_pkl_form.pkl_id) || siswa.nilai_pkl_form.nama_du_di || ''"
                        @focus="showPklDropdown(siswa, $event)"
                        @input="searchPkl($event, siswa)"
                        @blur="handleInputBlur(siswa)"
                        placeholder="Cari atau pilih Du/Di"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2"
                        :disabled="loadingPkl"
                      />
                      <Teleport to="body">
                        <div
                          v-if="siswa.showPklDropdown"
                          class="fixed z-[9999] bg-white rounded-md shadow-lg border border-gray-200 max-h-60 overflow-auto"
                          :style="getDropdownStyle(siswa)"
                        >
                          <div
                            v-for="pkl in getFilteredPkl(siswa)"
                            :key="pkl.id"
                            @mousedown.prevent="selectPkl(siswa, pkl, $event)"
                            @click.prevent="selectPkl(siswa, pkl, $event)"
                            class="px-4 py-2 hover:bg-gray-50 cursor-pointer"
                            :class="{ 'bg-blue-50': siswa.nilai_pkl_form.pkl_id == pkl.id }"
                          >
                            <div class="font-medium text-gray-900">{{ pkl.nama_perusahaan }}</div>
                            <div class="text-xs text-gray-500">{{ pkl.alamat_perusahaan }}</div>
                          </div>
                          <div v-if="getFilteredPkl(siswa).length === 0" class="px-4 py-2 text-sm text-gray-500 text-center">
                            Tidak ada hasil
                          </div>
                        </div>
                      </Teleport>
                    </div>
                    <div v-else-if="loadingPkl" class="text-sm text-gray-500">
                      Memuat data DU/DI...
                    </div>
                    <div v-else class="text-sm text-gray-500">
                      Belum ada data DU/DI untuk jurusan ini
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <FormField
                      v-model="siswa.nilai_pkl_form.lamanya_bulan"
                      type="number"
                      placeholder="0"
                      min="0"
                      class="w-full text-center"
                    />
                  </td>
                  <td class="px-4 py-3">
                    <FormField
                      v-model="siswa.nilai_pkl_form.keterangan"
                      type="select"
                      placeholder="Pilih Keterangan"
                      :options="keteranganOptions"
                      option-value="value"
                      option-label="label"
                      class="w-full"
                    />
                  </td>
                </tr>
              </tbody>
            </table>

          <!-- Empty State -->
          <div v-if="siswaList.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada siswa</h3>
            <p class="mt-1 text-sm text-gray-500">Tidak ada siswa aktif di kelas yang dipilih.</p>
          </div>

          <!-- Save Button -->
          <div v-if="siswaList.length > 0" class="mt-6 flex justify-end">
            <button
              @click="saveNilaiPkl"
              :disabled="saving"
              class="btn btn-primary"
            >
              <svg v-if="saving" class="inline-block animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'

const toast = useToast()

// State
const canAccess = ref(false)
const accessMessage = ref('')
const loading = ref(false)
const saving = ref(false)
const selectedKelas = ref('')
const kelasOptions = ref([])
const siswaList = ref([])
const pklOptions = ref([])
const loadingPkl = ref(false)
const inputRefs = ref(new Map())

// Keterangan options
const keteranganOptions = [
  { value: 'Cukup', label: 'Cukup' },
  { value: 'Baik', label: 'Baik' },
  { value: 'Sangat Baik', label: 'Sangat Baik' }
]

// Methods
const loadData = async () => {
  // First load to check access
  if (!canAccess.value && !loading.value) {
    await checkAccess()
  }

  if (!selectedKelas.value) {
    siswaList.value = []
    return
  }

  if (!canAccess.value) {
    return
  }

  try {
    loading.value = true
    const response = await axios.get('/wali-kelas/nilai-pkl', {
      params: {
        kelas_id: selectedKelas.value
      }
    })

    if (!response.data.can_access) {
      canAccess.value = false
      accessMessage.value = response.data.message || 'Kelas Anda tidak bisa menginputkan nilai PKL. Hanya wali kelas tingkat 12 yang dapat menginput nilai PKL.'
      return
    }

    canAccess.value = true
    kelasOptions.value = response.data.kelas || []
    
    // Get jurusan_id from selected kelas
    const selectedKelasData = kelasOptions.value.find(k => k.id === selectedKelas.value)
    if (selectedKelasData && selectedKelasData.jurusan_id) {
      await fetchPklOptions(selectedKelasData.jurusan_id)
    }

    // Map siswa with form data (after pklOptions is loaded)
    siswaList.value = (response.data.siswa || []).map(siswa => {
      // Find matching PKL by nama_du_di
      let pklId = ''
      let pklSearchQuery = ''
      if (siswa.nilai_pkl?.nama_du_di) {
        const matchedPkl = pklOptions.value.find(p => p.nama_perusahaan === siswa.nilai_pkl.nama_du_di)
        if (matchedPkl) {
          pklId = matchedPkl.id
          pklSearchQuery = matchedPkl.nama_perusahaan
        } else {
          // If not found in options, still show the saved value
          pklSearchQuery = siswa.nilai_pkl.nama_du_di
        }
      }
      
      return {
        ...siswa,
        showPklDropdown: false,
        pklSearchQuery: pklSearchQuery,
        nilai_pkl_form: {
          pkl_id: pklId,
          nama_du_di: siswa.nilai_pkl?.nama_du_di || '',
          lamanya_bulan: siswa.nilai_pkl?.lamanya_bulan || '',
          keterangan: siswa.nilai_pkl?.keterangan || ''
        }
      }
    })
  } catch (error) {
    console.error('Failed to load data:', error)
    if (error.response?.status === 403) {
      canAccess.value = false
      accessMessage.value = error.response?.data?.message || 'Kelas Anda tidak bisa menginputkan nilai PKL. Hanya wali kelas tingkat 12 yang dapat menginput nilai PKL.'
    } else {
      toast.error(error.response?.data?.message || 'Gagal mengambil data')
    }
  } finally {
    loading.value = false
  }
}

const checkAccess = async () => {
  try {
    loading.value = true
    const response = await axios.get('/wali-kelas/nilai-pkl')
    
    if (!response.data.can_access) {
      canAccess.value = false
      accessMessage.value = response.data.message || 'Kelas Anda tidak bisa menginputkan nilai PKL. Hanya wali kelas tingkat 12 yang dapat menginput nilai PKL.'
    } else {
      canAccess.value = true
      kelasOptions.value = response.data.kelas || []
      
      // Auto-select first kelas if available
      if (kelasOptions.value.length > 0 && !selectedKelas.value) {
        selectedKelas.value = kelasOptions.value[0].id
      }
    }
  } catch (error) {
    console.error('Failed to check access:', error)
    if (error.response?.status === 403) {
      canAccess.value = false
      accessMessage.value = error.response?.data?.message || 'Kelas Anda tidak bisa menginputkan nilai PKL. Hanya wali kelas tingkat 12 yang dapat menginput nilai PKL.'
    } else {
      toast.error(error.response?.data?.message || 'Gagal memeriksa akses')
    }
  } finally {
    loading.value = false
  }
}

const onKelasChange = () => {
  pklOptions.value = []
  loadData()
}

const fetchPklOptions = async (jurusanId) => {
  if (!jurusanId) {
    pklOptions.value = []
    return
  }

  try {
    loadingPkl.value = true
    const response = await axios.get('/wali-kelas/nilai-pkl/pkl-by-jurusan', {
      params: {
        jurusan_id: jurusanId
      }
    })
    
    pklOptions.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch PKL options:', error)
    toast.error('Gagal mengambil data DU/DI')
    pklOptions.value = []
  } finally {
    loadingPkl.value = false
  }
}

const getPklLabel = (pklId) => {
  if (!pklId) return ''
  const pkl = pklOptions.value.find(p => p.id == pklId)
  return pkl ? pkl.nama_perusahaan : ''
}

const setInputRef = (el, siswa) => {
  if (el) {
    inputRefs.value.set(siswa.id, el)
  }
}

const getDropdownStyle = (siswa) => {
  const containerEl = inputRefs.value.get(siswa.id)
  if (!containerEl) return {}
  
  const inputEl = containerEl.querySelector('input')
  if (!inputEl) return {}
  
  const rect = inputEl.getBoundingClientRect()
  return {
    top: `${rect.bottom + window.scrollY + 4}px`,
    left: `${rect.left + window.scrollX}px`,
    width: `${rect.width}px`,
  }
}

const showPklDropdown = (siswa, event) => {
  if (siswa.pklSearchQuery === undefined) {
    siswa.pklSearchQuery = getPklLabel(siswa.nilai_pkl_form.pkl_id)
  }
  siswa.showPklDropdown = true
}

const searchPkl = (event, siswa) => {
  siswa.pklSearchQuery = event.target.value
  siswa.showPklDropdown = true
}

const handleInputBlur = (siswa) => {
  // Delay to allow click on dropdown item
  setTimeout(() => {
    if (siswa.nilai_pkl_form.pkl_id) {
      siswa.pklSearchQuery = getPklLabel(siswa.nilai_pkl_form.pkl_id)
    } else {
      siswa.pklSearchQuery = ''
    }
    siswa.showPklDropdown = false
  }, 200)
}

const getFilteredPkl = (siswa) => {
  if (!siswa.pklSearchQuery) {
    return pklOptions.value
  }
  const query = siswa.pklSearchQuery.toLowerCase()
  return pklOptions.value.filter(p => 
    p.nama_perusahaan.toLowerCase().includes(query) ||
    p.alamat_perusahaan.toLowerCase().includes(query)
  )
}

const selectPkl = (siswa, pkl) => {
  siswa.nilai_pkl_form.pkl_id = pkl.id
  siswa.nilai_pkl_form.nama_du_di = pkl.nama_perusahaan
  siswa.pklSearchQuery = pkl.nama_perusahaan
  siswa.showPklDropdown = false
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest('.relative')) {
    siswaList.value.forEach(siswa => {
      siswa.showPklDropdown = false
    })
  }
}

const saveNilaiPkl = async () => {
  if (!selectedKelas.value) {
    toast.error('Pilih kelas terlebih dahulu')
    return
  }

  try {
    saving.value = true

    // Get tahun ajaran aktif untuk menentukan semester
    const tahunAjaranResponse = await axios.get('/lookup/tahun-ajaran-aktif')
    const semester = tahunAjaranResponse.data?.semester || '1'

    // Prepare data
    const nilaiPklData = siswaList.value.map(siswa => ({
      siswa_id: siswa.id,
      kelas_id: siswa.kelas.id,
      semester: semester,
      nama_du_di: siswa.nilai_pkl_form.nama_du_di || null,
      lamanya_bulan: siswa.nilai_pkl_form.lamanya_bulan ? parseInt(siswa.nilai_pkl_form.lamanya_bulan) : null,
      keterangan: siswa.nilai_pkl_form.keterangan || null
    }))

    const response = await axios.post('/wali-kelas/nilai-pkl', {
      nilai_pkl: nilaiPklData
    })

    toast.success(response.data.message || 'Nilai PKL berhasil disimpan')
    await loadData() // Reload data
  } catch (error) {
    console.error('Failed to save:', error)
    toast.error(error.response?.data?.message || 'Gagal menyimpan nilai PKL')
  } finally {
    saving.value = false
  }
}

// Lifecycle
onMounted(async () => {
  document.addEventListener('click', handleClickOutside)
  await checkAccess()
  if (canAccess.value && selectedKelas.value) {
    await loadData()
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
