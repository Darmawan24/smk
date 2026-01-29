<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <DataTable
        title="Data UKK (Uji Kompetensi Keahlian)"
        description="Kelola data ujian UKK (tahun, jurusan, kelas, DU/DI, penguji)"
        :data="events"
        :columns="columns"
        :loading="loading"
        empty-message="Belum ada data UKK"
        empty-description="Mulai dengan menambahkan data UKK."
        :searchable="true"
        @search="handleSearch"
      >
        <template #actions>
          <button @click="openForm" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah Data UKK
          </button>
        </template>

        <template #cell-tahun="{ item }">
          <div class="text-sm font-medium text-gray-900">{{ item.tahun_ajaran?.tahun || '-' }}</div>
        </template>

        <template #cell-jurusan="{ item }">
          <div class="text-sm text-gray-900">{{ item.jurusan?.nama_jurusan || '-' }}</div>
        </template>

        <template #cell-kelas="{ item }">
          <div class="text-sm text-gray-900">{{ item.kelas?.nama_kelas || '-' }}</div>
        </template>

        <template #cell-nama_du_di="{ item }">
          <div class="text-sm text-gray-900">{{ item.nama_du_di || '-' }}</div>
        </template>

        <template #cell-tanggal_ujian="{ item }">
          <div class="text-sm text-gray-900">{{ formatDate(item.tanggal_ujian) }}</div>
        </template>

        <template #cell-penguji="{ item }">
          <div class="text-sm">
            <div class="text-gray-900">{{ item.penguji_internal?.nama_lengkap || '-' }}</div>
            <div v-if="item.penguji_eksternal" class="text-xs text-gray-500">Eksternal: {{ item.penguji_eksternal }}</div>
          </div>
        </template>

        <template #row-actions="{ item }">
          <div class="flex items-center space-x-2">
            <button @click="editEvent(item)" class="text-blue-600 hover:text-blue-900" title="Edit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </button>
            <button @click="confirmDelete(item)" class="text-red-600 hover:text-red-900" title="Hapus">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
        </template>
      </DataTable>

      <Modal v-model:show="showForm" :title="isEditing ? 'Edit Data UKK' : 'Tambah Data UKK'" size="lg">
        <form @submit.prevent="submitForm" id="data-ukk-form" class="space-y-4">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField
              v-model="form.tahun_ajaran_id"
              type="select"
              label="Tahun Ajaran"
              placeholder="Pilih tahun ajaran"
              :options="tahunAjaranOptions"
              option-value="id"
              option-label="tahun"
              required
              :error="errors.tahun_ajaran_id"
              :disabled="isEditing"
            />
            <FormField
              v-model="form.jurusan_id"
              type="select"
              label="Jurusan"
              placeholder="Pilih jurusan"
              :options="jurusanOptions"
              option-value="id"
              option-label="nama_jurusan"
              required
              :error="errors.jurusan_id"
              :disabled="isEditing"
              @update:model-value="onJurusanChange"
            />
          </div>
          <FormField
            v-model="form.kelas_id"
            type="select"
            label="Kelas"
            placeholder="Pilih kelas"
            :options="kelasOptions"
            option-value="id"
            option-label="nama_kelas"
            required
            :error="errors.kelas_id"
            :disabled="!form.jurusan_id || isEditing"
            @update:model-value="() => {}"
          />
          <FormField
            v-model="form.nama_du_di"
            type="text"
            label="Nama DU/DI"
            placeholder="Masukkan nama DU/DI (opsional)"
            :error="errors.nama_du_di"
          />
          <FormField
            v-model="form.tanggal_ujian"
            type="date"
            label="Tanggal Ujian"
            required
            :error="errors.tanggal_ujian"
          />
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField
              v-model="form.penguji_internal_id"
              type="select"
              label="Penguji Internal"
              placeholder="Pilih penguji internal"
              :options="guruOptions.map(g => ({ value: g.id, label: `${g.nama_lengkap || g.user?.name}${g.nuptk ? ' - ' + g.nuptk : ''}` }))"
              required
              :error="errors.penguji_internal_id"
            />
            <FormField
              v-model="form.penguji_eksternal"
              type="text"
              label="Penguji Eksternal"
              placeholder="Nama penguji eksternal (opsional)"
              :error="errors.penguji_eksternal"
            />
          </div>
        </form>
        <template #footer>
          <button type="submit" form="data-ukk-form" :disabled="submitting" class="btn btn-primary">
            <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ submitting ? 'Menyimpan...' : 'Simpan' }}
          </button>
          <button type="button" @click="closeForm" class="btn btn-secondary mr-3">Batal</button>
        </template>
      </Modal>

      <ConfirmDialog
        v-model:show="showDeleteConfirm"
        title="Hapus Data UKK"
        :message="`Hapus data UKK ${selectedEvent?.jurusan?.nama_jurusan || ''} - ${selectedEvent?.kelas?.nama_kelas || ''}?`"
        confirm-text="Ya, Hapus"
        type="error"
        :loading="deleting"
        @confirm="doDelete"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import DataTable from '../../../components/ui/DataTable.vue'
import Modal from '../../../components/ui/Modal.vue'
import FormField from '../../../components/ui/FormField.vue'
import ConfirmDialog from '../../../components/ui/ConfirmDialog.vue'

const toast = useToast()
const events = ref([])
const loading = ref(true)
const submitting = ref(false)
const deleting = ref(false)
const showForm = ref(false)
const showDeleteConfirm = ref(false)
const isEditing = ref(false)
const selectedEvent = ref(null)

const form = reactive({
  tahun_ajaran_id: '',
  jurusan_id: '',
  kelas_id: '',
  nama_du_di: '',
  tanggal_ujian: '',
  penguji_internal_id: '',
  penguji_eksternal: ''
})

const errors = ref({})
const jurusanOptions = ref([])
const kelasOptions = ref([])
const guruOptions = ref([])
const tahunAjaranOptions = ref([])

const columns = [
  { key: 'tahun', label: 'Tahun Ajaran' },
  { key: 'jurusan', label: 'Jurusan' },
  { key: 'kelas', label: 'Kelas' },
  { key: 'nama_du_di', label: 'DU/DI' },
  { key: 'tanggal_ujian', label: 'Tanggal Ujian' },
  { key: 'penguji', label: 'Penguji' },
  { key: 'actions', label: 'Aksi' }
]

function formatDate (d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('id-ID')
}

async function fetchEvents () {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.search) params.append('search', filters.search)
    params.append('per_page', 100)
    const res = await axios.get(`/admin/ukk-events?${params}`)
    events.value = res.data?.data ?? res.data ?? []
  } catch (e) {
    toast.error('Gagal mengambil data UKK')
    events.value = []
  } finally {
    loading.value = false
  }
}

const filters = reactive({ search: '' })

function handleSearch (q) {
  filters.search = q
  fetchEvents()
}

async function fetchJurusan () {
  try {
    const r = await axios.get('/lookup/jurusan')
    jurusanOptions.value = r.data ?? []
  } catch (_) {}
}

async function fetchKelas (jurusanId) {
  if (!jurusanId) { kelasOptions.value = []; return }
  try {
    const r = await axios.get('/admin/kelas', { params: { per_page: 100 } })
    const all = r.data?.data ?? r.data ?? []
    kelasOptions.value = all.filter(k => k.jurusan_id == jurusanId && (k.tingkat == '12' || k.tingkat === 12))
  } catch (_) {
    kelasOptions.value = []
  }
}

async function fetchGuru () {
  try {
    const r = await axios.get('/lookup/guru')
    guruOptions.value = r.data ?? []
  } catch (_) {}
}

async function fetchTahunAjaran () {
  try {
    const r = await axios.get('/admin/tahun-ajaran', { params: { per_page: 100 } })
    const all = r.data?.data ?? r.data ?? []
    tahunAjaranOptions.value = all.filter(ta => ta.semester === '2' || ta.semester === 2)
  } catch (_) {
    tahunAjaranOptions.value = []
  }
}

function onJurusanChange () {
  form.kelas_id = ''
  fetchKelas(form.jurusan_id)
}

function openForm () {
  isEditing.value = false
  selectedEvent.value = null
  Object.assign(form, {
    tahun_ajaran_id: '', jurusan_id: '', kelas_id: '', nama_du_di: '',
    tanggal_ujian: '', penguji_internal_id: '', penguji_eksternal: ''
  })
  errors.value = {}
  kelasOptions.value = []
  showForm.value = true
}

function closeForm () {
  showForm.value = false
  fetchEvents()
}

function editEvent (item) {
  isEditing.value = true
  selectedEvent.value = item
  form.tahun_ajaran_id = item.tahun_ajaran_id
  form.jurusan_id = item.jurusan_id
  form.kelas_id = item.kelas_id
  form.nama_du_di = item.nama_du_di || ''
  form.tanggal_ujian = item.tanggal_ujian ? item.tanggal_ujian.split('T')[0] : ''
  form.penguji_internal_id = item.penguji_internal_id
  form.penguji_eksternal = item.penguji_eksternal || ''
  errors.value = {}
  fetchKelas(item.jurusan_id)
  showForm.value = true
}

async function submitForm () {
  errors.value = {}
  submitting.value = true
  try {
    const payload = {
      tahun_ajaran_id: form.tahun_ajaran_id,
      jurusan_id: form.jurusan_id,
      kelas_id: form.kelas_id,
      nama_du_di: form.nama_du_di || null,
      tanggal_ujian: form.tanggal_ujian,
      penguji_internal_id: form.penguji_internal_id,
      penguji_eksternal: form.penguji_eksternal || null
    }
    if (isEditing.value && selectedEvent.value?.id) {
      await axios.put(`/admin/ukk-events/${selectedEvent.value.id}`, payload)
      toast.success('Data UKK berhasil diperbarui')
    } else {
      await axios.post('/admin/ukk-events', payload)
      toast.success('Data UKK berhasil ditambahkan')
    }
    closeForm()
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data?.errors ?? {}
      toast.error(e.response?.data?.message ?? 'Validasi gagal')
    } else {
      toast.error(e.response?.data?.message ?? 'Gagal menyimpan data UKK')
    }
  } finally {
    submitting.value = false
  }
}

function confirmDelete (item) {
  selectedEvent.value = item
  showDeleteConfirm.value = true
}

async function doDelete () {
  if (!selectedEvent.value?.id) return
  deleting.value = true
  try {
    await axios.delete(`/admin/ukk-events/${selectedEvent.value.id}`)
    toast.success('Data UKK berhasil dihapus')
    showDeleteConfirm.value = false
    fetchEvents()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Gagal menghapus data UKK')
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  fetchEvents()
  fetchJurusan()
  fetchGuru()
  fetchTahunAjaran()
})
</script>
