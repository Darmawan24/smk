<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-6">
        <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
          Kelompok P5
        </h2>
        <p class="mt-1 text-sm text-gray-500">
          Atur kelompok projek P5 (guru fasilitator dan siswa per kelompok)
        </p>
      </div>

      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <FormField
          v-model="selectedP5Id"
          type="select"
          label="Pilih Projek P5"
          placeholder="Pilih projek P5"
          :options="p5Options"
          option-value="id"
          option-label="judul_display"
          @update:model-value="onP5Select"
        />
      </div>

      <div v-if="selectedP5Id && selectedP5" class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Kelompok – {{ selectedP5.judul || selectedP5.tema || 'P5' }}
        </h3>
        <p class="text-sm text-gray-500 mb-6">
          Atur kelompok dengan guru fasilitator dan siswa. Guru dan siswa yang sudah dipilih di kelompok lain tidak akan tampil lagi.
        </p>

        <div v-if="loadingKelompok" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <template v-else>
          <div v-for="(row, index) in kelompokForm" :key="index" class="border rounded-lg p-4 bg-gray-50/50 space-y-4 mb-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-700">Kelompok {{ index + 1 }}</span>
              <button
                v-if="kelompokForm.length > 1"
                type="button"
                @click="removeKelompokRow(index)"
                class="text-red-600 hover:text-red-800 text-sm"
              >
                Hapus kelompok
              </button>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <FormField
                v-model="row.guru_id"
                type="select"
                label="Guru Fasilitator"
                placeholder="Pilih guru fasilitator"
                :options="guruOptionsForKelompokRow(index)"
                option-value="id"
                option-label="nama_lengkap"
              />
              <div class="space-y-1 sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Siswa</label>
                <MultiSelect
                  v-model="row.siswa_ids"
                  :options="siswaOptionsForKelompokRow(index)"
                  option-value="id"
                  option-label="label_display"
                  placeholder="Pilih siswa (nama - kelas)"
                  :searchable="true"
                  :max-height="180"
                />
              </div>
            </div>
          </div>

          <button
            type="button"
            @click="addKelompokRow"
            class="btn btn-secondary w-full sm:w-auto mb-6"
          >
            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah kelompok lainnya
          </button>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button
              type="button"
              @click="saveKelompok"
              :disabled="savingKelompok"
              class="btn btn-primary"
            >
              <svg v-if="savingKelompok" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ savingKelompok ? 'Menyimpan...' : 'Simpan Kelompok' }}
            </button>
          </div>
        </template>
      </div>

      <div v-else-if="selectedP5Id && !loadingKelompok" class="bg-white shadow rounded-lg p-8 text-center text-gray-500">
        Pilih projek P5 di atas untuk mengatur kelompok.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import FormField from '../../../components/ui/FormField.vue'
import MultiSelect from '../../../components/ui/MultiSelect.vue'

const toast = useToast()
const route = useRoute()

const p5List = ref([])
const selectedP5Id = ref('')
const selectedP5 = ref(null)
const kelompokForm = ref([])
const allFasilitatorOptions = ref([])
const allSiswaOptions = ref([])
const loadingKelompok = ref(false)
const savingKelompok = ref(false)

const p5Options = ref([])

function judulDisplay(item) {
  return item?.judul || item?.tema || `P5 #${item?.id || ''}`
}

async function fetchP5List() {
  try {
    const res = await axios.get('/admin/p5?per_page=200')
    const data = res.data?.data ?? res.data ?? []
    const list = Array.isArray(data) ? data : []
    p5List.value = list
    p5Options.value = list.map((p) => ({ ...p, judul_display: judulDisplay(p) }))
  } catch (e) {
    toast.error('Gagal memuat daftar P5')
    p5List.value = []
    p5Options.value = []
  }
}

async function onP5Select() {
  if (!selectedP5Id.value) {
    selectedP5.value = null
    kelompokForm.value = []
    return
  }
  selectedP5.value = p5List.value.find((p) => p.id == selectedP5Id.value) || null
  await loadKelompokForP5(selectedP5Id.value)
}

async function loadKelompokForP5(p5Id) {
  if (!p5Id) return
  loadingKelompok.value = true
  try {
    const [kelompokRes, fasRes, siswaRes] = await Promise.all([
      axios.get(`/admin/p5/${p5Id}/kelompok`),
      axios.get(`/admin/p5/${p5Id}/available-fasilitator-kelompok`),
      axios.get(`/admin/p5/${p5Id}/available-siswa-kelompok`)
    ])
    const kelompok = kelompokRes.data?.kelompok || []
    const existingGurus = (kelompokRes.data?.kelompok || []).map((k) => k.guru).filter(Boolean)
    const existingSiswa = (kelompokRes.data?.kelompok || []).flatMap((k) => k.siswa || [])
    allFasilitatorOptions.value = [...existingGurus, ...(fasRes.data || [])].filter(
      (g, i, arr) => arr.findIndex((x) => x.id === g.id) === i
    )
    allSiswaOptions.value = [...existingSiswa, ...(siswaRes.data || [])].filter(
      (s, i, arr) => arr.findIndex((x) => x.id === s.id) === i
    )
    kelompokForm.value =
      kelompok.length > 0
        ? kelompok.map((k) => ({
            guru_id: k.guru_id || k.guru?.id,
            siswa_ids: (k.siswa || []).map((s) => s.id)
          }))
        : [{ guru_id: '', siswa_ids: [] }]
  } catch (e) {
    console.error('Error loading kelompok:', e)
    toast.error('Gagal memuat data kelompok')
    kelompokForm.value = [{ guru_id: '', siswa_ids: [] }]
  } finally {
    loadingKelompok.value = false
  }
}

function guruOptionsForKelompokRow(rowIndex) {
  const selectedGuruIds = kelompokForm.value
    .map((r, i) => (i !== rowIndex ? r.guru_id : null))
    .filter(Boolean)
  return (allFasilitatorOptions.value || []).filter(
    (g) => g.id === kelompokForm.value[rowIndex]?.guru_id || !selectedGuruIds.includes(g.id)
  )
}

function siswaOptionsForKelompokRow(rowIndex) {
  const selectedSiswaIds = new Set()
  kelompokForm.value.forEach((r, i) => {
    if (i !== rowIndex && r.siswa_ids) r.siswa_ids.forEach((id) => selectedSiswaIds.add(id))
  })
  return (allSiswaOptions.value || []).filter(
    (s) => (kelompokForm.value[rowIndex]?.siswa_ids || []).includes(s.id) || !selectedSiswaIds.has(s.id)
  )
}

function addKelompokRow() {
  kelompokForm.value.push({ guru_id: '', siswa_ids: [] })
}

function removeKelompokRow(index) {
  kelompokForm.value.splice(index, 1)
}

async function saveKelompok() {
  if (!selectedP5Id.value) return
  const payload = kelompokForm.value.filter(
    (r) => r.guru_id && r.siswa_ids && r.siswa_ids.length > 0
  )
  if (payload.length === 0) {
    toast.error('Minimal satu kelompok dengan guru fasilitator dan siswa')
    return
  }
  savingKelompok.value = true
  try {
    await axios.post(`/admin/p5/${selectedP5Id.value}/kelompok`, {
      kelompok: payload.map((r) => ({ guru_id: r.guru_id, siswa_ids: r.siswa_ids }))
    })
    toast.success('Kelompok berhasil disimpan')
    await loadKelompokForP5(selectedP5Id.value)
  } catch (e) {
    toast.error(e.response?.data?.message || 'Gagal menyimpan kelompok')
  } finally {
    savingKelompok.value = false
  }
}

onMounted(async () => {
  await fetchP5List()
  const p5IdFromQuery = route.query.p5_id || route.query.p5Id
  if (p5IdFromQuery) {
    selectedP5Id.value = String(p5IdFromQuery)
    selectedP5.value = p5List.value.find((p) => p.id == p5IdFromQuery) || null
    await loadKelompokForP5(selectedP5Id.value)
  }
})
</script>
