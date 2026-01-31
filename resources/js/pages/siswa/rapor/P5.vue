<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Rapor P5
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Lihat rapor Projek Penguatan Profil Pelajar Pancasila Anda
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data rapor P5...</p>
      </div>

      <!-- P5 Projects -->
      <div v-else-if="raporDetail" class="space-y-6">
        <div
          v-for="(project, idx) in raporDetail.p5_projects"
          :key="project.id"
          class="bg-white shadow rounded-lg overflow-hidden"
        >
          <div class="p-6 space-y-4">
            <!-- Project header -->
            <div class="border-b border-gray-200 pb-4">
              <p v-if="project.tahun_ajaran" class="text-sm text-gray-600 mb-2">
                Tahun Ajaran: {{ formatTahunAjaran(project.tahun_ajaran) }}
              </p>
              <h4 class="text-lg font-medium text-gray-900">{{ project.judul || project.tema }}</h4>
              <p class="text-sm text-gray-500 mt-1">
                Tema: {{ project.tema || project.dimensi || '-' }}
              </p>
              <p v-if="project.deskripsi" class="text-sm text-gray-600 mt-2">{{ project.deskripsi }}</p>
              <p v-if="project.fasilitator_nama" class="text-xs text-gray-500 mt-2">
                Fasilitator: {{ project.fasilitator_nama }}
              </p>
            </div>

            <!-- Capaian predikat legend -->
            <div class="bg-gray-50 p-4 rounded-lg text-xs">
              <div class="font-medium text-gray-700 mb-2">Capaian Siswa:</div>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div><span class="font-semibold">MB</span> = Mulai Berkembang</div>
                <div><span class="font-semibold">SB</span> = Sedang Berkembang</div>
                <div><span class="font-semibold">BSH</span> = Berkembang Sesuai Harapan</div>
                <div><span class="font-semibold">SAB</span> = Sangat Berkembang</div>
              </div>
            </div>

            <!-- Tujuan Pembelajaran table -->
            <div v-if="project.elemen_sub && project.elemen_sub.length > 0">
              <h5 class="font-medium text-gray-900 mb-2">Tujuan Pembelajaran</h5>
              <div class="overflow-x-auto">
                <table class="table">
                  <thead>
                    <tr>
                      <th class="w-12">No</th>
                      <th>Tujuan Pembelajaran</th>
                      <th class="text-center w-20">Capaian</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(es, i) in project.elemen_sub" :key="i">
                      <td class="text-center">{{ i + 1 }}</td>
                      <td>
                        <div class="font-medium text-gray-900">{{ es.sub_elemen }}</div>
                        <div v-if="es.deskripsi_tujuan" class="text-sm text-gray-500 mt-0.5">
                          {{ es.deskripsi_tujuan }}
                        </div>
                      </td>
                      <td class="text-center">
                        <span
                          v-if="es.predikat_label && es.predikat_label !== '-'"
                          :class="getPredikatClass(es.predikat)"
                          class="inline-flex px-2 py-0.5 rounded text-xs font-medium"
                        >
                          {{ es.predikat_label }}
                        </span>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div v-else class="text-sm text-gray-500 py-4">
              Belum ada nilai tujuan pembelajaran untuk projek ini.
            </div>

            <!-- Catatan Proses -->
            <div v-if="project.catatan_proses" class="pt-4 border-t border-gray-200">
              <h5 class="font-medium text-gray-900 mb-2">Catatan Proses</h5>
              <div class="bg-gray-50 p-4 rounded-lg text-sm text-gray-700">
                {{ project.catatan_proses }}
              </div>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div
          v-if="!raporDetail.p5_projects || raporDetail.p5_projects.length === 0"
          class="bg-white shadow rounded-lg p-8 text-center"
        >
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada data rapor P5</h3>
          <p class="mt-1 text-sm text-gray-500">
            Belum ada projek P5 atau nilai P5 untuk periode ini.
          </p>
        </div>
      </div>

      <!-- Empty state when no data -->
      <div v-else class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada data rapor P5</h3>
        <p class="mt-1 text-sm text-gray-500">
          Belum ada projek P5 atau nilai P5.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'

const toast = useToast()

const loading = ref(false)
const raporDetail = ref(null)

function formatTahunAjaran(ta) {
  if (!ta) return '-'
  const t = Number(ta.tahun) || 0
  const sem = ta.semester ?? ''
  if (sem) return `${t}/${t + 1} - Semester ${sem}`
  return `${t}/${t + 1}`
}

async function loadDetail() {
  loading.value = true
  raporDetail.value = null
  try {
    const res = await axios.get('/siswa/rapor/p5/detail')
    raporDetail.value = res.data
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal memuat data rapor P5')
    raporDetail.value = null
  } finally {
    loading.value = false
  }
}

function getPredikatClass(predikat) {
  const map = {
    MB: 'bg-gray-100 text-gray-800',
    SB: 'bg-yellow-100 text-yellow-800',
    BSH: 'bg-blue-100 text-blue-800',
    SAB: 'bg-green-100 text-green-800'
  }
  return map[predikat] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
  loadDetail()
})
</script>
