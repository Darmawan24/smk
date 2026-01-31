<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Catatan Wali Kelas
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Input catatan wali kelas untuk masing-masing siswa. Catatan ini akan muncul di rapor.
        </p>
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
      <div v-else-if="!selectedKelas" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih Kelas</h3>
        <p class="mt-1 text-sm text-gray-500">Pilih kelas untuk mulai input catatan wali kelas.</p>
      </div>

      <!-- Siswa Table with Catatan -->
      <div v-else class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <h3 class="text-lg font-medium text-gray-900">
            Catatan Wali Kelas {{ selectedKelasName }}
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            Tahun Ajaran: {{ tahunAjaran?.tahun || '-' }}
          </p>
        </div>

        <div class="overflow-x-auto">
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
                  NIS
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[300px]">
                  Catatan
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(row, index) in siswaData" :key="row.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-center text-sm text-gray-500">
                  {{ index + 1 }}
                </td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                  {{ row.nama_lengkap }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-500">
                  {{ row.nis }}
                </td>
                <td class="px-4 py-3">
                  <textarea
                    v-model="row.catatan_text"
                    rows="3"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Masukkan catatan wali kelas..."
                    @input="markAsChanged(row.id)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="siswaData.length === 0 && !loading" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada siswa</h3>
          <p class="mt-1 text-sm text-gray-500">Kelas ini belum memiliki siswa aktif.</p>
        </div>

        <!-- Save Button -->
        <div v-if="siswaData.length > 0" class="px-4 py-5 border-t border-gray-200 sm:px-6 flex justify-end">
          <button
            @click="batchUpdate"
            :disabled="!hasChanges || updating"
            class="btn btn-primary"
          >
            <svg v-if="updating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ updating ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'

const toast = useToast()

// Data
const kelasOptions = ref([])
const siswaData = ref([])
const changedItems = ref(new Set())
const tahunAjaran = ref(null)

// State
const loading = ref(false)
const updating = ref(false)
const selectedKelas = ref('')

// Computed
const selectedKelasName = computed(() => {
  const kelas = kelasOptions.value.find(k => k.id == selectedKelas.value)
  return kelas?.nama_kelas || ''
})

const hasChanges = computed(() => changedItems.value.size > 0)

// Methods
const fetchKelas = async () => {
  try {
    const response = await axios.get('/wali-kelas/catatan-akademik/list')
    kelasOptions.value = response.data.kelas || []
  } catch (error) {
    console.error('Failed to fetch kelas:', error)
    toast.error('Gagal mengambil data kelas')
  }
}

const loadSiswa = async () => {
  if (!selectedKelas.value) {
    siswaData.value = []
    return
  }

  try {
    loading.value = true
    const response = await axios.get('/wali-kelas/catatan-akademik/list', {
      params: { kelas_id: selectedKelas.value }
    })
    tahunAjaran.value = response.data.tahun_ajaran
    siswaData.value = (response.data.siswa || []).map(s => ({
      ...s,
      catatan_text: s.catatan_akademik?.catatan ?? ''
    }))
    changedItems.value.clear()
  } catch (error) {
    toast.error('Gagal mengambil data siswa')
    console.error(error)
    siswaData.value = []
  } finally {
    loading.value = false
  }
}

const markAsChanged = (id) => {
  changedItems.value.add(id)
}

const batchUpdate = async () => {
  if (!hasChanges.value || !tahunAjaran.value) return

  try {
    updating.value = true
    const catatan = siswaData.value.map(s => ({
      siswa_id: s.id,
      catatan: s.catatan_text || ''
    }))
    await axios.post('/wali-kelas/catatan-akademik/batch-update', {
      tahun_ajaran_id: tahunAjaran.value.id,
      catatan
    })
    toast.success('Catatan wali kelas berhasil disimpan')
    changedItems.value.clear()
    await loadSiswa()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyimpan catatan')
    console.error(error)
  } finally {
    updating.value = false
  }
}

// Lifecycle
onMounted(async () => {
  await fetchKelas()
})
</script>
