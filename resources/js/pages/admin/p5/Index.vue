<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <DataTable
        title="Data P5 (Projek Penguatan Profil Pelajar Pancasila)"
        description="Kelola data projek P5"
        :data="p5"
        :columns="columns"
        :loading="loading"
        empty-message="Belum ada data P5"
        empty-description="Mulai dengan menambahkan data P5 baru."
        :searchable="true"
        @search="handleSearch"
      >
        <template #actions>
          <button @click="showForm = true" class="btn btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Tambah P5
          </button>
        </template>

        <template #cell-judul="{ item }">
          <div class="text-sm">
            <div class="font-medium text-gray-900">{{ item.judul || item.tema }}</div>
            <div class="text-gray-500 text-xs line-clamp-2 mt-1">{{ item.deskripsi }}</div>
          </div>
        </template>

        <template #cell-kelompok_info="{ item }">
          <router-link
            :to="{ path: '/admin/p5/kelompok', query: { p5_id: item.id } }"
            class="text-sm text-blue-600 hover:text-blue-900"
          >
            <span v-if="item.kelompok && item.kelompok.length">{{ item.kelompok.length }} kelompok</span>
            <span v-else class="text-gray-500">Belum ada kelompok</span>
          </router-link>
        </template>

        <template #row-actions="{ item }">
          <div class="flex items-center justify-center gap-2">
            <button @click="editP5(item)" class="text-blue-600 hover:text-blue-900" title="Edit">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </button>
            <button @click="deleteP5(item)" class="text-red-600 hover:text-red-900" title="Hapus">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </button>
          </div>
        </template>
      </DataTable>

      <!-- Form Modal (Tambah/Edit P5 - simpan langsung) -->
      <Modal v-model:show="showForm" :title="modalTitle" size="lg">
        <form @submit.prevent="submitForm" id="p5-form" class="space-y-4">
          <FormField
            v-model="form.dimensi"
            type="select"
            label="Dimensi"
            placeholder="Pilih dimensi"
            :options="dimensiOptions"
            option-value="value"
            option-label="label"
            required
            :error="errors.dimensi"
            @update:model-value="onDimensiChange"
          />
          <FormField
            v-model="form.tema"
            type="select"
            label="Tema"
            placeholder="Pilih tema"
            :options="temaOptions"
            option-value="value"
            option-label="label"
            required
            :error="errors.tema"
          />
          <div class="space-y-4">
            <label class="block text-sm font-medium text-gray-700">Elemen & Sub Elemen</label>
            <p class="text-xs text-gray-500">Bisa memilih lebih dari satu pasang elemen dan sub elemen.</p>
            <div v-for="(row, index) in elemenSubForm" :key="index" class="border rounded-lg p-4 bg-gray-50/50 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">Pasangan {{ index + 1 }}</span>
                <button v-if="elemenSubForm.length > 1" type="button" @click="removeElemenSubRow(index)" class="text-red-600 hover:text-red-800 text-sm">
                  Hapus
                </button>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                  v-model="row.elemen"
                  type="select"
                  label="Elemen"
                  placeholder="Pilih elemen"
                  :options="elemenOptions"
                  option-value="value"
                  option-label="label"
                  @update:model-value="() => onElemenSubElemenChange(index)"
                />
                <FormField
                  v-model="row.sub_elemen"
                  type="select"
                  label="Sub Elemen"
                  placeholder="Pilih sub elemen"
                  :options="subElemenOptionsForRow(index)"
                  option-value="value"
                  option-label="label"
                />
              </div>
            </div>
            <button type="button" @click="addElemenSubRow" class="btn btn-secondary w-full sm:w-auto">
              <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              Tambah elemen dan sub elemen
            </button>
            <p v-if="errors.elemen_sub" class="text-sm text-red-600">{{ errors.elemen_sub }}</p>
          </div>
          <FormField
            v-model="form.judul"
            type="text"
            label="Judul"
            placeholder="Masukkan judul projek"
            required
            :error="errors.judul"
          />
          <FormField
            v-model="form.deskripsi"
            type="textarea"
            label="Deskripsi"
            placeholder="Masukkan deskripsi projek"
            required
            :error="errors.deskripsi"
            rows="4"
          />
          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeForm" class="btn btn-secondary">Batal</button>
            <button type="submit" :disabled="submitting" class="btn btn-primary">
              {{ submitting ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </Modal>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import DataTable from '../../../components/ui/DataTable.vue'
import Modal from '../../../components/ui/Modal.vue'
import FormField from '../../../components/ui/FormField.vue'

const toast = useToast()

const DIMENSI_LIST = [
  'Beriman, Bertakwa kepada Tuhan Yang Maha Esa, dan Berakhlak Mulia',
  'Berkebinekaan Global',
  'Bergotong Royong',
  'Mandiri',
  'Bernalar Kritis',
  'Kreatif'
]

const TEMA_LIST = [
  'Gaya Hidup Berkelanjutan',
  'Kearifan Lokal',
  'Bhinneka Tunggal Ika',
  'Bangunlah Jiwa dan Raganya',
  'Suara Demokrasi',
  'Rekayasa dan Teknologi',
  'Kewirausahaan',
  'Kebekerjaan'
]

const ELEMEN_BY_DIMENSI = {
  'Beriman, Bertakwa kepada Tuhan Yang Maha Esa, dan Berakhlak Mulia': [
    'Akhlak beragama',
    'Akhlak pribadi',
    'Akhlak kepada manusia',
    'Akhlak kepada alam',
    'Akhlak bernegara'
  ],
  'Berkebinekaan Global': [
    'Mengenal dan menghargai budaya',
    'Kemampuan komunikasi interkultural',
    'Refleksi dan tanggung jawab terhadap pengamalan kebinekaan',
    'Berkeadilan sosial'
  ],
  'Bergotong Royong': ['Kolaborasi', 'Kepedulian', 'Berbagi'],
  Mandiri: [
    'Pemahaman diri dan situasi yang dihadapi',
    'Regulasi diri'
  ],
  'Bernalar Kritis': [
    'Memperoleh dan memproses informasi dan gagasan',
    'Menganalisis dan mengevaluasi penalaran dan prosedurnya',
    'Refleksi pemikiran dan proses berpikir'
  ],
  Kreatif: [
    'Menghasilkan gagasan yang orisinal',
    'Menghasilkan karya dan tindakan yang orisinal',
    'Memiliki keluwesan berpikir dalam mencari alternatif solusi permasalahan'
  ]
}

const SUB_ELEMEN_BY_ELEMEN = {
  'Akhlak beragama': [
    'Mengenal dan Mencintai Tuhan Yang Maha Esa',
    'Pemahaman Agama dan Kepercayaan',
    'Pelaksanaan Ritual Ibadah'
  ],
  'Akhlak pribadi': [
    'Integritas',
    'Merawat Diri secara Fisik, Mental, dan Spiritual'
  ],
  'Akhlak kepada manusia': [
    'Mengutamakan persamaan dengan orang lain dan menghargai perbedaan',
    'Berempati kepada orang lain'
  ],
  'Akhlak kepada alam': [
    'Memahami Keterhubungan Ekosistem Bumi',
    'Menjaga Lingkungan Alam Sekitar'
  ],
  'Akhlak bernegara': [
    'Melaksanakan Hak dan Kewajiban sebagai Warga Negara Indonesia'
  ],
  'Mengenal dan menghargai budaya': [
    'Mendalami budaya dan identitas budaya',
    'Mengeksplorasi dan membandingkan pengetahuan budaya, kepercayaan, serta praktiknya',
    'Menumbuhkan rasa menghormati terhadap keanekaragaman budaya'
  ],
  'Kemampuan komunikasi interkultural': [
    'Berkomunikasi antar budaya',
    'Mempertimbangkan dan menumbuhkan berbagai perspektif'
  ],
  'Refleksi dan tanggung jawab terhadap pengamalan kebinekaan': [
    'Refleksi terhadap pengalaman kebinekaan',
    'Menghilangkan stereotip dan prasangka',
    'Menyelaraskan perbedaan budaya'
  ],
  'Berkeadilan sosial': [
    'Aktif membangun masyarakat yang inklusif, adil, dan berkelanjutan',
    'Berpartisipasi dalam proses pengambilan keputusan bersama',
    'Memahami peran individu dalam demokrasi'
  ],
  Kolaborasi: [
    'Kerja sama',
    'Komunikasi untuk mencapai tujuan bersama',
    'Saling ketergantungan positif',
    'Koordinasi Sosial'
  ],
  Kepedulian: ['Tanggap terhadap lingkungan Sosial'],
  Berbagi: ['Persepsi sosial'],
  'Pemahaman diri dan situasi yang dihadapi': [
    'Mengenali kualitas dan minat diri serta tantangan yang dihadapi',
    'Mengembangkan refleksi diri'
  ],
  'Regulasi diri': [
    'Regulasi emosi',
    'Penetapan tujuan belajar, prestasi, dan pengembangan diri serta rencana strategis untuk mencapainya',
    'Menunjukkan inisiatif dan bekerja secara mandiri',
    'Mengembangkan pengendalian dan disiplin diri',
    'Percaya diri, tangguh (resilient), dan adaptif'
  ],
  'Memperoleh dan memproses informasi dan gagasan': [
    'Mengajukan pertanyaan',
    'Mengidentifikasi, mengklarifikasi, dan mengolah informasi dan gagasan'
  ],
  'Menganalisis dan mengevaluasi penalaran dan prosedurnya': [
    'Menganalisis dan mengevaluasi penalaran dan prosedurnya'
  ],
  'Refleksi pemikiran dan proses berpikir': [
    'Merefleksi dan mengevaluasi pemikirannya sendiri'
  ],
  'Menghasilkan gagasan yang orisinal': [
    'Menghasilkan gagasan yang orisinal'
  ],
  'Menghasilkan karya dan tindakan yang orisinal': [
    'Menghasilkan karya dan tindakan yang orisinal'
  ],
  'Memiliki keluwesan berpikir dalam mencari alternatif solusi permasalahan': [
    'Memiliki keluwesan berpikir dalam mencari alternatif solusi permasalahan'
  ]
}

const p5 = ref([])
const loading = ref(false)
const showForm = ref(false)
const isEditing = ref(false)
const submitting = ref(false)
const errors = ref({})

const filters = ref({
  search: ''
})

const form = ref({
  id: null,
  dimensi: '',
  tema: '',
  judul: '',
  deskripsi: ''
})

const elemenSubForm = ref([{ elemen: '', sub_elemen: '', deskripsi_tujuan: '' }])

const modalTitle = computed(() => (isEditing.value ? 'Edit P5' : 'Tambah P5'))

const dimensiOptions = computed(() =>
  DIMENSI_LIST.map((d) => ({ value: d, label: d }))
)
const temaOptions = computed(() =>
  TEMA_LIST.map((t) => ({ value: t, label: t }))
)
const elemenOptions = computed(() => {
  const dimensi = form.value.dimensi
  if (!dimensi) return []
  const list = ELEMEN_BY_DIMENSI[dimensi] || []
  return list.map((e) => ({ value: e, label: e }))
})

function subElemenOptionsForRow(rowIndex) {
  const elemen = elemenSubForm.value[rowIndex]?.elemen
  if (!elemen) return []
  const list = SUB_ELEMEN_BY_ELEMEN[elemen] || []
  return list.map((s) => ({ value: s, label: s }))
}

const columns = [
  { key: 'judul', label: 'Judul' },
  { key: 'kelompok_info', label: 'Kelompok' }
]

function onDimensiChange() {
  elemenSubForm.value = [{ elemen: '', sub_elemen: '', deskripsi_tujuan: '' }]
}

function onElemenSubElemenChange(rowIndex) {
  elemenSubForm.value[rowIndex].sub_elemen = ''
}

function addElemenSubRow() {
  elemenSubForm.value.push({ elemen: '', sub_elemen: '', deskripsi_tujuan: '' })
}

function removeElemenSubRow(index) {
  elemenSubForm.value.splice(index, 1)
}

const fetchP5 = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.search) params.append('search', filters.value.search)

    const response = await axios.get(`/admin/p5?${params.toString()}`)
    if (response.data.data && Array.isArray(response.data.data)) {
      p5.value = response.data.data
    } else if (Array.isArray(response.data)) {
      p5.value = response.data
    } else {
      p5.value = []
    }
  } catch (error) {
    console.error('Error fetching P5:', error)
    toast.error('Gagal mengambil data P5')
  } finally {
    loading.value = false
  }
}

const handleSearch = (searchTerm) => {
  filters.value.search = searchTerm
  fetchP5()
}

const editP5 = (item) => {
  isEditing.value = true
  form.value = {
    id: item.id,
    dimensi: item.dimensi || '',
    tema: item.tema || '',
    judul: item.judul || '',
    deskripsi: item.deskripsi || ''
  }
  const es = item.elemen_sub
  elemenSubForm.value = Array.isArray(es) && es.length > 0
    ? es.map((x) => ({ elemen: x.elemen || '', sub_elemen: x.sub_elemen || '', deskripsi_tujuan: x.deskripsi_tujuan || '' }))
    : item.elemen || item.sub_elemen
      ? [{ elemen: item.elemen || '', sub_elemen: item.sub_elemen || '', deskripsi_tujuan: item.deskripsi_tujuan || '' }]
      : [{ elemen: '', sub_elemen: '', deskripsi_tujuan: '' }]
  showForm.value = true
}

const deleteP5 = async (item) => {
  const label = item.judul || item.tema
  if (!confirm(`Apakah Anda yakin ingin menghapus projek P5 "${label}"?`)) {
    return
  }

  try {
    await axios.delete(`/admin/p5/${item.id}`)
    toast.success('Projek P5 berhasil dihapus')
    fetchP5()
  } catch (error) {
    console.error('Error deleting P5:', error)
    toast.error(error.response?.data?.message || 'Gagal menghapus projek P5')
  }
}

const closeForm = () => {
  showForm.value = false
  isEditing.value = false
  errors.value = {}
  form.value = {
    id: null,
    dimensi: '',
    tema: '',
    judul: '',
    deskripsi: ''
  }
  elemenSubForm.value = [{ elemen: '', sub_elemen: '', deskripsi_tujuan: '' }]
}

const submitForm = async () => {
  errors.value = {}
  const validPairs = elemenSubForm.value.filter((r) => r.elemen && r.sub_elemen)
  if (!form.value.dimensi || !form.value.tema || !form.value.judul?.trim() || !form.value.deskripsi?.trim()) {
    if (!form.value.dimensi) errors.value.dimensi = 'Dimensi wajib diisi'
    if (!form.value.tema) errors.value.tema = 'Tema wajib diisi'
    if (!form.value.judul?.trim()) errors.value.judul = 'Judul wajib diisi'
    if (!form.value.deskripsi?.trim()) errors.value.deskripsi = 'Deskripsi wajib diisi'
    return
  }
  if (validPairs.length === 0) {
    errors.value.elemen_sub = 'Minimal satu pasangan elemen dan sub elemen wajib diisi'
    return
  }
  submitting.value = true
  try {
    const payload = {
      dimensi: form.value.dimensi,
      tema: form.value.tema,
      judul: form.value.judul,
      deskripsi: form.value.deskripsi,
      elemen_sub: validPairs.map((r) => ({
        elemen: r.elemen,
        sub_elemen: r.sub_elemen,
        deskripsi_tujuan: r.deskripsi_tujuan?.trim() || null
      }))
    }
    if (isEditing.value) {
      await axios.put(`/admin/p5/${form.value.id}`, payload)
      toast.success('Projek P5 berhasil diperbarui')
    } else {
      await axios.post('/admin/p5', payload)
      toast.success('Projek P5 berhasil ditambahkan')
    }
    closeForm()
    fetchP5()
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.error(error.response?.data?.message || 'Gagal menyimpan projek P5')
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchP5()
})
</script>
