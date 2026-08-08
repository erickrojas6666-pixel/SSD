<template>
  <AppShell>
    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num"><FileEdit :size="20" /></div>
        <div>
          <h2>Mis Secuencias Didácticas</h2>
          <p>Borradores en desarrollo e historial de secuencias validadas.</p>
        </div>
      </div>

      <div class="tabs">
        <button class="tab" :class="{ active: tab === 'borradores' }" @click="tab = 'borradores'">
          <FileEdit :size="16" /> Borradores
        </button>
        <button class="tab" :class="{ active: tab === 'historial' }" @click="tab = 'historial'">
          <History :size="16" /> Historial
        </button>
      </div>

      <BorradoresPanel v-if="tab === 'borradores'" />
      <HistorialPanel v-else />
    </div>
  </AppShell>
</template>

<script setup>
import { ref } from 'vue'
import { FileEdit, History } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
import BorradoresPanel from './BorradoresPanel.vue'
import HistorialPanel from './HistorialPanel.vue'

const tab = ref('borradores')
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
.tab:hover { color: var(--text-700); }
.tab.active { color: var(--uth-verde); border-color: var(--uth-verde); }
</style>
