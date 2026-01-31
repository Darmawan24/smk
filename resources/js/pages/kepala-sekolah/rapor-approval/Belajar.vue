<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Approve Rapor Belajar
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Ini berisi data nilai rapor siswa pada setiap semester yang sudah dilewati, silahkan klik tombol pada menu Aksi untuk menyetujui atau belum menyetujui.
          </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
          <button 
            @click="bulkApprove" 
            :disabled="selectedRapor.length === 0 || bulkProcessing"
            class="btn btn-primary"
          >
            <svg v-if="bulkProcessing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ bulkProcessing ? 'Memproses...' : `Setujui ${selectedRapor.length} Rapor` }}
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <FormField
            v-model="filters.tahun_ajaran_id"
            type="select"
            label="Tahun Ajaran"
            placeholder="Pilih Tahun Ajaran"
            :options="tahunAjaranOptions"
            option-value="id"
            option-label="full_description"
            @update:model-value="fetchRapor"
          />
          <FormField
            v-model="filters.kelas_id"
            type="select"
            label="Kelas"
            placeholder="Pilih Kelas"
            :options="kelasOptions"
            option-value="id"
            option-label="nama_kelas"
            @update:model-value="fetchRapor"
          />
          <FormField
            v-model="filters.jenis"
            type="select"
            label="Periode"
            placeholder="Pilih Periode"
            :options="jenisOptions"
            option-value="value"
            option-label="label"
            @update:model-value="fetchRapor"
          />
          <FormField
            v-model="filters.status"
            type="select"
            label="Status"
            placeholder="Pilih Status"
            :options="statusOptions"
            option-value="value"
            option-label="label"
            @update:model-value="fetchRapor"
          />
        </div>
      </div>

      <!-- Pilih filter dulu -->
      <div v-if="!filtersComplete" class="bg-white shadow rounded-lg p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Pilih filter terlebih dahulu</h3>
        <p class="mt-1 text-sm text-gray-500">
          Pilih Tahun Ajaran, Kelas, Periode, dan Status untuk menampilkan daftar rapor siswa.
        </p>
      </div>

      <!-- Loading State -->
      <div v-else-if="loading" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Memuat data rapor...</p>
      </div>

      <!-- Rapor Table -->
      <div v-else class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">
              Daftar Rapor
            </h3>
            <div class="flex items-center space-x-3">
              <label class="flex items-center text-sm text-gray-600">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  @change="toggleSelectAll"
                  class="mr-2 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                Pilih Semua
              </label>
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th class="w-12">
                  <input
                    type="checkbox"
                    :checked="allSelected"
                    @change="toggleSelectAll"
                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  />
                </th>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>NISN</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th>Tahun Ajaran</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in displayList" :key="row.rapor?.id ?? row.siswa?.id ?? index" class="hover:bg-gray-50">
                <td>
                  <input
                    v-if="row.rapor && row.periode_sudah_diisi"
                    type="checkbox"
                    :value="row.rapor.id"
                    v-model="selectedRapor"
                    :disabled="row.rapor.status === 'approved' || row.rapor.status === 'published'"
                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:opacity-50"
                  />
                  <span v-else class="inline-block w-4"></span>
                </td>
                <td class="text-center">{{ index + 1 }}</td>
                <td>
                  <div class="flex items-center">
                    <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                      {{ row.siswa?.nama_lengkap?.charAt(0) }}
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-gray-900">{{ row.siswa?.nama_lengkap }}</div>
                    </div>
                  </div>
                </td>
                <td class="text-sm text-gray-900">{{ row.siswa?.nisn || '-' }}</td>
                <td class="text-sm text-gray-900">{{ row.siswa?.nis || '-' }}</td>
                <td>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ row.kelas?.nama_kelas }}
                  </span>
                </td>
                <td>
                  <div class="text-sm text-gray-900">{{ row.tahun_ajaran?.tahun || row.tahun_ajaran?.nama }} - Semester {{ row.tahun_ajaran?.semester }}</div>
                </td>
                <td>
                  <span v-if="row.rapor && row.periode_sudah_diisi" :class="getStatusBadge(row.rapor.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getStatusText(row.rapor.status) }}
                  </span>
                  <span v-else-if="row.rapor && !row.periode_sudah_diisi" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-amber-700 bg-amber-100">
                    Belum lengkap (nilai periode belum diisi)
                  </span>
                  <span v-else-if="!row.rapor && row.periode_sudah_diisi" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-yellow-700 bg-yellow-100">
                    Belum
                  </span>
                  <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-500 bg-gray-100">
                    Belum ada rapor
                  </span>
                </td>
                <td>
                  <div class="flex items-center space-x-2">
                    <button
                      v-if="row.periode_sudah_diisi"
                      @click="openPreviewRaporBelajar(row)"
                      class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                      title="Lihat Rapor (sama dengan cetak rapor Wali Kelas)"
                    >
                      Lihat Rapor
                    </button>
                    <template v-if="row.periode_sudah_diisi">
                      <!-- Setujui: tampil jika status belum disetujui (draft/pending/tidak ada rapor) -->
                      <button
                        v-if="!row.rapor || row.rapor.status === 'pending' || row.rapor.status === 'draft'"
                        @click="!row.rapor ? approveRaporBySiswa(row) : approveRapor(row.rapor)"
                        class="text-green-600 hover:text-green-900 text-sm font-medium"
                        title="Setujui"
                      >
                        Setujui
                      </button>
                      <!-- Belum disetujui: tampil jika status sudah disetujui -->
                      <button
                        v-else-if="row.rapor && (row.rapor.status === 'approved' || row.rapor.status === 'published')"
                        @click="unapproveRapor(row.rapor)"
                        class="text-amber-600 hover:text-amber-900 text-sm font-medium"
                        title="Belum disetujui"
                      >
                        Belum disetujui
                      </button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="displayList.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada rapor</h3>
          <p class="mt-1 text-sm text-gray-500">Belum ada rapor yang tersedia untuk ditinjau.</p>
        </div>
      </div>

      <!-- Rapor Detail Modal -->
      <Modal v-model:show="showRaporDetail" title="Detail Rapor" size="xl">
        <div v-if="selectedRaporDetail" class="space-y-6">
          <!-- Student Info -->
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium text-gray-500">Nama Siswa</label>
                <p class="text-sm text-gray-900">{{ selectedRaporDetail.siswa?.nama_lengkap }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">NIS</label>
                <p class="text-sm text-gray-900">{{ selectedRaporDetail.siswa?.nis }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Kelas</label>
                <p class="text-sm text-gray-900">{{ selectedRaporDetail.kelas?.nama_kelas }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Tahun Ajaran</label>
                <p class="text-sm text-gray-900">{{ selectedRaporDetail.tahun_ajaran?.nama }} - Semester {{ selectedRaporDetail.tahun_ajaran?.semester }}</p>
              </div>
            </div>
          </div>

          <!-- Grades Table -->
          <div>
            <h4 class="text-lg font-medium text-gray-900 mb-4">Nilai Akademik</h4>
            <div class="overflow-x-auto">
              <table class="table">
                <thead>
                  <tr>
                    <th>Mata Pelajaran</th>
                    <th class="text-center">Harian</th>
                    <th class="text-center">UTS</th>
                    <th class="text-center">UAS</th>
                    <th class="text-center">Akhir</th>
                    <th class="text-center">Predikat</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="nilai in selectedRaporDetail.nilai" :key="nilai.id">
                    <td>{{ nilai.mata_pelajaran?.nama_mapel }}</td>
                    <td class="text-center">{{ nilai.nilai_harian || '-' }}</td>
                    <td class="text-center">{{ nilai.nilai_uts || '-' }}</td>
                    <td class="text-center">{{ nilai.nilai_uas || '-' }}</td>
                    <td class="text-center">{{ nilai.nilai_akhir || '-' }}</td>
                    <td class="text-center">
                      <span :class="getPredicateColor(nilai.nilai_akhir)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                        {{ getPredicate(nilai.nilai_akhir) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Summary -->
          <div class="bg-blue-50 rounded-lg p-4">
            <div class="grid grid-cols-3 gap-4 text-center">
              <div>
                <label class="text-sm font-medium text-gray-500">Rata-rata</label>
                <p class="text-lg font-bold text-blue-600">{{ selectedRaporDetail.rata_rata || '-' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Ranking</label>
                <p class="text-lg font-bold text-blue-600">{{ selectedRaporDetail.ranking || '-' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-500">Status</label>
                <p class="text-lg font-bold text-blue-600">{{ getStatusText(selectedRaporDetail.status) }}</p>
              </div>
            </div>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-between">
            <div>
              <button 
                v-if="selectedRaporDetail?.status === 'pending' || selectedRaporDetail?.status === 'draft'" 
                @click="approveRapor(selectedRaporDetail)" 
                class="btn btn-success mr-3"
              >
                Setujui
              </button>
              <button 
                v-if="selectedRaporDetail?.status === 'pending' || selectedRaporDetail?.status === 'draft'" 
                @click="rejectRapor(selectedRaporDetail)" 
                class="btn btn-danger"
              >
                Tolak
              </button>
            </div>
            <button @click="showRaporDetail = false" class="btn btn-secondary">Tutup</button>
          </div>
        </template>
      </Modal>

      <!-- Approval Confirmation -->
      <ConfirmDialog
        v-model:show="showApprovalConfirm"
        title="Setujui Rapor"
        :message="approvalMessage"
        confirm-text="Ya, Setujui"
        type="success"
        :loading="processing"
        @confirm="confirmApproval"
      />

      <!-- Rejection Modal -->
      <Modal v-model:show="showRejectionModal" title="Tolak Rapor" size="md">
        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Berikan alasan penolakan untuk rapor <strong>{{ selectedRaporForAction?.siswa?.nama_lengkap }}</strong>
          </p>
          <FormField
            v-model="rejectionReason"
            type="textarea"
            label="Alasan Penolakan"
            placeholder="Masukkan alasan penolakan..."
            required
            rows="3"
          />
        </div>

        <template #footer>
          <button @click="confirmRejection" :disabled="!rejectionReason || processing" class="btn btn-danger">
            {{ processing ? 'Memproses...' : 'Tolak Rapor' }}
          </button>
          <button @click="showRejectionModal = false" class="btn btn-secondary mr-3">Batal</button>
        </template>
      </Modal>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import FormField from '../../../components/ui/FormField.vue'
import Modal from '../../../components/ui/Modal.vue'
import ConfirmDialog from '../../../components/ui/ConfirmDialog.vue'

const toast = useToast()

// Data
const raporData = ref([])
const tahunAjaranOptions = ref([])
const kelasOptions = ref([])
const selectedRapor = ref([])
const selectedRaporDetail = ref(null)
const selectedRaporForAction = ref(null)
const pendingApproveBySiswa = ref(null)
const rejectionReason = ref('')
// State
const loading = ref(true)
const processing = ref(false)
const bulkProcessing = ref(false)
const showRaporDetail = ref(false)
const showApprovalConfirm = ref(false)
const showRejectionModal = ref(false)

// Filters
const filters = reactive({
  tahun_ajaran_id: '',
  kelas_id: '',
  jenis: '',
  status: ''
})

// Options: hanya Tengah Semester (STS) atau Akhir Semester (SAS)
const jenisOptions = [
  { value: 'sts', label: 'Tengah Semester (STS)' },
  { value: 'sas', label: 'Akhir Semester (SAS)' }
]
const statusOptions = [
  { value: 'setujui', label: 'Setujui' },
  { value: 'belum_disetujui', label: 'Belum Disetujui' }
]

// Computed: filter wajib diisi sebelum tampil list (termasuk status)
const filtersComplete = computed(() => {
  return !!(filters.tahun_ajaran_id && filters.kelas_id && filters.jenis && filters.status)
})

// Normalisasi: backend bisa mengembalikan [{ siswa, rapor, tahun_ajaran, kelas, periode_sudah_diisi }] atau [rapor, ...]
const displayList = computed(() => {
  const d = raporData.value
  if (!d.length) return []
  if (d[0].siswa != null && Object.prototype.hasOwnProperty.call(d[0], 'rapor')) return d
  return d.map(r => ({
    siswa: r.siswa,
    rapor: r,
    tahun_ajaran: r.tahunAjaran || r.tahun_ajaran,
    kelas: r.kelas,
    periode_sudah_diisi: r.periode_sudah_diisi ?? false
  }))
})

const allSelected = computed(() => {
  const pendingRapor = displayList.value.filter(row => {
    const rapor = row.rapor
    return rapor && row.periode_sudah_diisi && (rapor.status === 'pending' || rapor.status === 'draft')
  })
  return pendingRapor.length > 0 && selectedRapor.value.length === pendingRapor.length
})

const approvalMessage = computed(() => {
  if (pendingApproveBySiswa.value) {
    return `Apakah Anda yakin ingin menyetujui rapor ${pendingApproveBySiswa.value.siswa?.nama_lengkap}?`
  }
  if (selectedRapor.value.length > 1) {
    return `Apakah Anda yakin ingin menyetujui ${selectedRapor.value.length} rapor yang dipilih?`
  } else if (selectedRaporForAction.value) {
    return `Apakah Anda yakin ingin menyetujui rapor ${selectedRaporForAction.value.siswa?.nama_lengkap}?`
  }
  return 'Apakah Anda yakin ingin menyetujui rapor ini?'
})

// Methods
const fetchTahunAjaran = async () => {
  try {
    const response = await axios.get('/lookup/tahun-ajaran')
    tahunAjaranOptions.value = response.data.map(ta => ({
      ...ta,
      full_description: `${ta.tahun} - Semester ${ta.semester}`
    }))
    
    // Set current active year as default
    const activeYear = tahunAjaranOptions.value.find(t => t.is_active)
    if (activeYear) {
      filters.tahun_ajaran_id = activeYear.id
    }
  } catch (error) {
    console.error('Failed to fetch tahun ajaran:', error)
  }
}

const fetchKelas = async () => {
  try {
    const response = await axios.get('/lookup/kelas')
    kelasOptions.value = response.data
  } catch (error) {
    console.error('Failed to fetch kelas:', error)
  }
}

const fetchRapor = async () => {
  if (!filters.tahun_ajaran_id || !filters.kelas_id || !filters.jenis || !filters.status) {
    raporData.value = []
    loading.value = false
    return
  }
  try {
    loading.value = true
    const params = new URLSearchParams()
    params.append('tahun_ajaran_id', filters.tahun_ajaran_id)
    params.append('kelas_id', filters.kelas_id)
    params.append('jenis', filters.jenis)
    params.append('status', filters.status)
    
    const response = await axios.get(`/kepala-sekolah/rapor-approval?${params}`)
    raporData.value = response.data.data || []
    selectedRapor.value = []
  } catch (error) {
    toast.error('Gagal mengambil data rapor')
    console.error(error)
    raporData.value = []
  } finally {
    loading.value = false
  }
}

const toggleSelectAll = () => {
  const pendingRows = displayList.value.filter(row => row.rapor && row.periode_sudah_diisi && (row.rapor.status === 'pending' || row.rapor.status === 'draft'))
  if (allSelected.value) {
    selectedRapor.value = []
  } else {
    selectedRapor.value = pendingRows.map(row => row.rapor.id)
  }
}

/** Buka PDF rapor belajar di tab baru (sama konten dengan cetak rapor Wali Kelas). */
const openPreviewRaporBelajar = async (row) => {
  if (!row.siswa?.id || !row.tahun_ajaran?.id) {
    toast.error('Data siswa atau tahun ajaran tidak lengkap')
    return
  }
  // semester dari tahun_ajaran (1 atau 2), jenis (sts/sas) = periode tengah/akhir semester
  const semester = String(row.tahun_ajaran.semester ?? '1')
  const jenis = filters.jenis || 'sts'
  try {
    const params = new URLSearchParams({
      tahun_ajaran_id: row.tahun_ajaran.id,
      semester,
      jenis
    })
    const res = await axios.get(
      `/kepala-sekolah/rapor-approval/preview-rapor-belajar/${row.siswa.id}?${params}`,
      { responseType: 'blob' }
    )
    if (res.status !== 200 || (res.data?.type && !res.data.type.includes('pdf'))) {
      const text = await res.data.text()
      try {
        const j = JSON.parse(text)
        toast.error(j?.message || 'Gagal menghasilkan PDF')
      } catch (_) {
        toast.error('Respons bukan PDF. Gagal menghasilkan rapor.')
      }
      return
    }
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    window.open(url, '_blank', 'noopener,noreferrer')
    toast.success('Rapor dibuka di tab baru')
  } catch (error) {
    const msg = error.response?.data?.message || error.response?.status === 403
      ? 'Tidak dapat menampilkan rapor'
      : 'Gagal membuka rapor'
    toast.error(typeof msg === 'string' ? msg : 'Gagal membuka rapor')
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const j = JSON.parse(text)
        if (j?.message) toast.error(j.message)
      } catch (_) {}
    }
  }
}

const viewRapor = async (rapor) => {
  try {
    const response = await axios.get(`/kepala-sekolah/rapor-approval/${rapor.id}`)
    selectedRaporDetail.value = response.data.data
    showRaporDetail.value = true
  } catch (error) {
    toast.error('Gagal mengambil detail rapor')
    console.error(error)
  }
}

const approveRapor = (rapor) => {
  pendingApproveBySiswa.value = null
  selectedRaporForAction.value = rapor
  showApprovalConfirm.value = true
}

const approveRaporBySiswa = (row) => {
  selectedRaporForAction.value = null
  pendingApproveBySiswa.value = row
  showApprovalConfirm.value = true
}

const confirmApproval = async () => {
  try {
    processing.value = true

    if (pendingApproveBySiswa.value) {
      await axios.post('/kepala-sekolah/rapor-approval/approve-siswa', {
        siswa_id: pendingApproveBySiswa.value.siswa?.id,
        tahun_ajaran_id: pendingApproveBySiswa.value.tahun_ajaran?.id
      })
      toast.success('Rapor berhasil disetujui')
      pendingApproveBySiswa.value = null
    } else if (selectedRapor.value.length > 1) {
      await axios.post('/kepala-sekolah/rapor-approval/bulk-approve', {
        rapor_ids: selectedRapor.value
      })
      toast.success(`${selectedRapor.value.length} rapor berhasil disetujui`)
      selectedRapor.value = []
    } else {
      await axios.post(`/kepala-sekolah/rapor-approval/${selectedRaporForAction.value.id}/approve`)
      toast.success('Rapor berhasil disetujui')
    }

    showApprovalConfirm.value = false
    showRaporDetail.value = false
    await fetchRapor()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal menyetujui rapor')
    console.error(error)
  } finally {
    processing.value = false
  }
}

const rejectRapor = (rapor) => {
  selectedRaporForAction.value = rapor
  rejectionReason.value = ''
  showRejectionModal.value = true
}

const unapproveRapor = async (rapor) => {
  if (!confirm(`Apakah Anda yakin ingin mengubah rapor ${rapor.siswa?.nama_lengkap} menjadi belum disetujui?`)) return
  try {
    processing.value = true
    await axios.post(`/kepala-sekolah/rapor-approval/${rapor.id}/unapprove`)
    toast.success('Rapor berhasil diubah ke belum disetujui')
    await fetchRapor()
  } catch (error) {
    toast.error(error.response?.data?.message || 'Gagal mengubah status rapor')
    console.error(error)
  } finally {
    processing.value = false
  }
}

const confirmRejection = async () => {
  try {
    processing.value = true
    
    await axios.post(`/kepala-sekolah/rapor-approval/${selectedRaporForAction.value.id}/reject`, {
      reason: rejectionReason.value
    })
    
    toast.success('Rapor berhasil ditolak')
    showRejectionModal.value = false
    showRaporDetail.value = false
    await fetchRapor()
  } catch (error) {
    toast.error('Gagal menolak rapor')
    console.error(error)
  } finally {
    processing.value = false
  }
}

const bulkApprove = () => {
  if (selectedRapor.value.length === 0) return
  showApprovalConfirm.value = true
}

const getStatusBadge = (status) => {
  if (status === 'approved' || status === 'published') return 'bg-green-100 text-green-800'
  if (status === 'pending' || status === 'draft') return 'bg-yellow-100 text-yellow-800'
  return 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
  if (status === 'approved' || status === 'published') return 'Setujui'
  if (status === 'pending' || status === 'draft') return 'Belum'
  return status
}

const getPredicate = (nilai) => {
  if (!nilai) return '-'
  if (nilai >= 90) return 'A'
  if (nilai >= 80) return 'B'
  if (nilai >= 70) return 'C'
  if (nilai >= 60) return 'D'
  return 'E'
}

const getPredicateColor = (nilai) => {
  const predicate = getPredicate(nilai)
  const colors = {
    A: 'bg-green-100 text-green-800',
    B: 'bg-blue-100 text-blue-800',
    C: 'bg-yellow-100 text-yellow-800',
    D: 'bg-orange-100 text-orange-800',
    E: 'bg-red-100 text-red-800'
  }
  return colors[predicate] || 'bg-gray-100 text-gray-800'
}

// Lifecycle: hanya load opsi filter; daftar rapor di-fetch setelah user pilih Tahun Ajaran, Kelas, dan Periode
onMounted(async () => {
  await fetchTahunAjaran()
  await fetchKelas()
  loading.value = false
})
</script>

