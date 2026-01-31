<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Cetak Transkrip Hasil Belajar
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Cetak transkrip hasil belajar siswa (No, Mata Pelajaran, Nilai Akhir) per periode
        </p>
      </div>

      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <FormField
            v-model="filters.kelas_id"
            type="select"
            label="Kelas"
            placeholder="Pilih Kelas"
            :options="kelasOptions"
            option-value="id"
            option-label="full_name"
            @update:model-value="fetchSiswa"
          />
          <FormField
            v-model="filters.tahun_ajaran_id"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="full_description"
            @update:model-value="fetchSiswa"
          />
          <FormField
            v-model="filters.semester"
            type="select"
            label="Semester"
            placeholder="Pilih Semester"
            :options="semesterOptions"
            option-value="value"
            option-label="label"
            @update:model-value="fetchSiswa"
          />
          <FormField
            v-model="filters.jenis"
            type="select"
            label="Periode"
            placeholder="Pilih Periode"
            :options="jenisOptions"
            option-value="value"
            option-label="label"
            @update:model-value="fetchSiswa"
          />
          <FormField
            v-model="filters.titimangsa"
            type="date"
            label="Titimangsa Rapor"
            :required="true"
          />
        </div>
        <div class="mt-4">
          <FormField
            v-model="filters.search"
            type="text"
            label="Cari Siswa"
            placeholder="Nama atau NIS"
            @update:model-value="handleSearch"
          />
        </div>
      </div>

      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data siswa...</p>
      </div>

      <div v-else class="bg-white shadow rounded-lg overflow-hidden">
        <table class="table">
          <thead>
            <tr>
              <th>No</th>
              <th>Siswa</th>
              <th>Kelas</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in (siswaList?.data ?? [])" :key="item.id" class="hover:bg-gray-50">
              <td class="text-center">{{ (siswaList?.current_page - 1) * (siswaList?.per_page ?? 15) + index + 1 }}</td>
              <td>
                <div class="font-medium text-gray-900">{{ item.nama_lengkap }}</div>
                <div class="text-sm text-gray-500">NIS: {{ item.nis }} / NISN: {{ item.nisn }}</div>
              </td>
              <td>
                <span class="text-sm text-gray-900">{{ item.kelas?.nama_kelas }}</span>
                <span v-if="item.kelas?.jurusan" class="text-sm text-gray-500"> ({{ item.kelas.jurusan.nama_jurusan }})</span>
              </td>
              <td>
                <button
                  type="button"
                  :disabled="downloadingId === item.id"
                  @click="cetakTranskrip(item)"
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                >
                  <svg v-if="downloadingId === item.id" class="animate-spin -ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  {{ downloadingId === item.id ? 'Mengunduh...' : 'Cetak Transkrip' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="siswaList && siswaList.last_page > 1" class="px-4 py-3 border-t border-gray-200 flex justify-between items-center">
          <span class="text-sm text-gray-700">
            Menampilkan {{ siswaList.from }}–{{ siswaList.to }} dari {{ siswaList.total }}
          </span>
          <div class="flex space-x-2">
            <button
              @click="fetchSiswa(siswaList.current_page - 1)"
              :disabled="!siswaList.prev_page_url"
              class="btn btn-sm btn-secondary"
            >
              Sebelumnya
            </button>
            <button
              @click="fetchSiswa(siswaList.current_page + 1)"
              :disabled="!siswaList.next_page_url"
              class="btn btn-sm btn-secondary"
            >
              Selanjutnya
            </button>
          </div>
        </div>

        <div v-if="!loading && (!siswaList?.data?.length)" class="p-8 text-center text-gray-500">
          <p>Pilih filter (minimal Kelas dan Tahun Ajaran) untuk menampilkan daftar siswa.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import FormField from '../../../../components/ui/FormField.vue'

const toast = useToast()
const siswaList = ref(null)
const loading = ref(false)
const downloadingId = ref(null)
const kelasOptions = ref([])
const tahunAjaranOptions = ref([])

const filters = reactive({
  kelas_id: '',
  tahun_ajaran_id: '',
  semester: '1',
  jenis: 'sas',
  search: '',
  titimangsa: new Date().toISOString().split('T')[0]
})

const semesterOptions = [
  { value: '1', label: 'Semester 1' },
  { value: '2', label: 'Semester 2' }
]
const jenisOptions = [
  { value: 'sts', label: 'Tengah Semester (STS)' },
  { value: 'sas', label: 'Akhir Semester (SAS)' }
]

async function fetchKelas () {
  try {
    const r = await axios.get('/lookup/kelas')
    const raw = r.data ?? []
    kelasOptions.value = raw.map(k => ({
      ...k,
      full_name: k.nama_kelas + (k.jurusan ? ` (${k.jurusan.nama_jurusan})` : '')
    }))
  } catch (_) {
    kelasOptions.value = []
  }
}

async function fetchTahunAjaran () {
  try {
    const r = await axios.get('/lookup/tahun-ajaran')
    tahunAjaranOptions.value = (r.data ?? []).map(ta => ({
      ...ta,
      full_description: `${ta.tahun ?? ''}/${(ta.tahun ?? 0) + 1} - Semester ${ta.semester ?? ''}`
    }))
    const active = tahunAjaranOptions.value.find(t => t.is_active)
    if (active && !filters.tahun_ajaran_id) filters.tahun_ajaran_id = active.id
  } catch (_) {
    tahunAjaranOptions.value = []
  }
}

async function fetchSiswa (page = 1) {
  if (!filters.kelas_id && !filters.tahun_ajaran_id) {
    siswaList.value = null
    return
  }
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.kelas_id) params.append('kelas_id', filters.kelas_id)
    if (filters.tahun_ajaran_id) params.append('tahun_ajaran_id', filters.tahun_ajaran_id)
    if (filters.search) params.append('search', filters.search)
    params.append('page', page)
    params.append('per_page', 15)
    const r = await axios.get(`/admin/cetak-rapor/transkrip?${params}`)
    siswaList.value = r.data
  } catch (e) {
    toast.error('Gagal mengambil daftar siswa')
    siswaList.value = null
  } finally {
    loading.value = false
  }
}

function handleSearch () {
  fetchSiswa(1)
}

async function cetakTranskrip (siswa) {
  if (!filters.titimangsa) {
    toast.error('Harap pilih titimangsa rapor terlebih dahulu')
    return
  }
  downloadingId.value = siswa.id
  try {
    const params = new URLSearchParams()
    if (filters.tahun_ajaran_id) params.append('tahun_ajaran_id', filters.tahun_ajaran_id)
    params.append('semester', filters.semester)
    params.append('jenis', filters.jenis)
    params.append('titimangsa', filters.titimangsa)
    const res = await axios.get(
      `/admin/cetak-rapor/transkrip/${siswa.id}/download?${params}`,
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
    downloadingId.value = null
  }
}

onMounted(() => {
  fetchKelas()
  fetchTahunAjaran()
})
</script>
