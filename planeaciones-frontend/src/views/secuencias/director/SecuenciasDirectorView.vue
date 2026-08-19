<template>
  <AppShell>
    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num"><ShieldCheck :size="20" /></div>
        <div>
          <h2>Secuencias de tu carrera</h2>
          <p v-if="periodo">Todas las secuencias del periodo <strong>{{ periodo }}</strong>.</p>
          <p v-else>Todas las secuencias de tu carrera.</p>
        </div>
      </div>

      <div v-if="errorMsg" class="alert a-danger mb4">{{ errorMsg }}</div>
      <div v-if="successMsg" class="alert a-success mb4">{{ successMsg }}</div>

      <div class="card">
        <div class="cp" style="overflow-x:auto">
          <table class="tt">
            <thead>
              <tr><th>Asignatura</th><th>Periodo</th><th>Docente(s)</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-if="cargando"><td colspan="5" class="sz-sm" style="text-align:center;color:var(--text-300)">Cargando…</td></tr>
              <tr v-else-if="secuencias.length === 0"><td colspan="5" class="sz-sm" style="text-align:center;color:var(--text-300)">No hay secuencias registradas en este periodo.</td></tr>
              <tr
                v-for="s in secuencias"
                :key="s.id"
                class="row-open"
                title="Doble clic para abrir"
                @dblclick="secuenciaSeleccionada = s.id"
              >
                <td>{{ s.asignatura?.nombre }}</td>
                <td>{{ s.periodo }}</td>
                <td>{{ s.autores.map(a => a.nombre_completo).join(', ') }}</td>
                <td><span :class="['estado-badge', badgeEstado(s.estado)]">{{ etiquetaEstado(s.estado) }}</span></td>
                <td><IconButton title="Ver resumen" @click.stop="secuenciaSeleccionada = s.id"><Eye :size="16" /></IconButton></td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="secuencias.length" class="tbl-hint">Tip: doble clic en una fila para abrir el resumen. Solo las secuencias en estado "En validación" se pueden validar o rechazar.</p>
      </div>
    </div>

    <DirectorResumenModal
      v-if="secuenciaSeleccionada"
      :secuencia-id="secuenciaSeleccionada"
      @close="secuenciaSeleccionada = null"
      @resuelta="onResuelta"
    />
  </AppShell>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ShieldCheck, Eye } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
import IconButton from '@/components/IconButton.vue'
import DirectorResumenModal from '@/views/secuencias/DirectorResumenModal.vue'
import api from '@/services/api'

const secuencias = ref([])
const periodo = ref('')
const cargando = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const secuenciaSeleccionada = ref(null)

onMounted(cargar)

async function cargar() {
  cargando.value = true
  try {
    const { data } = await api.get('/director/secuencias')
    secuencias.value = data.secuencias
    periodo.value = data.periodo
  } catch (e) {
    errorMsg.value = 'No se pudieron cargar las secuencias de tu carrera.'
  } finally {
    cargando.value = false
  }
}

function onResuelta(mensaje) {
  secuenciaSeleccionada.value = null
  successMsg.value = mensaje
  cargar()
}

function badgeEstado(estado) {
  return { borrador: 'estado-En_desarrollo', en_revision: 'estado-En_revision', en_proceso_validacion: 'estado-En_proceso_validacion', validada: 'estado-Validada', rechazada: 'estado-Rechazada' }[estado] ?? 'estado-En_desarrollo'
}
function etiquetaEstado(estado) {
  return { borrador: 'Borrador', en_revision: 'En revisión', en_proceso_validacion: 'En validación', validada: 'Validada', rechazada: 'Rechazada' }[estado] ?? estado
}
</script>
