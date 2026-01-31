<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Cetak Rapor Hasil Belajar
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Cetak rapor hasil belajar siswa yang sudah disetujui
        </p>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <FormField
            v-model="filters.kelas_id"
            type="select"
            label="Kelas"
            placeholder="Pilih Kelas"
            :options="kelasFilterOptions"
            option-value="id"
            option-label="full_name"
            @update:model-value="onFiltersChange"
          />
          <FormField
            v-model="filters.semester"
            type="select"
            label="Semester"
            placeholder="Pilih Semester"
            :options="semesterOptions"
            @update:model-value="onFiltersChange"
          />
          <FormField
            v-model="filters.jenis"
            type="select"
            label="Periode"
            placeholder="Pilih Periode"
            :options="jenisOptions"
            @update:model-value="onFiltersChange"
          />
          <FormField
            v-model="filters.titimangsa"
            type="date"
            label="Titimangsa Rapor"
            :required="true"
            @update:model-value="onFiltersChange"
          />
        </div>
        <div class="mt-4">
          <FormField
            v-model="filters.search"
            type="text"
            label="Cari Siswa"
            placeholder="Cari berdasarkan nama atau NIS"
            @update:model-value="onFiltersChange"
          />
        </div>
        <p class="mt-2 text-xs text-gray-500">
          Pilih periode STS (Tengah Semester) atau SAS (Akhir Semester). Cetak hanya tersedia jika nilai periode tersebut sudah diisi, semua nilai ≥ KKM, dan rapor sudah disetujui KS.
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data rapor...</p>
      </div>

      <!-- Empty Filters State -->
      <div v-else-if="!filtersReady" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih Semua Filter</h3>
        <p class="mt-1 text-sm text-gray-500">
          Pilih Kelas, Semester, Periode, dan Titimangsa untuk menampilkan daftar rapor.
        </p>
      </div>

      <!-- Rapor List -->
      <div v-else-if="filtersReady && (list?.length ?? 0) > 0" class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto max-h-[70vh] overflow-y-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky top-0 z-10">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  No
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Nama Siswa
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  NISN
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  NIS
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Cetak Rapor
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Cetak Transkrip
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(row, idx) in list" :key="row.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ idx + 1 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ row.nama_lengkap }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ row.nisn ?? '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ row.nis ?? '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <button
                    type="button"
                    :disabled="!row.can_cetak_rapor"
                    :class="[
                      'inline-flex items-center px-3 py-1.5 border text-sm font-medium rounded-md',
                      row.can_cetak_rapor
                        ? 'bg-blue-600 text-white border-transparent hover:bg-blue-700'
                        : 'bg-gray-200 text-gray-400 cursor-not-allowed border-gray-200'
                    ]"
                    @click="row.can_cetak_rapor && downloadRapor(row)"
                  >
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak
                  </button>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <button
                    type="button"
                    :disabled="downloadingTranskripId === row.id"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                    @click="cetakTranskrip(row)"
                  >
                    <svg v-if="downloadingTranskripId === row.id" class="animate-spin -ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty State (when filters filled but no data) -->
      <div v-else-if="filtersReady" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada siswa</h3>
        <p class="mt-1 text-sm text-gray-500">Tidak ada siswa dalam kelas ini.</p>
      </div>

      <!-- Preview Modal -->
      <Modal v-model:show="showPreviewModal" title="Preview Rapor Hasil Belajar" size="xl">
        <div v-if="selectedRapor" class="space-y-6">
          <!-- Header -->
          <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-medium text-gray-900">Rapor Hasil Belajar</h3>
            <p class="text-sm text-gray-500 mt-1">
              {{ selectedRapor.siswa?.nama_lengkap }} - {{ selectedRapor.kelas?.nama_kelas }}
            </p>
          </div>

          <!-- Nilai Akademik -->
          <div v-if="selectedRapor.nilai && Object.keys(selectedRapor.nilai).length > 0">
            <h4 class="font-medium text-gray-900 mb-3">Nilai Akademik</h4>
            <div v-for="(nilaiGroup, kelompok) in selectedRapor.nilai" :key="kelompok" class="mb-4">
              <h5 class="text-sm font-medium text-gray-700 mb-2">{{ kelompok }}</h5>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mata Pelajaran</th>
                      <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">STS</th>
                      <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">SAS</th>
                      <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Rata-rata</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="nilai in nilaiGroup" :key="nilai.id">
                      <td class="px-4 py-2 text-sm text-gray-900">{{ nilai.mata_pelajaran?.nama_mapel }}</td>
                      <td class="px-4 py-2 text-sm text-center text-gray-900">{{ nilai.nilai_sts || '-' }}</td>
                      <td class="px-4 py-2 text-sm text-center text-gray-900">{{ nilai.nilai_sas || '-' }}</td>
                      <td class="px-4 py-2 text-sm text-center font-medium text-gray-900">
                        {{ nilai.nilai_akhir || '-' }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Kehadiran -->
          <div v-if="selectedRapor.kehadiran">
            <h4 class="font-medium text-gray-900 mb-3">Kehadiran</h4>
            <div class="grid grid-cols-3 gap-4">
              <div class="bg-gray-50 p-3 rounded">
                <div class="text-sm text-gray-500">Hadir</div>
                <div class="text-lg font-medium text-gray-900">{{ selectedRapor.kehadiran.hadir || 0 }}</div>
              </div>
              <div class="bg-gray-50 p-3 rounded">
                <div class="text-sm text-gray-500">Izin</div>
                <div class="text-lg font-medium text-gray-900">{{ selectedRapor.kehadiran.izin || 0 }}</div>
              </div>
              <div class="bg-gray-50 p-3 rounded">
                <div class="text-sm text-gray-500">Sakit</div>
                <div class="text-lg font-medium text-gray-900">{{ selectedRapor.kehadiran.sakit || 0 }}</div>
              </div>
            </div>
          </div>

          <!-- Catatan Akademik -->
          <div v-if="selectedRapor.catatan_akademik">
            <h4 class="font-medium text-gray-900 mb-3">Catatan Akademik</h4>
            <div class="bg-gray-50 p-4 rounded">
              <p class="text-sm text-gray-700">{{ selectedRapor.catatan_akademik.catatan || '-' }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button @click="showPreviewModal = false" class="btn btn-secondary">
              Tutup
            </button>
            <button @click="downloadRapor(selectedRapor)" class="btn btn-primary">
              Download PDF
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import Modal from '../../../../components/ui/Modal.vue'
import FormField from '../../../../components/ui/FormField.vue'

const toast = useToast()

const list = ref([])
const loading = ref(false)
const showPreviewModal = ref(false)
const selectedRapor = ref(null)
const downloadingTranskripId = ref(null)

const filters = ref({
  kelas_id: '',
  semester: '',
  jenis: '',
  search: '',
  titimangsa: new Date().toISOString().split('T')[0]
})

const kelasOptions = ref([])

const semesterOptions = [
  { value: '1', label: 'Semester 1' },
  { value: '2', label: 'Semester 2' }
]

const jenisOptions = [
  { value: 'sts', label: 'STS (Tengah Semester)' },
  { value: 'sas', label: 'SAS (Akhir Semester)' }
]

const kelasFilterOptions = computed(() => [
  ...kelasOptions.value
])

const filtersReady = computed(() => {
  return !!filters.value.kelas_id && !!filters.value.semester && !!filters.value.jenis && !!filters.value.titimangsa
})

const getStatusBadge = (status) => {
  if (status === 'approved') {
    return 'bg-green-100 text-green-800'
  }
  return 'bg-red-100 text-red-800'
}

const getStatusLabel = (status) => {
  if (status === 'approved') {
    return 'Disetujui'
  }
  return 'Tidak Disetujui'
}

const getSemesterLabel = (rapor) => {
  if (rapor.semester) {
    return `Semester ${rapor.semester}`
  }
  return '-'
}

const getJenisLabel = (jenis) => {
  if (jenis === 'sts') return 'STS'
  if (jenis === 'sas') return 'SAS'
  return '-'
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID')
}

const fetchRapor = async () => {
  if (!filtersReady.value) return
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.append('kelas_id', filters.value.kelas_id)
    params.append('semester', filters.value.semester)
    params.append('jenis', filters.value.jenis)
    if (filters.value.search) params.append('search', filters.value.search)

    const response = await axios.get(`/admin/cetak-rapor/hasil-belajar?${params.toString()}`)
    list.value = response.data?.data ?? []
  } catch (error) {
    console.error('Error fetching rapor:', error)
    toast.error(error.response?.data?.message || 'Gagal mengambil data rapor')
    list.value = []
  } finally {
    loading.value = false
  }
}

const fetchKelas = async () => {
  try {
    const response = await axios.get('/lookup/kelas')
    kelasOptions.value = response.data
  } catch (error) {
    console.error('Error fetching kelas:', error)
  }
}


function onFiltersChange() {
  if (filtersReady.value) {
    fetchRapor(1)
  }
}

const previewRapor = async (rapor) => {
  try {
    const response = await axios.get(`/admin/cetak-rapor/hasil-belajar/${rapor.id}`)
    selectedRapor.value = response.data
    showPreviewModal.value = true
  } catch (error) {
    console.error('Error fetching rapor detail:', error)
    toast.error('Gagal memuat detail rapor')
  }
}

const cetakTranskrip = async (row) => {
  const siswaId = row?.id
  if (!siswaId) {
    toast.error('Data siswa tidak ditemukan')
    return
  }
  if (!filters.value.titimangsa) {
    toast.error('Harap pilih titimangsa rapor terlebih dahulu')
    return
  }
  downloadingTranskripId.value = siswaId
  try {
    const params = new URLSearchParams()
    const tahunAjaranId = row.tahun_ajaran?.id || row.rapor?.tahun_ajaran_id
    if (tahunAjaranId) params.append('tahun_ajaran_id', tahunAjaranId)
    params.append('semester', row.rapor?.semester || filters.value.semester || '1')
    params.append('jenis', filters.value.jenis || row.rapor?.jenis || 'sas')
    params.append('titimangsa', filters.value.titimangsa)
    const res = await axios.get(
      `/admin/cetak-rapor/transkrip/${siswaId}/download?${params}`,
      { responseType: 'blob' }
    )
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    window.open(url, '_blank')
    toast.success('Transkrip dibuka di tab baru')
  } catch (e) {
    const msg = e.response?.data?.message ?? 'Gagal mengunduh transkrip'
    if (e.response?.data instanceof Blob) {
      try {
        const text = await e.response.data.text()
        const j = JSON.parse(text)
        toast.error(j?.message ?? msg)
      } catch (_) {
        toast.error(msg)
      }
    } else {
      toast.error(typeof msg === 'string' ? msg : 'Gagal mengunduh transkrip')
    }
  } finally {
    downloadingTranskripId.value = null
  }
}

const downloadRapor = async (row) => {
  const rapor = row?.rapor
  if (!rapor?.id) {
    toast.error('Rapor tidak tersedia untuk dicetak')
    return
  }
  try {
    const params = new URLSearchParams()
    const semester = rapor.semester || filters.value.semester || '1'
    const jenis = filters.value.jenis || rapor.jenis || 'sas'
    params.append('semester', semester)
    params.append('jenis', jenis)
    if (filters.value.titimangsa) params.append('titimangsa', filters.value.titimangsa)
    const url = `/admin/cetak-rapor/hasil-belajar/${rapor.id}/download?${params.toString()}`
    const response = await axios.get(url, { responseType: 'blob' })
    
    const blobUrl = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = blobUrl
    link.setAttribute('download', `rapor-${row.nis}-${row.tahun_ajaran?.tahun}-s${semester}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(blobUrl)
    
    toast.success('Rapor berhasil diunduh')
  } catch (error) {
    console.error('Error downloading rapor:', error)
    let msg = 'Gagal mengunduh rapor'
    if (error.response?.status === 403) {
      msg = 'Rapor belum disetujui kepala sekolah'
    } else if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        if (json?.message) msg = json.message
      } catch (_) {}
    } else if (error.response?.data?.message) {
      msg = error.response.data.message
    }
    toast.error(msg)
  }
}

onMounted(() => {
  fetchKelas()
})
</script>

