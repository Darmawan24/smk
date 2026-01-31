<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Dashboard Administrator
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Selamat datang di Pengolahan Nilai Rapor Kurikulum Merdeka
          </p>
        </div>
      </div>

      <!-- Stats -->
      <div class="mt-8">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <!-- Total Siswa -->
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Siswa</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.total_siswa || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
              <div class="text-sm">
                <router-link to="/admin/siswa" class="font-medium text-blue-700 hover:text-blue-900">
                  Lihat semua siswa
                </router-link>
              </div>
            </div>
          </div>

          <!-- Total Guru -->
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Guru</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.total_guru || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
              <div class="text-sm">
                <router-link to="/admin/guru" class="font-medium text-green-700 hover:text-green-900">
                  Lihat semua guru
                </router-link>
              </div>
            </div>
          </div>

          <!-- Total Kelas -->
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Kelas</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.total_kelas || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
              <div class="text-sm">
                <router-link to="/admin/kelas" class="font-medium text-yellow-700 hover:text-yellow-900">
                  Lihat semua kelas
                </router-link>
              </div>
            </div>
          </div>

          <!-- Total Mata Pelajaran -->
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Mata Pelajaran</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.total_mapel || 0 }}</dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
              <div class="text-sm">
                <router-link to="/admin/mata-pelajaran" class="font-medium text-purple-700 hover:text-purple-900">
                  Lihat mata pelajaran
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tahun Ajaran Aktif -->
      <div class="mt-8" v-if="stats.tahun_ajaran_aktif">
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">
                Tahun Ajaran Aktif
              </h3>
              <div class="mt-2 text-sm text-blue-700">
                <p>{{ stats.tahun_ajaran_aktif?.tahun || '-' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Class Capacity -->
      <div class="mt-8">
        <div class="bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Kapasitas Kelas</h3>
            <div class="mt-6">
              <div v-if="stats.class_capacity?.length" class="space-y-4">
                <div v-for="kelas in stats.class_capacity.slice(0, 5)" :key="kelas.kelas" class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="flex items-center justify-between text-sm">
                      <span class="font-medium text-gray-900">{{ kelas.kelas }}</span>
                      <span class="text-gray-500">{{ kelas.siswa_count }}/{{ kelas.kapasitas }}</span>
                    </div>
                    <div class="mt-1">
                      <div class="bg-gray-200 rounded-full h-2">
                        <div 
                          class="h-2 rounded-full" 
                          :class="kelas.percentage >= 90 ? 'bg-red-600' : kelas.percentage >= 75 ? 'bg-yellow-600' : 'bg-green-600'"
                          :style="{ width: `${Math.min(kelas.percentage, 100)}%` }"
                        ></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-4">
                <p class="text-sm text-gray-500">Belum ada data kelas</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../../stores/auth'
import { useToast } from 'vue-toastification'

const authStore = useAuthStore()
const toast = useToast()
const stats = ref({})
const loading = ref(true)

const fetchStats = async () => {
  // Check if user is still authenticated before fetching
  if (!authStore.isAuthenticated) {
    loading.value = false
    return
  }

  try {
    const response = await axios.get('/dashboard/admin')
    stats.value = response.data
  } catch (error) {
    // Don't show error if user is not authenticated (likely logged out)
    if (error.response?.status === 401 || !authStore.isAuthenticated) {
      // User is logged out, don't show error
      return
    }
    console.error('Error fetching stats:', error)
    toast.error('Gagal mengambil data statistik')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStats()
})
</script>