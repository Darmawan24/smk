<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit' : 'Tambah' }} Siswa</h1>
      
      <div class="mt-6 bg-white shadow rounded-lg p-6">
        <p class="text-gray-500">Form siswa dalam pengembangan...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const isEdit = computed(() => !!route.params.id)
</script>