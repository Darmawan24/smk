<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Cek Penilaian
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Monitoring penilaian siswa di kelas Anda
          </p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-white shadow rounded-lg mb-6">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
              ]"
            >
              {{ tab.label }}
            </button>
          </nav>
        </div>
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
            @update:model-value="loadData"
          />
          <FormField
            v-model="selectedTahunAjaran"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="tahun"
            @update:model-value="loadData"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
      </div>

      <!-- STS Tab -->
      <div v-else-if="activeTab === 'sts'" class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <h3 class="text-lg font-medium text-gray-900">Nilai STS (Sumatif Tengah Semester)</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="w-16">No</th>
                <th>Nama Siswa</th>
                <th>NIS</th>
                <th v-for="mapel in mapelList" :key="mapel.id" class="text-center">
                  {{ mapel.nama_mapel }}
                </th>
                <th class="text-center">Rata-rata</th>
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
                    </div>
                  </div>
                </td>
                <td class="text-sm text-gray-500">{{ siswa.nis }}</td>
                <td v-for="mapel in mapelList" :key="mapel.id" class="text-center">
                  <span v-if="getNilaiSTS(siswa.id, mapel.id)" 
                        :class="getNilaiColor(getNilaiSTS(siswa.id, mapel.id))"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getNilaiSTS(siswa.id, mapel.id) }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="text-center font-medium">
                  {{ getRataRataSTS(siswa.id) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- SAS Tab -->
      <div v-else-if="activeTab === 'sas'" class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <h3 class="text-lg font-medium text-gray-900">Nilai SAS (Sumatif Akhir Semester)</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="w-16">No</th>
                <th>Nama Siswa</th>
                <th>NIS</th>
                <th v-for="mapel in mapelList" :key="mapel.id" class="text-center">
                  {{ mapel.nama_mapel }}
                </th>
                <th class="text-center">Rata-rata</th>
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
                    </div>
                  </div>
                </td>
                <td class="text-sm text-gray-500">{{ siswa.nis }}</td>
                <td v-for="mapel in mapelList" :key="mapel.id" class="text-center">
                  <span v-if="getNilaiSAS(siswa.id, mapel.id)" 
                        :class="getNilaiColor(getNilaiSAS(siswa.id, mapel.id))"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getNilaiSAS(siswa.id, mapel.id) }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="text-center font-medium">
                  {{ getRataRataSAS(siswa.id) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- P5 Tab -->
      <div v-else-if="activeTab === 'p5'" class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <h3 class="text-lg font-medium text-gray-900">Nilai P5 (Projek Penguatan Profil Pelajar Pancasila)</h3>
        </div>
        <div class="p-6">
          <div v-if="p5List.length === 0" class="text-center py-8">
            <p class="text-sm text-gray-500">Belum ada projek P5</p>
          </div>
          <div v-else class="space-y-6">
            <div v-for="p5 in p5List" :key="p5.id" class="border border-gray-200 rounded-lg p-4">
              <h4 class="font-medium text-gray-900 mb-4">{{ p5.tema }}</h4>
              <div class="overflow-x-auto">
                <table class="table">
                  <thead>
                    <tr>
                      <th class="w-16">No</th>
                      <th>Nama Siswa</th>
                      <th v-for="dimensi in dimensiList" :key="dimensi.id" class="text-center">
                        {{ dimensi.nama }}
                      </th>
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
                          </div>
                        </div>
                      </td>
                      <td v-for="dimensi in dimensiList" :key="dimensi.id" class="text-center">
                        <span v-if="getNilaiP5(siswa.id, p5.id, dimensi.id)" 
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          {{ getNilaiP5(siswa.id, p5.id, dimensi.id) }}
                        </span>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'

const toast = useToast()

// Data
const kelasOptions = ref([])
const tahunAjaranOptions = ref([])
const siswaList = ref([])
const mapelList = ref([])
const p5List = ref([])
const dimensiList = ref([])
const nilaiData = ref([])
const p5NilaiData = ref([])

// State
const loading = ref(false)
const activeTab = ref('sts')
const selectedKelas = ref('')
const selectedTahunAjaran = ref('')

// Tabs
const tabs = [
  { id: 'sts', label: 'Nilai STS' },
  { id: 'sas', label: 'Nilai SAS' },
  { id: 'p5', label: 'Nilai P5' }
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

const fetchMapel = async () => {
  try {
    const response = await axios.get('/lookup/mata-pelajaran')
    mapelList.value = response.data
  } catch (error) {
    console.error('Failed to fetch mata pelajaran:', error)
  }
}

const fetchDimensi = async () => {
  try {
    const response = await axios.get('/lookup/dimensi-p5')
    dimensiList.value = response.data
  } catch (error) {
    console.error('Failed to fetch dimensi:', error)
  }
}

const loadData = async () => {
  if (!selectedKelas.value || !selectedTahunAjaran.value) {
    siswaList.value = []
    nilaiData.value = []
    return
  }

  try {
    loading.value = true
    
    // Load nilai kelas
    const response = await axios.get('/wali-kelas/nilai-kelas', {
      params: {
        kelas_id: selectedKelas.value,
        tahun_ajaran_id: selectedTahunAjaran.value
      }
    })
    
    nilaiData.value = response.data.nilai || {}
    
    // Extract siswa list from nilai data
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
    siswaList.value = siswaResponse.data.data || siswaResponse.data
    
    // Load P5 data
    if (activeTab.value === 'p5') {
      await loadP5Data()
    }
  } catch (error) {
    toast.error('Gagal mengambil data')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const loadP5Data = async () => {
  try {
    // Load P5 projects
    const p5Response = await axios.get('/guru/p5', {
      params: { tahun_ajaran_id: selectedTahunAjaran.value }
    })
    p5List.value = p5Response.data.data || p5Response.data
    
    // Load P5 nilai for each project
    for (const p5 of p5List.value) {
      try {
        const nilaiResponse = await axios.get(`/guru/p5/${p5.id}/nilai`)
        p5NilaiData.value[p5.id] = nilaiResponse.data.nilai || {}
      } catch (error) {
        console.error(`Failed to load nilai for P5 ${p5.id}:`, error)
      }
    }
  } catch (error) {
    console.error('Failed to load P5 data:', error)
  }
}

const getNilaiSTS = (siswaId, mapelId) => {
  const siswaNilai = nilaiData.value[siswaId] || []
  const nilai = siswaNilai.find(n => n.mata_pelajaran_id === mapelId)
  if (!nilai) return null
  
  // STS is typically nilai_sumatif_1, nilai_sumatif_2, nilai_sumatif_3
  const sumatif = [
    nilai.nilai_sumatif_1,
    nilai.nilai_sumatif_2,
    nilai.nilai_sumatif_3
  ].filter(n => n !== null && n !== undefined)
  
  if (sumatif.length === 0) return null
  return (sumatif.reduce((a, b) => a + b, 0) / sumatif.length).toFixed(2)
}

const getNilaiSAS = (siswaId, mapelId) => {
  const siswaNilai = nilaiData.value[siswaId] || []
  const nilai = siswaNilai.find(n => n.mata_pelajaran_id === mapelId)
  if (!nilai) return null
  
  // SAS is typically nilai_sumatif_4, nilai_sumatif_5, nilai_uas
  const sumatif = [
    nilai.nilai_sumatif_4,
    nilai.nilai_sumatif_5,
    nilai.nilai_uas
  ].filter(n => n !== null && n !== undefined)
  
  if (sumatif.length === 0) return null
  return (sumatif.reduce((a, b) => a + b, 0) / sumatif.length).toFixed(2)
}

const getNilaiP5 = (siswaId, p5Id, dimensiId) => {
  const p5Nilai = p5NilaiData.value[p5Id]?.[siswaId] || []
  const nilai = p5Nilai.find(n => n.dimensi_id === dimensiId)
  return nilai?.nilai || null
}

const getRataRataSTS = (siswaId) => {
  const nilai = mapelList.value.map(mapel => parseFloat(getNilaiSTS(siswaId, mapel.id)) || 0)
  const validNilai = nilai.filter(n => n > 0)
  if (validNilai.length === 0) return '-'
  return (validNilai.reduce((a, b) => a + b, 0) / validNilai.length).toFixed(2)
}

const getRataRataSAS = (siswaId) => {
  const nilai = mapelList.value.map(mapel => parseFloat(getNilaiSAS(siswaId, mapel.id)) || 0)
  const validNilai = nilai.filter(n => n > 0)
  if (validNilai.length === 0) return '-'
  return (validNilai.reduce((a, b) => a + b, 0) / validNilai.length).toFixed(2)
}

const getNilaiColor = (nilai) => {
  if (!nilai) return 'bg-gray-100 text-gray-800'
  const num = parseFloat(nilai)
  if (num >= 90) return 'bg-green-100 text-green-800'
  if (num >= 80) return 'bg-blue-100 text-blue-800'
  if (num >= 70) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

// Watch activeTab to reload P5 data when switching
watch(activeTab, (newTab) => {
  if (newTab === 'p5' && selectedKelas.value && selectedTahunAjaran.value) {
    loadP5Data()
  }
})

// Lifecycle
onMounted(() => {
  fetchKelas()
  fetchTahunAjaran()
  fetchMapel()
  fetchDimensi()
})
</script>

