<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Dashboard Siswa
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Selamat datang, {{ authStore.user?.name }}
          </p>
        </div>
      </div>

      <!-- Profile Card -->
      <div class="mt-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="h-16 w-16 bg-blue-600 rounded-full flex items-center justify-center text-white text-xl font-bold">
                  {{ authStore.user?.name?.charAt(0) }}
                </div>
              </div>
              <div class="ml-5">
                <h3 class="text-lg font-medium text-gray-900">{{ profile.nama_lengkap || authStore.user?.name }}</h3>
                <p class="text-sm text-gray-500">NIS: {{ profile.nis }}</p>
                <p class="text-sm text-gray-500">Kelas: {{ profile.kelas || '-' }}</p>
                <p class="text-sm text-gray-500">Jurusan: {{ profile.jurusan || '-' }}</p>
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

const profile = ref({})
const loading = ref(true)

const fetchDashboardData = async () => {
  // Check if user is still authenticated before fetching
  if (!authStore.isAuthenticated) {
    loading.value = false
    return
  }

  try {
    const response = await axios.get('/dashboard/siswa')
    profile.value = response.data.profile || {}
  } catch (error) {
    // Don't show error if user is not authenticated (likely logged out)
    if (error.response?.status === 401 || !authStore.isAuthenticated) {
      // User is logged out, don't show error
      return
    }
    console.error('Error fetching dashboard data:', error)
    toast.error('Gagal mengambil data dashboard')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})
</script>
