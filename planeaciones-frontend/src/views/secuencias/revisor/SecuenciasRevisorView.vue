<template>
  <AppShell>
    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num"><ClipboardList :size="20" /></div>
        <div>
          <h2>Cola de Revisión</h2>
          <p>Secuencias enviadas por los docentes, pendientes de tu revisión.</p>
        </div>
      </div>

      <div v-if="errorMsg" class="alert a-danger mb4">{{ errorMsg }}</div>

      <div class="card">
        <div class="cp" style="overflow-x:auto">
          <table class="tt">
            <thead>
              <tr><th>Asignatura</th><th>Carrera</th><th>Periodo</th><th>Docente(s)</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-if="cargando"><td colspan="5" class="sz-sm" style="text-align:center;color:var(--text-300)">Cargando…</td></tr>
              <tr v-else-if="secuencias.length === 0"><td colspan="5" class="sz-sm" style="text-align:center;color:var(--text-300)">No hay secuencias pendientes de revisión.</td></tr>
              <tr v-for="s in secuencias" :key="s.id">
                <td>{{ s.asignatura?.nombre }}</td>
                <td>{{ s.carrera?.nombre }}</td>
                <td>{{ s.periodo }}</td>
                <td>{{ s.autores.map(a => a.nombre_completo).join(', ') }}</td>
                <td><IconButton title="Revisar" @click="abrirEditor(s.id)"><ArrowRight :size="16" /></IconButton></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ClipboardList, ArrowRight } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
import IconButton from '@/components/IconButton.vue'
import api from '@/services/api'
import router from '@/router'

const secuencias = ref([])
const cargando = ref(false)
const errorMsg = ref('')

onMounted(cargar)

async function cargar() {
  cargando.value = true
  try {
    const { data } = await api.get('/revisor/secuencias')
    secuencias.value = data
  } catch (e) {
    errorMsg.value = 'No se pudo cargar la cola de revisión.'
  } finally {
    cargando.value = false
  }
}

function abrirEditor(id) {
  router.push({ name: 'secuencia-editor', params: { id } })
}
</script>
