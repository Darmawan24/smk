<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Nilai P5
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Cek penilaian P5 (Projek Penguatan Profil Pelajar Pancasila) siswa berdasarkan projek
        </p>
      </div>

      <!-- Filter Kelas & Projek -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Filter Kelas
            </label>
            <select
              v-model="selectedKelasId"
              @change="onKelasChange"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Semua Kelas</option>
              <option
                v-for="k in kelas"
                :key="k.id"
                :value="k.id"
              >
                {{ k.nama_kelas }} {{ k.jurusan?.nama_jurusan ? `- ${k.jurusan.nama_jurusan}` : '' }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Filter Projek P5
            </label>
            <select
              v-model="selectedProjekId"
              @change="onProjekChange"
              :disabled="!kelasLoaded"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
            >
              <option value="">Pilih Projek P5</option>
              <option
                v-for="p in projek"
                :key="p.id"
                :value="p.id"
              >
                {{ p.judul }} {{ p.tema ? `(${p.tema})` : '' }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data...</p>
      </div>

      <!-- Empty: no filter -->
      <div v-else-if="!selectedProjekId" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih Projek P5</h3>
        <p class="mt-1 text-sm text-gray-500">
          Pilih projek terlebih dahulu untuk melihat daftar siswa dan progress nilai
        </p>
      </div>

      <!-- Empty: no siswa -->
      <div v-else-if="selectedProjekId && siswaList.length === 0" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada siswa</h3>
        <p class="mt-1 text-sm text-gray-500">
          Tidak ada siswa dari kelas yang Anda walikan pada projek ini
        </p>
      </div>

      <!-- Table: Siswa + Fasilitator + Progress + Detail -->
      <div v-else class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <h3 class="text-lg font-medium text-gray-900">
            {{ p5Info?.judul || 'Projek P5' }}
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            Daftar siswa, fasilitator, dan progress input nilai per sub elemen
          </p>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">No</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Siswa</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">NIS</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fasilitator</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Progress</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Aksi</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(row, index) in siswaList" :key="row.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-center text-gray-500">{{ index + 1 }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ row.nama_lengkap }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ row.nis || '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">
                  {{ row.fasilitator?.nama_lengkap || '-' }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      row.progress?.lengkap ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'
                    ]"
                  >
                    {{ row.progress?.sudah ?? 0 }}/{{ row.progress?.total ?? 0 }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <button
                    type="button"
                    @click="openDetail(row)"
                    class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                  >
                    Detail
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Detail: nilai per sub elemen -->
      <Modal v-model:show="showDetailModal" :title="`Detail Nilai P5 - ${selectedSiswa?.nama_lengkap || ''}`" size="lg">
        <div v-if="detailLoading" class="py-8 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        </div>
        <div v-else class="space-y-4">
          <div v-if="selectedSiswa" class="p-3 bg-gray-50 rounded-lg">
            <p class="text-sm font-medium text-gray-700">{{ selectedSiswa.nama_lengkap }}</p>
            <p class="text-xs text-gray-500">NIS: {{ selectedSiswa.nis }} · Fasilitator: {{ selectedSiswa.fasilitator?.nama_lengkap || '-' }}</p>
          </div>
          <div>
            <h4 class="text-sm font-medium text-gray-700 mb-2">Nilai per Sub Elemen</h4>
            <div class="border rounded-lg overflow-hidden">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sub Elemen</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-40">Nilai</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="es in (elemenSubDetail || [])" :key="es.sub_elemen || es.elemen">
                    <td class="px-4 py-2 text-sm text-gray-900">{{ es.sub_elemen || es.elemen || '-' }}</td>
                    <td class="px-4 py-2">
                      <span
                        v-if="getNilaiForSiswa(es)"
                        :class="[
                          'inline-flex px-2 py-0.5 rounded text-xs font-medium',
                          getNilaiBadgeClass(getNilaiForSiswa(es))
                        ]"
                      >
                        {{ getNilaiLabel(getNilaiForSiswa(es)) }}
                      </span>
                      <span v-else class="text-sm text-gray-400">-</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="!elemenSubDetail || elemenSubDetail.length === 0" class="text-sm text-gray-500 py-2">Tidak ada sub elemen pada projek ini.</p>
          </div>
        </div>
        <template #footer>
          <button type="button" @click="showDetailModal = false" class="btn btn-secondary">Tutup</button>
        </template>
      </Modal>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import Modal from '../../../../components/ui/Modal.vue'

const kelas = ref([])
const kelasLoaded = ref(false)
const selectedKelasId = ref('')
const projek = ref([])
const selectedProjekId = ref('')
const loading = ref(false)
const siswaList = ref([])
const p5Info = ref(null)
const elemenSubDetail = ref([])
const nilaiPerSiswa = ref({})
const showDetailModal = ref(false)
const detailLoading = ref(false)
const selectedSiswa = ref(null)

// Ambil daftar kelas dari endpoint yang sama seperti Cek STS/SAS agar pilihan kelas muncul
const fetchKelas = async () => {
  try {
    const response = await axios.get('/wali-kelas/cek-penilaian/sts')
    kelas.value = response.data.kelas || []
    kelasLoaded.value = true
  } catch (err) {
    console.error('Failed to fetch kelas:', err)
    kelas.value = []
    kelasLoaded.value = true
  }
}

const onKelasChange = async () => {
  selectedProjekId.value = ''
  projek.value = []
  siswaList.value = []
  p5Info.value = null
  await fetchProjek()
}

const onProjekChange = async () => {
  if (!selectedProjekId.value) {
    siswaList.value = []
    p5Info.value = null
    elemenSubDetail.value = []
    nilaiPerSiswa.value = {}
    return
  }
  try {
    loading.value = true
    const params = selectedKelasId.value ? { kelas_id: selectedKelasId.value } : {}
    const response = await axios.get(`/wali-kelas/cek-penilaian/p5/${selectedProjekId.value}`, { params })
    p5Info.value = response.data.p5 || null
    siswaList.value = response.data.siswa || []
    elemenSubDetail.value = response.data.elemen_sub || []
    nilaiPerSiswa.value = response.data.nilai_per_siswa || {}
  } catch (err) {
    console.error('Failed to fetch P5 detail:', err)
    siswaList.value = []
    p5Info.value = null
    elemenSubDetail.value = []
    nilaiPerSiswa.value = {}
  } finally {
    loading.value = false
  }
}

const openDetail = (row) => {
  selectedSiswa.value = row
  showDetailModal.value = true
}

const getNilaiForSiswa = (es) => {
  if (!selectedSiswa.value || !nilaiPerSiswa.value[selectedSiswa.value.id]) return null
  const sub = es.sub_elemen ?? es.elemen ?? ''
  return nilaiPerSiswa.value[selectedSiswa.value.id][sub] || null
}

const getNilaiLabel = (nilai) => {
  const map = { MB: 'Mulai Berkembang', SB: 'Sedang Berkembang', BSH: 'Berkembang Sesuai Harapan', SAB: 'Sangat Berkembang' }
  return map[nilai] || nilai
}

const getNilaiBadgeClass = (nilai) => {
  const map = {
    MB: 'bg-gray-100 text-gray-800',
    SB: 'bg-blue-100 text-blue-800',
    BSH: 'bg-green-100 text-green-800',
    SAB: 'bg-indigo-100 text-indigo-800'
  }
  return map[nilai] || 'bg-gray-100 text-gray-800'
}

const fetchProjek = async () => {
  try {
    loading.value = true
    const params = selectedKelasId.value ? { kelas_id: selectedKelasId.value } : {}
    const response = await axios.get('/wali-kelas/cek-penilaian/p5', { params })
    projek.value = response.data.projek || []
    // Fallback: jika kelas dari STS kosong, isi dari response P5 (sumber kelas sama: wali kelas)
    const fromP5 = response.data.kelas || []
    if (fromP5.length > 0 && kelas.value.length === 0) {
      kelas.value = fromP5
    }
  } catch (err) {
    console.error('Failed to fetch projek:', err)
    projek.value = []
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchKelas()
  await fetchProjek()
})
</script>
