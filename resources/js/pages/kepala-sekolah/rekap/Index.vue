<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Rekap & Laporan
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Rekap data akademik dan statistik sekolah
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
            v-model="selectedTahunAjaran"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="tahun"
            @update:model-value="loadData"
          />
          <FormField
            v-if="activeTab === 'nilai' || activeTab === 'kehadiran'"
            v-model="selectedKelas"
            type="select"
            label="Kelas (Opsional)"
            placeholder="Pilih Kelas"
            :options="kelasOptions"
            option-value="id"
            option-label="nama_kelas"
            @update:model-value="loadData"
          />
          <FormField
            v-if="activeTab === 'nilai'"
            v-model="selectedJurusan"
            type="select"
            label="Jurusan (Opsional)"
            placeholder="Pilih Jurusan"
            :options="jurusanOptions"
            option-value="id"
            option-label="nama_jurusan"
            @update:model-value="loadData"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
      </div>

      <!-- Statistik Tab -->
      <div v-else-if="activeTab === 'statistik'" class="bg-white shadow rounded-lg p-6">
        <div v-if="statistikData" class="space-y-6">
          <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-blue-50 p-5 rounded-lg">
              <div class="text-sm text-blue-600 font-medium">Total Kelas</div>
              <div class="text-3xl font-bold text-blue-900 mt-2">{{ statistikData.total_kelas || 0 }}</div>
            </div>
            <div class="bg-green-50 p-5 rounded-lg">
              <div class="text-sm text-green-600 font-medium">Total Siswa</div>
              <div class="text-3xl font-bold text-green-900 mt-2">{{ statistikData.total_siswa || 0 }}</div>
            </div>
            <div class="bg-yellow-50 p-5 rounded-lg">
              <div class="text-sm text-yellow-600 font-medium">Total Guru</div>
              <div class="text-3xl font-bold text-yellow-900 mt-2">{{ statistikData.total_guru || 0 }}</div>
            </div>
            <div class="bg-purple-50 p-5 rounded-lg">
              <div class="text-sm text-purple-600 font-medium">Total Mapel</div>
              <div class="text-3xl font-bold text-purple-900 mt-2">{{ statistikData.total_mapel || 0 }}</div>
            </div>
          </div>
          <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="bg-indigo-50 p-5 rounded-lg">
              <div class="text-sm text-indigo-600 font-medium">Rata-rata Nilai</div>
              <div class="text-3xl font-bold text-indigo-900 mt-2">{{ statistikData.rata_rata_nilai || 0 }}</div>
            </div>
            <div class="bg-pink-50 p-5 rounded-lg">
              <div class="text-sm text-pink-600 font-medium">Rapor Disetujui</div>
              <div class="text-3xl font-bold text-pink-900 mt-2">{{ statistikData.total_rapor_approved || 0 }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Nilai Tab -->
      <div v-else-if="activeTab === 'nilai'" class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Rekap Nilai</h3>
            <div class="text-sm text-gray-500">
              Total Siswa: {{ rekapNilaiData?.statistik?.total_siswa || 0 }} | 
              Total Mapel: {{ rekapNilaiData?.statistik?.total_mapel || 0 }}
            </div>
          </div>
        </div>
        <div class="p-6">
          <div v-if="rekapNilaiData?.statistik" class="mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
              <div class="text-sm text-blue-600 font-medium">Rata-rata Umum</div>
              <div class="text-2xl font-bold text-blue-900">{{ rekapNilaiData.statistik.rata_rata_umum || 0 }}</div>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Siswa</th>
                  <th>Kelas</th>
                  <th>Mata Pelajaran</th>
                  <th class="text-center">Nilai Rapor</th>
                  <th class="text-center">Predikat</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(nilai, index) in rekapNilaiData?.data?.data || []" :key="nilai.id">
                  <td class="text-center">{{ index + 1 }}</td>
                  <td>{{ nilai.siswa?.nama_lengkap }}</td>
                  <td>{{ nilai.siswa?.kelas?.nama_kelas }}</td>
                  <td>{{ nilai.mata_pelajaran?.nama_mapel }}</td>
                  <td class="text-center">{{ nilai.nilai_rapor }}</td>
                  <td class="text-center">{{ nilai.predikat }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Kehadiran Tab -->
      <div v-else-if="activeTab === 'kehadiran'" class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <h3 class="text-lg font-medium text-gray-900">Rekap Kehadiran</h3>
        </div>
        <div class="p-6">
          <div v-if="rekapKehadiranData?.statistik" class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="bg-red-50 p-4 rounded-lg">
              <div class="text-sm text-red-600 font-medium">Total Sakit</div>
              <div class="text-2xl font-bold text-red-900">{{ rekapKehadiranData.statistik.total_sakit || 0 }}</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
              <div class="text-sm text-yellow-600 font-medium">Total Izin</div>
              <div class="text-2xl font-bold text-yellow-900">{{ rekapKehadiranData.statistik.total_izin || 0 }}</div>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
              <div class="text-sm text-orange-600 font-medium">Total Tanpa Keterangan</div>
              <div class="text-2xl font-bold text-orange-900">{{ rekapKehadiranData.statistik.total_tanpa_keterangan || 0 }}</div>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg">
              <div class="text-sm text-blue-600 font-medium">Total Siswa</div>
              <div class="text-2xl font-bold text-blue-900">{{ rekapKehadiranData.statistik.total_siswa || 0 }}</div>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Siswa</th>
                  <th>Kelas</th>
                  <th class="text-center">Sakit</th>
                  <th class="text-center">Izin</th>
                  <th class="text-center">Tanpa Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(kehadiran, index) in rekapKehadiranData?.data?.data || []" :key="kehadiran.id">
                  <td class="text-center">{{ index + 1 }}</td>
                  <td>{{ kehadiran.siswa?.nama_lengkap }}</td>
                  <td>{{ kehadiran.siswa?.kelas?.nama_kelas }}</td>
                  <td class="text-center">{{ kehadiran.sakit || 0 }}</td>
                  <td class="text-center">{{ kehadiran.izin || 0 }}</td>
                  <td class="text-center">{{ kehadiran.tanpa_keterangan || 0 }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Prestasi Tab -->
      <div v-else-if="activeTab === 'prestasi'" class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <h3 class="text-lg font-medium text-gray-900">Top 10 Siswa Berprestasi</h3>
        </div>
        <div class="p-6">
          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th class="w-16">Rank</th>
                  <th>Nama Siswa</th>
                  <th>NIS</th>
                  <th>Kelas</th>
                  <th class="text-center">Rata-rata</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(siswa, index) in prestasiData?.top_siswa || []" :key="siswa.siswa_id">
                  <td class="text-center">
                    <span v-if="index < 3" class="text-2xl">
                      {{ index === 0 ? '🥇' : index === 1 ? '🥈' : '🥉' }}
                    </span>
                    <span v-else class="font-medium">{{ index + 1 }}</span>
                  </td>
                  <td>{{ siswa.siswa?.nama_lengkap }}</td>
                  <td>{{ siswa.siswa?.nis }}</td>
                  <td>{{ siswa.siswa?.kelas?.nama_kelas }}</td>
                  <td class="text-center font-medium">{{ siswa.rata_rata }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Legger Tab -->
      <div v-else-if="activeTab === 'legger'" class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Legger Nilai</h3>
            <FormField
              v-model="selectedKelasLegger"
              type="select"
              placeholder="Pilih Kelas"
              :options="kelasOptions"
              option-value="id"
              option-label="nama_kelas"
              @update:model-value="loadLegger"
              class="w-64"
            />
          </div>
        </div>
        <div v-if="leggerData" class="p-6">
          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Siswa</th>
                  <th>NIS</th>
                  <th v-for="mapel in mapelList" :key="mapel.id" class="text-center min-w-[100px]">
                    {{ mapel.nama_mapel }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in leggerData.legger" :key="item.siswa.id">
                  <td class="text-center">{{ index + 1 }}</td>
                  <td>{{ item.siswa.nama_lengkap }}</td>
                  <td>{{ item.siswa.nis }}</td>
                  <td v-for="mapel in mapelList" :key="mapel.id" class="text-center">
                    <span v-if="getNilaiLegger(item.nilai, mapel.id)" 
                          :class="getNilaiColor(getNilaiLegger(item.nilai, mapel.id))"
                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                      {{ getNilaiLegger(item.nilai, mapel.id) }}
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
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'

const toast = useToast()

// Data
const tahunAjaranOptions = ref([])
const kelasOptions = ref([])
const jurusanOptions = ref([])
const mapelList = ref([])
const statistikData = ref(null)
const rekapNilaiData = ref(null)
const rekapKehadiranData = ref(null)
const prestasiData = ref(null)
const leggerData = ref(null)

// State
const loading = ref(false)
const activeTab = ref('statistik')
const selectedTahunAjaran = ref('')
const selectedKelas = ref('')
const selectedJurusan = ref('')
const selectedKelasLegger = ref('')

// Tabs
const tabs = [
  { id: 'statistik', label: 'Statistik' },
  { id: 'nilai', label: 'Rekap Nilai' },
  { id: 'kehadiran', label: 'Rekap Kehadiran' },
  { id: 'prestasi', label: 'Prestasi' },
  { id: 'legger', label: 'Legger' }
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

const fetchKelas = async () => {
  try {
    const response = await axios.get('/lookup/kelas')
    kelasOptions.value = response.data
  } catch (error) {
    console.error('Failed to fetch kelas:', error)
  }
}

const fetchJurusan = async () => {
  try {
    const response = await axios.get('/lookup/jurusan')
    jurusanOptions.value = response.data
  } catch (error) {
    console.error('Failed to fetch jurusan:', error)
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

const loadData = async () => {
  if (!selectedTahunAjaran.value) return

  try {
    loading.value = true
    
    switch (activeTab.value) {
      case 'statistik':
        await loadStatistik()
        break
      case 'nilai':
        await loadRekapNilai()
        break
      case 'kehadiran':
        await loadRekapKehadiran()
        break
      case 'prestasi':
        await loadPrestasi()
        break
    }
  } catch (error) {
    toast.error('Gagal mengambil data')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const loadStatistik = async () => {
  try {
    const response = await axios.get('/kepala-sekolah/rekap/statistik', {
      params: { tahun_ajaran_id: selectedTahunAjaran.value }
    })
    statistikData.value = response.data.statistik
  } catch (error) {
    console.error('Failed to load statistik:', error)
  }
}

const loadRekapNilai = async () => {
  try {
    const params = { tahun_ajaran_id: selectedTahunAjaran.value }
    if (selectedKelas.value) params.kelas_id = selectedKelas.value
    if (selectedJurusan.value) params.jurusan_id = selectedJurusan.value
    
    const response = await axios.get('/kepala-sekolah/rekap/nilai', { params })
    rekapNilaiData.value = response.data
  } catch (error) {
    console.error('Failed to load rekap nilai:', error)
  }
}

const loadRekapKehadiran = async () => {
  try {
    const params = { tahun_ajaran_id: selectedTahunAjaran.value }
    if (selectedKelas.value) params.kelas_id = selectedKelas.value
    
    const response = await axios.get('/kepala-sekolah/rekap/kehadiran', { params })
    rekapKehadiranData.value = response.data
  } catch (error) {
    console.error('Failed to load rekap kehadiran:', error)
  }
}

const loadPrestasi = async () => {
  try {
    const response = await axios.get('/kepala-sekolah/rekap/prestasi', {
      params: { tahun_ajaran_id: selectedTahunAjaran.value }
    })
    prestasiData.value = response.data
  } catch (error) {
    console.error('Failed to load prestasi:', error)
  }
}

const loadLegger = async () => {
  if (!selectedKelasLegger.value || !selectedTahunAjaran.value) {
    leggerData.value = null
    return
  }

  try {
    loading.value = true
    const response = await axios.get(`/kepala-sekolah/legger/kelas/${selectedKelasLegger.value}`, {
      params: { tahun_ajaran_id: selectedTahunAjaran.value }
    })
    leggerData.value = response.data
  } catch (error) {
    toast.error('Gagal mengambil data legger')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const getNilaiLegger = (nilaiArray, mapelId) => {
  const nilai = nilaiArray?.find(n => n.mata_pelajaran_id === mapelId)
  return nilai?.nilai_rapor ? nilai.nilai_rapor.toFixed(2) : null
}

const getNilaiColor = (nilai) => {
  if (!nilai) return 'bg-gray-100 text-gray-800'
  const num = parseFloat(nilai)
  if (num >= 90) return 'bg-green-100 text-green-800'
  if (num >= 80) return 'bg-blue-100 text-blue-800'
  if (num >= 70) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

watch(activeTab, () => {
  if (selectedTahunAjaran.value) {
    loadData()
  }
})

// Lifecycle
onMounted(() => {
  fetchTahunAjaran()
  fetchKelas()
  fetchJurusan()
  fetchMapel()
  loadStatistik()
})
</script>
