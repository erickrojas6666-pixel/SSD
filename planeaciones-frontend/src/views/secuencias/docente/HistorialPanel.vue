<template>
  <div>
    <div class="card mb4">
      <div class="cp">
        <p class="sz-sm" style="color:var(--text-300)">Secuencias validadas. Puedes usar cualquiera como base para una
          nueva.</p>
      </div>
    </div>

    <div v-if="errorMsg" class="alert a-danger mb4">{{ errorMsg }}</div>
    <div v-if="successMsg" class="alert a-success mb4">{{ successMsg }}</div>

    <div class="card">
      <div class="cp" style="overflow-x:auto">
        <table class="tt">
          <thead>
            <tr>
              <th>Asignatura</th>
              <th>Periodo</th>
              <th>Fecha de validación</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="cargando">
              <td colspan="4" class="sz-sm" style="text-align:center;color:var(--text-300)">Cargando…</td>
            </tr>
            <tr v-else-if="historial.length === 0">
              <td colspan="4" class="sz-sm" style="text-align:center;color:var(--text-300)">Aún no tienes secuencias
                validadas.</td>
            </tr>
            <tr v-for="s in historial" :key="s.id" class="row-open" title="Doble clic para abrir"
              @dblclick="abrirEditor(s.id)">
              <td>{{ s.asignatura?.nombre }}</td>
              <td>{{ s.periodo }}</td>
              <td>{{ formatearFecha(s.fecha_validacion) }}</td>
              <td>
                <div class="flex ic g2u">
                  <IconButton title="Ver" @click.stop="abrirEditor(s.id)">
                    <Eye :size="16" />
                  </IconButton>
                  <IconButton title="Descargar documento de la planeación" :disabled="descargandoId === s.id"
                    @click.stop="descargarPlaneacion(s)">
                    <Download :size="16" />
                  </IconButton>
                  <IconButton title="Duplicar como nueva" variant="primary" @click.stop="secuenciaADuplicar = s">
                    <Copy :size="16" />
                  </IconButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p v-if="historial.length" class="tbl-hint">Tip: doble clic en una fila para abrir la secuencia.</p>
    </div>

    <DuplicarSecuenciaModal v-if="secuenciaADuplicar" :secuencia="secuenciaADuplicar" @close="secuenciaADuplicar = null"
      @duplicada="onDuplicada" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Eye, Copy, Download } from 'lucide-vue-next'
import IconButton from '@/components/IconButton.vue'
import DuplicarSecuenciaModal from '@/views/secuencias/DuplicarSecuenciaModal.vue'
import api from '@/services/api'
import router from '@/router'

const historial = ref([])
const cargando = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const secuenciaADuplicar = ref(null)
const descargandoId = ref(null)

onMounted(cargar)

async function cargar() {
  cargando.value = true
  try {
    const { data } = await api.get('/docente/secuencias', { params: { estado: 'validada' } })
    historial.value = data
  } catch (e) {
    errorMsg.value = 'No se pudo cargar tu historial.'
  } finally {
    cargando.value = false
  }
}

function abrirEditor(id) {
  router.push({ name: 'secuencia-editor', params: { id } })
}

async function descargarPlaneacion(s) {
  descargandoId.value = s.id
  errorMsg.value = ''
  try {
    const { data } = await api.get(`/secuencias/${s.id}/documento-planeacion`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
    const enlace = document.createElement('a')
    enlace.href = url
    enlace.download = `planeacion-${s.asignatura?.nombre || 'secuencia'}.pdf`
    document.body.appendChild(enlace)
    enlace.click()
    enlace.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    errorMsg.value = 'No se pudo descargar el documento de la planeación.'
    console.error(e)
  } finally {
    descargandoId.value = null
  }
}

function onDuplicada(secuencia) {
  secuenciaADuplicar.value = null
  successMsg.value = 'Secuencia duplicada correctamente.'
  router.push({ name: 'secuencia-editor', params: { id: secuencia.id } })
}

function formatearFecha(fecha) {
  return fecha ? new Date(fecha).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'
}
</script>