<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Rapor Belajar
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Lihat rapor hasil belajar Anda per periode
        </p>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <FormField
            v-model="filters.tahun_ajaran_id"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="full_description"
            @update:model-value="onFiltersChange"
          />
          <FormField
            v-model="filters.jenis"
            type="select"
            label="Periode"
            placeholder="Pilih Periode"
            :options="periodeOptions"
            option-value="value"
            option-label="label"
            @update:model-value="onFiltersChange"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data rapor...</p>
      </div>

      <!-- Empty Filters -->
      <div v-else-if="!filtersReady" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih Tahun Ajaran dan Periode</h3>
        <p class="mt-1 text-sm text-gray-500">
          Pilih tahun ajaran serta periode (STS/SAS) untuk menampilkan rapor hasil belajar.
        </p>
      </div>

      <!-- Detail Content -->
      <div v-else-if="raporDetail" class="bg-white shadow rounded-lg overflow-hidden">
        <div class="p-6 space-y-6">
          <!-- Header -->
          <div class="border-b border-gray-200 pb-4">
            <h3 class="text-lg font-medium text-gray-900">Rapor Hasil Belajar</h3>
            <p class="text-sm text-gray-500 mt-1">
              {{ raporDetail.tahun_ajaran?.label }} | {{ raporDetail.periode }}
            </p>
          </div>

          <!-- Nilai Akademik -->
          <div v-if="hasNilai" class="space-y-4">
            <h4 class="font-medium text-gray-900">Nilai Akademik</h4>
            <div class="overflow-x-auto">
              <table class="table">
                <thead>
                  <tr>
                    <th>Mata Pelajaran</th>
                    <th class="text-center">Nilai Rapor</th>
                    <th>Deskripsi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="row in flattenNilai"
                    :key="row.nama_mapel + row.nilai_rapor"
                  >
                    <td>
                      <div class="font-medium text-gray-900">{{ row.nama_mapel }}</div>
                    </td>
                    <td class="text-center">
                      <span
                        :class="getNilaiColor(row.nilai_rapor)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      >
                        {{ row.nilai_rapor }}
                      </span>
                    </td>
                    <td class="text-sm text-gray-500">{{ row.deskripsi || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-else class="rounded-lg border border-gray-200 p-6 text-center text-sm text-gray-500">
            Belum ada nilai untuk periode ini.
          </div>

          <!-- Kehadiran -->
          <div v-if="raporDetail.kehadiran" class="pt-4 border-t border-gray-200">
            <h4 class="font-medium text-gray-900 mb-3">Kehadiran</h4>
            <div class="grid grid-cols-3 gap-4">
              <div class="bg-red-50 p-4 rounded-lg">
                <div class="text-sm text-red-600 font-medium">Sakit</div>
                <div class="text-2xl font-bold text-red-900">{{ raporDetail.kehadiran.sakit || 0 }}</div>
              </div>
              <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-sm text-yellow-600 font-medium">Izin</div>
                <div class="text-2xl font-bold text-yellow-900">{{ raporDetail.kehadiran.izin || 0 }}</div>
              </div>
              <div class="bg-orange-50 p-4 rounded-lg">
                <div class="text-sm text-orange-600 font-medium">Tanpa Keterangan</div>
                <div class="text-2xl font-bold text-orange-900">{{ raporDetail.kehadiran.tanpa_keterangan || 0 }}</div>
              </div>
            </div>
          </div>

          <!-- Catatan Akademik -->
          <div v-if="raporDetail.catatan_akademik?.catatan" class="pt-4 border-t border-gray-200">
            <h4 class="font-medium text-gray-900 mb-3">Catatan Akademik</h4>
            <div class="bg-gray-50 p-4 rounded-lg">
              <p class="text-sm text-gray-700">{{ raporDetail.catatan_akademik.catatan }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Error / Empty result -->
      <div v-else-if="filtersReady && !loading" class="bg-white shadow rounded-lg p-8 text-center">
        <p class="text-sm text-gray-500">{{ errorMessage || 'Data rapor tidak tersedia untuk periode ini.' }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'

const toast = useToast()

const loading = ref(false)
const raporDetail = ref(null)
const errorMessage = ref('')

const filters = ref({
  tahun_ajaran_id: '',
  jenis: ''
})

const periodeOptions = [
  { value: 'sts', label: 'Tengah Semester (STS)' },
  { value: 'sas', label: 'Akhir Semester (SAS)' }
]

const tahunAjaranOptions = computed(() => {
  const list = tahunAjaranRaw.value
  if (!Array.isArray(list)) return []
  return list.map((ta) => ({
    ...ta,
    full_description: `${ta.tahun ?? ''}/${(Number(ta.tahun) || 0) + 1} - Semester ${ta.semester ?? ''}`
  }))
})

const tahunAjaranRaw = ref([])
const filtersReady = computed(
  () => !!filters.value.tahun_ajaran_id && !!filters.value.jenis
)

const flattenNilai = computed(() => {
  const byKelompok = raporDetail.value?.nilai_by_kelompok
  if (!byKelompok || typeof byKelompok !== 'object') return []
  const order = ['umum', 'kejuruan', 'muatan_lokal']
  const out = []
  for (const key of order) {
    const rows = byKelompok[key] || []
    for (const r of rows) {
      if (r && (r.nama_mapel || r.nilai_rapor !== undefined)) {
        out.push({
          nama_mapel: r.nama_mapel ?? '-',
          nilai_rapor: r.nilai_rapor ?? '-',
          deskripsi: r.deskripsi ?? '-'
        })
      }
    }
  }
  return out
})

const hasNilai = computed(() => flattenNilai.value.length > 0)

function onFiltersChange() {
  if (filtersReady.value) loadDetail()
  else {
    raporDetail.value = null
    errorMessage.value = ''
  }
}

async function fetchTahunAjaran() {
  try {
    const res = await axios.get('/lookup/tahun-ajaran')
    tahunAjaranRaw.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    console.error('fetch tahun ajaran:', e)
    toast.error('Gagal memuat data tahun ajaran')
    tahunAjaranRaw.value = []
  }
}

async function loadDetail() {
  if (!filters.value.tahun_ajaran_id || !filters.value.jenis) return
  loading.value = true
  raporDetail.value = null
  errorMessage.value = ''
  try {
    const params = new URLSearchParams({
      tahun_ajaran_id: filters.value.tahun_ajaran_id,
      jenis: filters.value.jenis
    })
    const res = await axios.get(`/siswa/rapor/detail?${params}`)
    raporDetail.value = res.data
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Gagal memuat data rapor'
    toast.error(errorMessage.value)
    raporDetail.value = null
  } finally {
    loading.value = false
  }
}

function getNilaiColor(nilai) {
  if (nilai === '-' || nilai === null || nilai === undefined) return 'bg-gray-100 text-gray-800'
  const num = parseFloat(nilai)
  if (isNaN(num)) return 'bg-gray-100 text-gray-800'
  if (num >= 90) return 'bg-green-100 text-green-800'
  if (num >= 80) return 'bg-blue-100 text-blue-800'
  if (num >= 70) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

onMounted(() => {
  fetchTahunAjaran()
})
</script>
