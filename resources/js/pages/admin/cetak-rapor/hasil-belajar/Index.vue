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
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <FormField
            v-model="filters.kelas_id"
            type="select"
            label="Kelas"
            placeholder="Pilih Kelas"
            :options="kelasFilterOptions"
            option-value="id"
            option-label="full_name"
            @update:model-value="fetchRapor"
          />
          <FormField
            v-model="filters.tahun_ajaran_id"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranFilterOptions"
            option-value="id"
            option-label="label"
            @update:model-value="fetchRapor"
          />
          <FormField
            v-model="filters.status"
            type="select"
            label="Status"
            placeholder="Pilih Status"
            :options="statusOptions"
            @update:model-value="fetchRapor"
          />
        </div>
        <div class="mt-4">
          <FormField
            v-model="filters.search"
            type="text"
            label="Cari Siswa"
            placeholder="Cari berdasarkan nama atau NIS"
            @update:model-value="handleSearch"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data rapor...</p>
      </div>

      <!-- Rapor List -->
      <div v-else class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Siswa
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Kelas
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tahun Ajaran
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tanggal
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="rapor in (raporList?.data || [])" :key="rapor.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ rapor.siswa?.nama_lengkap }}</div>
                <div class="text-sm text-gray-500">NIS: {{ rapor.siswa?.nis }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ rapor.kelas?.nama_kelas }}</div>
                <div class="text-sm text-gray-500">{{ rapor.kelas?.jurusan?.nama_jurusan }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ rapor.tahun_ajaran ? `${rapor.tahun_ajaran.tahun} - Semester ${rapor.tahun_ajaran.semester}` : '-' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadge(rapor.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ getStatusLabel(rapor.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(rapor.tanggal_rapor) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <button
                    @click="previewRapor(rapor)"
                    class="text-blue-600 hover:text-blue-900"
                    title="Preview"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                  </button>
                  <button
                    @click="downloadRapor(rapor)"
                    class="text-green-600 hover:text-green-900"
                    title="Download PDF"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="raporList && raporList.last_page > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Menampilkan {{ raporList.from }} sampai {{ raporList.to }} dari {{ raporList.total }} hasil
            </div>
            <div class="flex space-x-2">
              <button
                @click="fetchRapor(raporList.current_page - 1)"
                :disabled="!raporList.prev_page_url"
                class="btn btn-sm btn-secondary"
              >
                Sebelumnya
              </button>
              <button
                @click="fetchRapor(raporList.current_page + 1)"
                :disabled="!raporList.next_page_url"
                class="btn btn-sm btn-secondary"
              >
                Selanjutnya
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && (!raporList || !raporList.data || (raporList.data && raporList.data.length === 0))" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada rapor</h3>
        <p class="mt-1 text-sm text-gray-500">Tidak ada rapor yang dapat dicetak untuk filter yang dipilih.</p>
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
            <p class="text-sm text-gray-500">
              Tahun Ajaran {{ selectedRapor.tahun_ajaran?.tahun }} - Semester {{ selectedRapor.tahun_ajaran?.semester }}
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

const raporList = ref([])
const loading = ref(false)
const showPreviewModal = ref(false)
const selectedRapor = ref(null)

const filters = ref({
  kelas_id: '',
  tahun_ajaran_id: '',
  status: '',
  search: ''
})

const kelasOptions = ref([])
const tahunAjaranOptions = ref([])

const statusOptions = [
  { value: '', label: 'Semua Status' },
  { value: 'approved', label: 'Disetujui' },
  { value: 'published', label: 'Dipublikasikan' }
]

const kelasFilterOptions = computed(() => [
  { id: '', full_name: 'Semua Kelas' },
  ...kelasOptions.value
])

const tahunAjaranFilterOptions = computed(() => [
  { id: '', label: 'Semua Tahun Ajaran' },
  ...tahunAjaranOptions.value.map(ta => ({
    id: ta.id,
    label: `${ta.tahun} - Semester ${ta.semester}`
  }))
])

const getStatusBadge = (status) => {
  const badges = {
    draft: 'bg-gray-100 text-gray-800',
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    published: 'bg-blue-100 text-blue-800',
    rejected: 'bg-red-100 text-red-800'
  }
  return badges[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    draft: 'Draft',
    pending: 'Menunggu Persetujuan',
    approved: 'Disetujui',
    published: 'Dipublikasikan',
    rejected: 'Ditolak'
  }
  return labels[status] || status
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID')
}

const fetchRapor = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.kelas_id) params.append('kelas_id', filters.value.kelas_id)
    if (filters.value.tahun_ajaran_id) params.append('tahun_ajaran_id', filters.value.tahun_ajaran_id)
    if (filters.value.status) params.append('status', filters.value.status)
    if (filters.value.search) params.append('search', filters.value.search)
    params.append('page', page)

    const response = await axios.get(`/admin/cetak-rapor/hasil-belajar?${params.toString()}`)
    // Handle paginated response
    if (response.data.data) {
      raporList.value = response.data
    } else if (Array.isArray(response.data)) {
      raporList.value = { data: response.data }
    } else {
      raporList.value = response.data || { data: [] }
    }
  } catch (error) {
    console.error('Error fetching rapor:', error)
    toast.error('Gagal mengambil data rapor')
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

const fetchTahunAjaran = async () => {
  try {
    const response = await axios.get('/lookup/tahun-ajaran')
    tahunAjaranOptions.value = response.data
  } catch (error) {
    console.error('Error fetching tahun ajaran:', error)
  }
}

const handleSearch = () => {
  fetchRapor(1)
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

const downloadRapor = async (rapor) => {
  try {
    const response = await axios.get(`/admin/cetak-rapor/hasil-belajar/${rapor.id}/download`, {
      responseType: 'blob'
    })
    
    // Create blob link to download
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `rapor-${rapor.siswa?.nis}-${rapor.tahun_ajaran?.tahun}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    toast.success('Rapor berhasil diunduh')
  } catch (error) {
    console.error('Error downloading rapor:', error)
    // If PDF generation is not implemented, show preview instead
    if (error.response?.status === 404 || error.response?.data?.message?.includes('PDF generation')) {
      toast.info('Fitur download PDF akan segera tersedia. Silakan gunakan preview.')
      previewRapor(rapor)
    } else {
      toast.error('Gagal mengunduh rapor')
    }
  }
}

onMounted(() => {
  fetchRapor()
  fetchKelas()
  fetchTahunAjaran()
})
</script>

