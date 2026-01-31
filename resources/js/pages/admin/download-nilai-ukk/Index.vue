<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Download Nilai UKK
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Pilih jurusan untuk menampilkan daftar kelas yang memiliki nilai UKK, lalu unduh dalam format .xlsx
        </p>
      </div>

      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FormField
            v-model="filters.jurusan_id"
            type="select"
            label="Jurusan"
            placeholder="Pilih Jurusan"
            :options="jurusanOptions"
            option-value="id"
            option-label="nama_jurusan"
            @update:model-value="fetchKelasList"
          />
        </div>
      </div>

      <div v-if="!filters.jurusan_id" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih jurusan terlebih dahulu</h3>
        <p class="mt-1 text-sm text-gray-500">
          Pilih jurusan untuk menampilkan daftar kelas yang memiliki data nilai UKK.
        </p>
      </div>

      <div v-else-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat daftar kelas...</p>
      </div>

      <div v-else class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <h3 class="text-lg font-medium text-gray-900">
            Daftar Kelas (Nilai UKK per Kelas & Tahun Ajaran)
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            Klik tombol Download untuk mengunduh daftar nilai UKK dalam format .xlsx
          </p>
        </div>
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Kelas</th>
                <th>Jurusan</th>
                <th>Tahun Pelajaran</th>
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in kelasList" :key="`${row.kelas_id}-${row.tahun_ajaran_id}`" class="hover:bg-gray-50">
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <span class="font-medium text-gray-900">{{ row.kelas?.nama_kelas || '-' }}</span>
                </td>
                <td class="text-gray-600">{{ row.kelas?.jurusan?.nama_jurusan || '-' }}</td>
                <td class="text-gray-600">{{ row.tahun_ajaran?.label || '-' }}</td>
                <td class="text-center">
                  <button
                    type="button"
                    :disabled="downloadingId === `${row.kelas_id}-${row.tahun_ajaran_id}`"
                    @click="downloadExcel(row)"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <svg
                      v-if="downloadingId === `${row.kelas_id}-${row.tahun_ajaran_id}`"
                      class="animate-spin -ml-0.5 mr-2 h-4 w-4"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg
                      v-else
                      class="w-4 h-4 mr-2"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ downloadingId === `${row.kelas_id}-${row.tahun_ajaran_id}` ? 'Mengunduh...' : 'Download .xlsx' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="kelasList.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada data nilai UKK</h3>
          <p class="mt-1 text-sm text-gray-500">
            Tidak ada nilai UKK untuk jurusan ini. Input nilai UKK terlebih dahulu (menu Kelola UKK / Nilai UKK).
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import FormField from '../../../components/ui/FormField.vue'

const toast = useToast()
const jurusanOptions = ref([])
const kelasList = ref([])
const loading = ref(false)
const downloadingId = ref(null)

const filters = reactive({
  jurusan_id: ''
})

async function fetchJurusan () {
  try {
    const r = await axios.get('/lookup/jurusan')
    jurusanOptions.value = r.data ?? []
  } catch (_) {
    jurusanOptions.value = []
  }
}

async function fetchKelasList () {
  if (!filters.jurusan_id) {
    kelasList.value = []
    return
  }
  loading.value = true
  try {
    const r = await axios.get('/admin/ukk/kelas-list', {
      params: { jurusan_id: filters.jurusan_id }
    })
    kelasList.value = r.data?.data ?? []
  } catch (e) {
    toast.error('Gagal mengambil daftar kelas')
    kelasList.value = []
  } finally {
    loading.value = false
  }
}

async function downloadExcel (row) {
  const id = `${row.kelas_id}-${row.tahun_ajaran_id}`
  downloadingId.value = id
  try {
    const params = new URLSearchParams({
      jurusan_id: filters.jurusan_id,
      kelas_id: row.kelas_id,
      tahun_ajaran_id: row.tahun_ajaran_id
    })
    const res = await axios.get(`/admin/ukk/export?${params}`, {
      responseType: 'blob'
    })
    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const contentDisposition = res.headers['content-disposition']
    let fileName = 'Daftar_Nilai_UKK.xlsx'
    if (contentDisposition) {
      const match = contentDisposition.match(/filename="?([^";]+)"?/)
      if (match) fileName = match[1]
    }
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = fileName
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    a.remove()
    toast.success('File berhasil diunduh')
  } catch (e) {
    const msg = e.response?.data?.message ?? 'Gagal mengunduh file'
    if (e.response?.data instanceof Blob) {
      try {
        const text = await e.response.data.text()
        const j = JSON.parse(text)
        if (j?.message) toast.error(j.message)
        else toast.error(msg)
      } catch (_) {
        toast.error(msg)
      }
    } else {
      toast.error(typeof msg === 'string' ? msg : 'Gagal mengunduh file')
    }
  } finally {
    downloadingId.value = null
  }
}

onMounted(() => {
  fetchJurusan()
})
</script>
