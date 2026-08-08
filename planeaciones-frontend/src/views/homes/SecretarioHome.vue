<template>
  <AppShell>
    <div class="hero">
      <div class="hero-tag"><FolderKanban :size="14" /> Secretario</div>
      <h2>Panel del Secretario</h2>
      <p>Apoyo administrativo en el seguimiento de planeaciones.</p>
    </div>

    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num">1</div>
        <div>
          <h2>Próximos pasos</h2>
          <p>Este panel se irá completando con los módulos correspondientes a tu rol.</p>
        </div>
      </div>
      <div class="card">
        <div class="cp">
          <p class="sz-md">Sesión iniciada correctamente. Aquí se mostrará el seguimiento administrativo.</p>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { FolderKanban } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
</script>
