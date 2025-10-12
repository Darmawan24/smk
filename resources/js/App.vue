<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav v-if="authStore.isAuthenticated" class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/" class="text-xl font-bold text-blue-600">
                SIAKAD SMK
              </router-link>
            </div>

            <!-- Navigation Links -->
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link 
                to="/" 
                class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                active-class="border-blue-500 text-gray-900"
                exact-active-class="border-blue-500 text-gray-900"
              >
                Dashboard
              </router-link>

              <!-- Role-specific navigation -->
              <template v-if="authStore.user?.role === 'admin'">
                <router-link 
                  to="/admin/siswa" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Data Siswa
                </router-link>
                <router-link 
                  to="/admin/guru" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Data Guru
                </router-link>
                <router-link 
                  to="/admin/kelas" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Data Kelas
                </router-link>
              </template>

              <template v-if="authStore.user?.role === 'guru'">
                <router-link 
                  to="/guru/nilai" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Input Nilai
                </router-link>
                <router-link 
                  to="/guru/capaian-pembelajaran" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Capaian Pembelajaran
                </router-link>
              </template>

              <template v-if="authStore.user?.role === 'wali_kelas'">
                <router-link 
                  to="/wali-kelas/nilai-kelas" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Nilai Kelas
                </router-link>
                <router-link 
                  to="/wali-kelas/kehadiran" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Kehadiran
                </router-link>
                <router-link 
                  to="/wali-kelas/rapor" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Rapor
                </router-link>
              </template>

              <template v-if="authStore.user?.role === 'kepala_sekolah'">
                <router-link 
                  to="/kepala-sekolah/rapor-approval" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Approval Rapor
                </router-link>
                <router-link 
                  to="/kepala-sekolah/rekap" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Rekap & Laporan
                </router-link>
              </template>

              <template v-if="authStore.user?.role === 'siswa'">
                <router-link 
                  to="/siswa/nilai" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Nilai Saya
                </router-link>
                <router-link 
                  to="/siswa/rapor" 
                  class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                  active-class="border-blue-500 text-gray-900"
                >
                  Rapor
                </router-link>
              </template>
            </div>
          </div>

          <!-- Right side -->
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
            <!-- Profile dropdown -->
            <div class="ml-3 relative">
              <div>
                <button @click="showProfileMenu = !showProfileMenu" type="button" class="bg-white flex text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                  <span class="sr-only">Open user menu</span>
                  <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">
                    {{ userInitials }}
                  </div>
                </button>
              </div>

              <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                <div v-show="showProfileMenu" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                  <div class="px-4 py-2 text-sm text-gray-700 border-b">
                    <div class="font-medium">{{ authStore.user?.name }}</div>
                    <div class="text-xs text-gray-500">{{ roleText }}</div>
                  </div>
                  <a href="#" @click.prevent="logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem" tabindex="-1">
                    Logout
                  </a>
                </div>
              </transition>
            </div>
          </div>

          <!-- Mobile menu button -->
          <div class="-mr-2 flex items-center sm:hidden">
            <button @click="showMobileMenu = !showMobileMenu" type="button" class="bg-white inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false">
              <span class="sr-only">Open main menu</span>
              <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile menu -->
      <div v-show="showMobileMenu" class="sm:hidden" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1">
          <router-link to="/" class="bg-blue-50 border-blue-500 text-blue-700 block pl-3 pr-4 py-2 border-l-4 text-base font-medium" exact-active-class="bg-blue-50 border-blue-500 text-blue-700">
            Dashboard
          </router-link>
          <!-- Add mobile navigation items here -->
        </div>
        <div class="pt-4 pb-3 border-t border-gray-200">
          <div class="flex items-center px-4">
            <div class="flex-shrink-0">
              <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                {{ userInitials }}
              </div>
            </div>
            <div class="ml-3">
              <div class="text-base font-medium text-gray-800">{{ authStore.user?.name }}</div>
              <div class="text-sm font-medium text-gray-500">{{ roleText }}</div>
            </div>
          </div>
          <div class="mt-3 space-y-1">
            <a href="#" @click.prevent="logout" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">
              Logout
            </a>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main content -->
    <main class="flex-1">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useToast } from 'vue-toastification'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const showProfileMenu = ref(false)
const showMobileMenu = ref(false)

const userInitials = computed(() => {
  if (!authStore.user?.name) return ''
  return authStore.user.name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const roleText = computed(() => {
  const roles = {
    admin: 'Administrator',
    guru: 'Guru',
    wali_kelas: 'Wali Kelas',
    kepala_sekolah: 'Kepala Sekolah',
    siswa: 'Siswa'
  }
  return roles[authStore.user?.role] || ''
})

const logout = async () => {
  try {
    await authStore.logout()
    toast.success('Berhasil logout')
    router.push('/login')
  } catch (error) {
    toast.error('Gagal logout')
  }
}

onMounted(async () => {
  if (authStore.token) {
    try {
      await authStore.getUser()
    } catch (error) {
      console.error('Failed to get user data:', error)
    }
  }
})
</script>