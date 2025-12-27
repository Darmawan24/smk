<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Nilai Kelas
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Monitoring nilai siswa di kelas Anda
          </p>
        </div>
        <button 
          @click="showRekap = true" 
          class="btn btn-secondary"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          Lihat Rekap
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
            @update:model-value="loadNilai"
          />
          <FormField
            v-model="selectedTahunAjaran"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="tahun"
            @update:model-value="loadNilai"
          />
          <FormField
            v-model="selectedSiswa"
            type="select"
            label="Siswa (Opsional)"
            placeholder="Pilih Siswa"
            :options="siswaOptions"
            option-value="id"
            option-label="nama_lengkap"
            @update:model-value="loadNilai"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data nilai...</p>
      </div>

      <!-- No Selection State -->
      <div v-else-if="!selectedKelas || !selectedTahunAjaran" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih Kelas dan Tahun Ajaran</h3>
        <p class="mt-1 text-sm text-gray-500">Pilih kelas dan tahun ajaran untuk melihat nilai siswa.</p>
      </div>

      <!-- Nilai Table -->
      <div v-else class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-medium text-gray-900">
                Nilai {{ selectedKelasName }}
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Tahun Ajaran: {{ selectedTahunAjaranName }}
              </p>
            </div>
            <div class="flex space-x-2">
              <button 
                @click="viewBySiswa = !viewBySiswa" 
                class="btn btn-sm btn-secondary"
              >
                {{ viewBySiswa ? 'Tampilkan Semua' : 'Tampilkan per Siswa' }}
              </button>
            </div>
          </div>
        </div>

        <!-- View by Mata Pelajaran -->
        <div v-if="!viewBySiswa" class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="w-16">No</th>
                <th>Mata Pelajaran</th>
                <th v-for="siswa in siswaList" :key="siswa.id" class="text-center min-w-[120px]">
                  <div class="flex flex-col items-center">
                    <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-medium mb-1">
                      {{ siswa.nama_lengkap?.charAt(0) }}
                    </div>
                    <div class="text-xs text-gray-600">{{ siswa.nama_lengkap }}</div>
                  </div>
                </th>
                <th class="text-center">Rata-rata</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(mapel, index) in mapelList" :key="mapel.id">
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <div class="font-medium text-gray-900">{{ mapel.nama_mapel }}</div>
                  <div class="text-sm text-gray-500">{{ mapel.kode_mapel }}</div>
                </td>
                <td v-for="siswa in siswaList" :key="siswa.id" class="text-center">
                  <span v-if="getNilai(siswa.id, mapel.id)" 
                        :class="getNilaiColor(getNilai(siswa.id, mapel.id))"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getNilai(siswa.id, mapel.id) }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="text-center font-medium">
                  {{ getRataRataMapel(mapel.id) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- View by Siswa -->
        <div v-else class="space-y-6 p-6">
          <div v-for="siswa in siswaList" :key="siswa.id" class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center">
                <div class="h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-medium">
                  {{ siswa.nama_lengkap?.charAt(0) }}
                </div>
                <div class="ml-3">
                  <div class="font-medium text-gray-900">{{ siswa.nama_lengkap }}</div>
                  <div class="text-sm text-gray-500">NIS: {{ siswa.nis }}</div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-500">Rata-rata</div>
                <div class="text-lg font-medium text-gray-900">{{ getRataRataSiswa(siswa.id) }}</div>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="table">
                <thead>
                  <tr>
                    <th class="w-16">No</th>
                    <th>Mata Pelajaran</th>
                    <th class="text-center">Nilai Rapor</th>
                    <th class="text-center">Predikat</th>
                    <th>Deskripsi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(mapel, index) in mapelList" :key="mapel.id">
                    <td class="text-center">{{ index + 1 }}</td>
                    <td>
                      <div class="font-medium text-gray-900">{{ mapel.nama_mapel }}</div>
                    </td>
                    <td class="text-center">
                      <span v-if="getNilai(siswa.id, mapel.id)" 
                            :class="getNilaiColor(getNilai(siswa.id, mapel.id))"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                        {{ getNilai(siswa.id, mapel.id) }}
                      </span>
                      <span v-else class="text-gray-400">-</span>
                    </td>
                    <td class="text-center">
                      <span v-if="getPredikat(siswa.id, mapel.id)" 
                            :class="getPredikatColor(getPredikat(siswa.id, mapel.id))"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                        {{ getPredikat(siswa.id, mapel.id) }}
                      </span>
                      <span v-else class="text-gray-400">-</span>
                    </td>
                    <td class="text-sm text-gray-500">
                      {{ getDeskripsi(siswa.id, mapel.id) || '-' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="siswaList.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada data siswa</h3>
          <p class="mt-1 text-sm text-gray-500">Pastikan kelas memiliki siswa aktif.</p>
        </div>
      </div>

      <!-- Rekap Modal -->
      <Modal v-model:show="showRekap" title="Rekap Nilai Kelas" size="xl">
        <div v-if="rekapData" class="space-y-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="bg-blue-50 p-4 rounded-lg">
              <div class="text-sm text-blue-600 font-medium">Total Siswa</div>
              <div class="text-2xl font-bold text-blue-900">{{ rekapData.length }}</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
              <div class="text-sm text-green-600 font-medium">Rata-rata Kelas</div>
              <div class="text-2xl font-bold text-green-900">{{ getRataRataKelas() }}</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
              <div class="text-sm text-yellow-600 font-medium">Total Mapel</div>
              <div class="text-2xl font-bold text-yellow-900">{{ mapelList.length }}</div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th class="w-16">No</th>
                  <th>Nama Siswa</th>
                  <th>NIS</th>
                  <th class="text-center">Total Mapel</th>
                  <th class="text-center">Rata-rata</th>
                  <th class="text-center">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in rekapData" :key="item.siswa.id">
                  <td class="text-center">{{ index + 1 }}</td>
                  <td>
                    <div class="flex items-center">
                      <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                        {{ item.siswa.nama_lengkap?.charAt(0) }}
                      </div>
                      <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900">{{ item.siswa.nama_lengkap }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="text-sm text-gray-500">{{ item.siswa.nis }}</td>
                  <td class="text-center">{{ item.total_mapel }}</td>
                  <td class="text-center font-medium">{{ item.rata_rata }}</td>
                  <td class="text-center">
                    <span :class="getStatusColor(item.rata_rata)"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                      {{ getStatus(item.rata_rata) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-2 text-sm text-gray-500">Memuat rekap...</p>
        </div>
      </Modal>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'
import Modal from '../../../components/ui/Modal.vue'

const toast = useToast()

// Data
const kelasOptions = ref([])
const tahunAjaranOptions = ref([])
const siswaOptions = ref([])
const siswaList = ref([])
const mapelList = ref([])
const nilaiData = ref({})
const rekapData = ref(null)

// State
const loading = ref(false)
const selectedKelas = ref('')
const selectedTahunAjaran = ref('')
const selectedSiswa = ref('')
const viewBySiswa = ref(false)
const showRekap = ref(false)

// Computed
const selectedKelasName = computed(() => {
  const kelas = kelasOptions.value.find(k => k.id == selectedKelas.value)
  return kelas?.nama_kelas || ''
})

const selectedTahunAjaranName = computed(() => {
  const ta = tahunAjaranOptions.value.find(t => t.id == selectedTahunAjaran.value)
  return ta ? `${ta.tahun} - Semester ${ta.semester}` : ''
})

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

const fetchMapel = async () => {
  try {
    const response = await axios.get('/lookup/mata-pelajaran')
    mapelList.value = response.data
  } catch (error) {
    console.error('Failed to fetch mata pelajaran:', error)
  }
}

const loadNilai = async () => {
  if (!selectedKelas.value || !selectedTahunAjaran.value) {
    siswaList.value = []
    nilaiData.value = {}
    return
  }

  try {
    loading.value = true
    
    let response
    if (selectedSiswa.value) {
      response = await axios.get(`/wali-kelas/nilai-kelas/siswa/${selectedSiswa.value}`, {
        params: { tahun_ajaran_id: selectedTahunAjaran.value }
      })
      siswaList.value = response.data.siswa ? [response.data.siswa] : []
      nilaiData.value = { [selectedSiswa.value]: response.data.nilai || [] }
    } else {
      response = await axios.get('/wali-kelas/nilai-kelas', {
        params: {
          kelas_id: selectedKelas.value,
          tahun_ajaran_id: selectedTahunAjaran.value
        }
      })
      nilaiData.value = response.data.nilai || {}
      
      // Extract siswa list
      const siswaIds = new Set()
      Object.values(nilaiData.value).forEach(nilaiArray => {
        nilaiArray.forEach(nilai => {
          if (nilai.siswa) {
            siswaIds.add(nilai.siswa.id)
          }
        })
      })
      
      // Load siswa details
      const siswaResponse = await axios.get('/admin/siswa', {
        params: {
          kelas_id: selectedKelas.value,
          status: 'aktif',
          per_page: 1000
        }
      })
      siswaList.value = (siswaResponse.data.data || siswaResponse.data).filter(s => siswaIds.has(s.id))
      siswaOptions.value = siswaResponse.data.data || siswaResponse.data
    }
  } catch (error) {
    toast.error('Gagal mengambil data nilai')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const loadRekap = async () => {
  if (!selectedKelas.value || !selectedTahunAjaran.value) return

  try {
    const response = await axios.get('/wali-kelas/nilai-kelas/rekap', {
      params: {
        kelas_id: selectedKelas.value,
        tahun_ajaran_id: selectedTahunAjaran.value
      }
    })
    rekapData.value = response.data.rekap || []
  } catch (error) {
    toast.error('Gagal mengambil data rekap')
    console.error(error)
  }
}

const getNilai = (siswaId, mapelId) => {
  const siswaNilai = nilaiData.value[siswaId] || []
  const nilai = siswaNilai.find(n => n.mata_pelajaran_id === mapelId)
  return nilai?.nilai_rapor ? nilai.nilai_rapor.toFixed(2) : null
}

const getPredikat = (siswaId, mapelId) => {
  const siswaNilai = nilaiData.value[siswaId] || []
  const nilai = siswaNilai.find(n => n.mata_pelajaran_id === mapelId)
  return nilai?.predikat || null
}

const getDeskripsi = (siswaId, mapelId) => {
  const siswaNilai = nilaiData.value[siswaId] || []
  const nilai = siswaNilai.find(n => n.mata_pelajaran_id === mapelId)
  return nilai?.deskripsi || null
}

const getRataRataSiswa = (siswaId) => {
  const siswaNilai = nilaiData.value[siswaId] || []
  if (siswaNilai.length === 0) return '-'
  const total = siswaNilai.reduce((sum, n) => sum + (n.nilai_rapor || 0), 0)
  return (total / siswaNilai.length).toFixed(2)
}

const getRataRataMapel = (mapelId) => {
  const nilai = siswaList.value.map(siswa => {
    const siswaNilai = nilaiData.value[siswa.id] || []
    const n = siswaNilai.find(n => n.mata_pelajaran_id === mapelId)
    return n?.nilai_rapor || 0
  }).filter(n => n > 0)
  
  if (nilai.length === 0) return '-'
  const total = nilai.reduce((sum, n) => sum + n, 0)
  return (total / nilai.length).toFixed(2)
}

const getRataRataKelas = () => {
  if (!rekapData.value || rekapData.value.length === 0) return '-'
  const total = rekapData.value.reduce((sum, item) => sum + parseFloat(item.rata_rata || 0), 0)
  return (total / rekapData.value.length).toFixed(2)
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

const getStatus = (rataRata) => {
  const num = parseFloat(rataRata)
  if (num >= 85) return 'Sangat Baik'
  if (num >= 75) return 'Baik'
  if (num >= 65) return 'Cukup'
  return 'Perlu Perhatian'
}

const getStatusColor = (rataRata) => {
  const num = parseFloat(rataRata)
  if (num >= 85) return 'bg-green-100 text-green-800'
  if (num >= 75) return 'bg-blue-100 text-blue-800'
  if (num >= 65) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

watch(showRekap, (newVal) => {
  if (newVal) {
    loadRekap()
  }
})

// Lifecycle
onMounted(() => {
  fetchKelas()
  fetchTahunAjaran()
  fetchMapel()
})
</script>
