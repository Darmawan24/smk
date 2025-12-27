<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Kehadiran Siswa
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Input dan kelola data kehadiran siswa di kelas Anda
          </p>
        </div>
        <button 
          @click="batchUpdate" 
          :disabled="!hasChanges || updating"
          class="btn btn-primary"
        >
          <svg v-if="updating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ updating ? 'Menyimpan...' : 'Simpan Semua' }}
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
            @update:model-value="loadKehadiran"
          />
          <FormField
            v-model="selectedTahunAjaran"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="tahun"
            @update:model-value="loadKehadiran"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data kehadiran...</p>
      </div>

      <!-- No Selection State -->
      <div v-else-if="!selectedKelas || !selectedTahunAjaran" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih Kelas dan Tahun Ajaran</h3>
        <p class="mt-1 text-sm text-gray-500">Pilih kelas dan tahun ajaran untuk mulai input kehadiran.</p>
      </div>

      <!-- Kehadiran Table -->
      <div v-else class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">
                Kehadiran {{ selectedKelasName }}
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Tahun Ajaran: {{ selectedTahunAjaranName }}
              </p>
            </div>
            <div v-if="hasChanges" class="text-sm text-blue-600 font-medium">
              Ada perubahan yang belum disimpan
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
                <th class="w-32 text-center">Sakit</th>
                <th class="w-32 text-center">Izin</th>
                <th class="w-32 text-center">Tanpa Keterangan</th>
                <th class="w-32 text-center">Total Absen</th>
                <th class="w-32 text-center">Persentase</th>
                <th class="w-32 text-center">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(kehadiran, index) in kehadiranData" :key="kehadiran.id" class="hover:bg-gray-50">
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <div class="flex items-center">
                    <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                      {{ kehadiran.siswa?.nama_lengkap?.charAt(0) }}
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-gray-900">{{ kehadiran.siswa?.nama_lengkap }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-sm text-gray-500">{{ kehadiran.siswa?.nis }}</td>
                <td>
                  <input
                    v-model.number="kehadiran.sakit"
                    type="number"
                    min="0"
                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-center"
                    @input="markAsChanged(kehadiran.id)"
                  />
                </td>
                <td>
                  <input
                    v-model.number="kehadiran.izin"
                    type="number"
                    min="0"
                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-center"
                    @input="markAsChanged(kehadiran.id)"
                  />
                </td>
                <td>
                  <input
                    v-model.number="kehadiran.tanpa_keterangan"
                    type="number"
                    min="0"
                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-center"
                    @input="markAsChanged(kehadiran.id)"
                  />
                </td>
                <td class="text-center font-medium">
                  {{ getTotalAbsen(kehadiran) }}
                </td>
                <td class="text-center">
                  <span :class="getPersentaseColor(getPersentase(kehadiran))"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getPersentase(kehadiran) }}%
                  </span>
                </td>
                <td class="text-center">
                  <span :class="getStatusColor(getPersentase(kehadiran))"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getStatus(kehadiran) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div class="px-4 py-5 border-t border-gray-200 sm:px-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="bg-blue-50 p-4 rounded-lg">
              <div class="text-sm text-blue-600 font-medium">Total Siswa</div>
              <div class="text-2xl font-bold text-blue-900">{{ kehadiranData.length }}</div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
              <div class="text-sm text-red-600 font-medium">Total Sakit</div>
              <div class="text-2xl font-bold text-red-900">{{ getTotalSakit() }}</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
              <div class="text-sm text-yellow-600 font-medium">Total Izin</div>
              <div class="text-2xl font-bold text-yellow-900">{{ getTotalIzin() }}</div>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
              <div class="text-sm text-orange-600 font-medium">Total Tanpa Keterangan</div>
              <div class="text-2xl font-bold text-orange-900">{{ getTotalTanpaKeterangan() }}</div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="kehadiranData.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada data kehadiran</h3>
          <p class="mt-1 text-sm text-gray-500">Pastikan kelas memiliki siswa aktif.</p>
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
const tahunAjaranOptions = ref([])
const kehadiranData = ref([])
const changedItems = ref(new Set())

// State
const loading = ref(false)
const updating = ref(false)
const selectedKelas = ref('')
const selectedTahunAjaran = ref('')

// Computed
const selectedKelasName = computed(() => {
  const kelas = kelasOptions.value.find(k => k.id == selectedKelas.value)
  return kelas?.nama_kelas || ''
})

const selectedTahunAjaranName = computed(() => {
  const ta = tahunAjaranOptions.value.find(t => t.id == selectedTahunAjaran.value)
  return ta ? `${ta.tahun} - Semester ${ta.semester}` : ''
})

const hasChanges = computed(() => changedItems.value.size > 0)

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

const loadKehadiran = async () => {
  if (!selectedKelas.value || !selectedTahunAjaran.value) {
    kehadiranData.value = []
    return
  }

  try {
    loading.value = true
    const response = await axios.get('/wali-kelas/kehadiran', {
      params: {
        kelas_id: selectedKelas.value,
        tahun_ajaran_id: selectedTahunAjaran.value
      }
    })
    kehadiranData.value = response.data.kehadiran || []
    changedItems.value.clear()
  } catch (error) {
    toast.error('Gagal mengambil data kehadiran')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const markAsChanged = (id) => {
  changedItems.value.add(id)
}

const getTotalAbsen = (kehadiran) => {
  return (kehadiran.sakit || 0) + (kehadiran.izin || 0) + (kehadiran.tanpa_keterangan || 0)
}

const getPersentase = (kehadiran) => {
  const totalAbsen = getTotalAbsen(kehadiran)
  const totalHari = 180 // Asumsi 180 hari efektif
  const hadir = totalHari - totalAbsen
  const persentase = (hadir / totalHari) * 100
  return Math.max(0, Math.min(100, Math.round(persentase)))
}

const getStatus = (kehadiran) => {
  const persentase = getPersentase(kehadiran)
  if (persentase >= 95) return 'Sangat Baik'
  if (persentase >= 85) return 'Baik'
  if (persentase >= 75) return 'Cukup'
  return 'Kurang'
}

const getPersentaseColor = (persentase) => {
  if (persentase >= 95) return 'bg-green-100 text-green-800'
  if (persentase >= 85) return 'bg-blue-100 text-blue-800'
  if (persentase >= 75) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

const getStatusColor = (persentase) => {
  if (persentase >= 95) return 'bg-green-100 text-green-800'
  if (persentase >= 85) return 'bg-blue-100 text-blue-800'
  if (persentase >= 75) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

const getTotalSakit = () => {
  return kehadiranData.value.reduce((sum, k) => sum + (k.sakit || 0), 0)
}

const getTotalIzin = () => {
  return kehadiranData.value.reduce((sum, k) => sum + (k.izin || 0), 0)
}

const getTotalTanpaKeterangan = () => {
  return kehadiranData.value.reduce((sum, k) => sum + (k.tanpa_keterangan || 0), 0)
}

const batchUpdate = async () => {
  if (!hasChanges.value) return

  try {
    updating.value = true
    
    const changedData = kehadiranData.value
      .filter(item => changedItems.value.has(item.id))
      .map(item => ({
        id: item.id,
        sakit: item.sakit || 0,
        izin: item.izin || 0,
        tanpa_keterangan: item.tanpa_keterangan || 0
      }))

    await axios.post('/wali-kelas/kehadiran/batch-update', {
      kehadiran: changedData
    })

    toast.success('Kehadiran berhasil disimpan')
    changedItems.value.clear()
    
    // Reload data
    await loadKehadiran()
  } catch (error) {
    toast.error('Gagal menyimpan kehadiran')
    console.error(error)
  } finally {
    updating.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchKelas()
  fetchTahunAjaran()
})
</script>
