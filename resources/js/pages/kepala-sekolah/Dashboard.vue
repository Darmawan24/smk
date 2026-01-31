<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>

      <!-- Statistik Approve Rapor Belajar -->
      <div class="mt-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Statistik Rapor Belajar</h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Menunggu Persetujuan</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.pending || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Disetujui</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.approved || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Ditolak</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.rejected || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Rapor</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.total || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>
        <p class="mt-2 text-sm text-gray-500">
          Tahun ajaran aktif: {{ tahunAjaranAktif || '-' }}
        </p>
      </div>

      <!-- Ringkasan Lainnya -->
      <div class="mt-8 bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan</h2>
        <div v-if="overview" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
          <div>
            <div class="text-sm text-gray-500">Total Siswa</div>
            <div class="text-xl font-semibold text-gray-900">{{ overview.total_siswa || 0 }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Total Guru</div>
            <div class="text-xl font-semibold text-gray-900">{{ overview.total_guru || 0 }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Total Kelas</div>
            <div class="text-xl font-semibold text-gray-900">{{ overview.total_kelas || 0 }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Total Jurusan</div>
            <div class="text-xl font-semibold text-gray-900">{{ overview.total_jurusan || 0 }}</div>
          </div>
        </div>
        <p v-else class="text-gray-500">Memuat data...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const toast = useToast()
const stats = ref({})
const overview = ref(null)
const tahunAjaranAktif = ref('')

async function fetchDashboard() {
  try {
    const res = await axios.get('/dashboard/kepala-sekolah')
    const raporStats = res.data?.rapor_approval_stats
    if (raporStats) {
      stats.value = raporStats
    }
    const ov = res.data?.overview
    if (ov) {
      overview.value = ov
    }
    const taAktif = res.data?.tahun_ajaran_aktif
    if (taAktif?.tahun) {
      tahunAjaranAktif.value = taAktif.tahun
    }
  } catch (e) {
    console.error('fetch dashboard:', e)
    toast.error('Gagal memuat data dashboard')
  }
}

onMounted(() => {
  fetchDashboard()
})
</script>
