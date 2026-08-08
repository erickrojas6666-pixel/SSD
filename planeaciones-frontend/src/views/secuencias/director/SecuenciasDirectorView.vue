<template>
  <AppShell>
    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num"><ShieldCheck :size="20" /></div>
        <div>
          <h2>Validación de Secuencias</h2>
          <p>Secuencias de tu carrera, ya revisadas, esperando tu decisión final.</p>
        </div>
      </div>

      <div v-if="errorMsg" class="alert a-danger mb4">{{ errorMsg }}</div>
      <div v-if="successMsg" class="alert a-success mb4">{{ successMsg }}</div>

      <div class="card">
        <div class="cp" style="overflow-x:auto">
          <table class="tt">
            <thead>
              <tr><th>Asignatura</th><th>Periodo</th><th>Docente(s)</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-if="cargando"><td colspan="4" class="sz-sm" style="text-align:center;color:var(--text-300)">Cargando…</td></tr>
              <tr v-else-if="secuencias.length === 0"><td colspan="4" class="sz-sm" style="text-align:center;color:var(--text-300)">No hay secuencias pendientes de validación.</td></tr>
              <tr v-for="s in secuencias" :key="s.id">
                <td>{{ s.asignatura?.nombre }}</td>
                <td>{{ s.periodo }}</td>
                <td>{{ s.autores.map(a => a.nombre_completo).join(', ') }}</td>
                <td><IconButton title="Ver resumen" @click="secuenciaSeleccionada = s.id"><Eye :size="16" /></IconButton></td>
              </tr>
            </tbody>
          </table>
        </div>
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
const cargando = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const secuenciaSeleccionada = ref(null)

onMounted(cargar)

async function cargar() {
  cargando.value = true
  try {
    const { data } = await api.get('/director/secuencias')
    secuencias.value = data
  } catch (e) {
    errorMsg.value = 'No se pudo cargar la cola de validación.'
  } finally {
    cargando.value = false
  }
}

function onResuelta(mensaje) {
  secuenciaSeleccionada.value = null
  successMsg.value = mensaje
  cargar()
}
</script>
