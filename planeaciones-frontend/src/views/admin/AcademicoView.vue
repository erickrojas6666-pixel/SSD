<template>
  <AppShell>
    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num"><GraduationCap :size="20" /></div>
        <div>
          <h2>Carreras y Especialidades</h2>
          <p>Consulta, crea, edita y activa o desactiva los catálogos académicos.</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button class="tab" :class="{ active: tab === 'carreras' }" @click="tab = 'carreras'">
          <GraduationCap :size="16" /> Carreras
        </button>
        <button class="tab" :class="{ active: tab === 'especialidades' }" @click="tab = 'especialidades'">
          <BookOpen :size="16" /> Especialidades
        </button>
        <button class="tab" :class="{ active: tab === 'asignaturas' }" @click="tab = 'asignaturas'">
          <FileText :size="16" /> Asignaturas
        </button>
      </div>

      <CarrerasPanel v-if="tab === 'carreras'" />
      <EspecialidadesPanel v-else-if="tab === 'especialidades'" />
      <AsignaturasPanel v-else />
    </div>
  </AppShell>
</template>

<script setup>
import { ref } from 'vue'
import { GraduationCap, BookOpen, FileText } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
import CarrerasPanel from './CarrerasPanel.vue'
import EspecialidadesPanel from './EspecialidadesPanel.vue'
import AsignaturasPanel from './AsignaturasPanel.vue'

const tab = ref('carreras')
</script>

<style scoped>
.tabs {
  display: flex;
  gap: var(--s5);
  border-bottom: 1px solid var(--border);
  margin-bottom: var(--s5);
}
.tab {
  display: flex;
  align-items: center;
  gap: var(--s2);
  padding: var(--s3) var(--s1);
  border: none;
  background: none;
  cursor: pointer;
  font-family: var(--font);
  font-size: var(--p-sm);
  font-weight: 600;
  color: var(--text-300);
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  transition: color var(--tf) var(--ease), border-color var(--tf) var(--ease);
}
.tab:hover {
  color: var(--text-700);
}
.tab.active {
  color: var(--uth-verde);
  border-color: var(--uth-verde);
}
</style>
