<template>
  <div>
    <div class="card mb4">
      <div class="cp flex jb ic">
        <p class="sz-sm" style="color:var(--text-300)">Secuencias en desarrollo que aún puedes editar.</p>
        <button class="btn btn-primary" @click="modalCrearAbierto = true">
          <Plus :size="16" style="margin-right:4px" /> Nueva secuencia
        </button>
      </div>
    </div>

    <div v-if="errorMsg" class="alert a-danger mb4">{{ errorMsg }}</div>
    <div v-if="successMsg" class="alert a-success mb4">{{ successMsg }}</div>

    <div class="card">
      <div class="cp" style="overflow-x:auto">
        <table class="tt">
          <thead>
            <tr><th>Asignatura</th><th>Periodo</th><th>Grupos</th><th>Estado</th><th></th></tr>
          </thead>
          <tbody>
            <tr v-if="cargando"><td colspan="5" class="sz-sm" style="text-align:center;color:var(--text-300)">Cargando…</td></tr>
            <tr v-else-if="borradores.length === 0"><td colspan="5" class="sz-sm" style="text-align:center;color:var(--text-300)">No tienes secuencias en borrador.</td></tr>
            <tr v-for="s in borradores" :key="s.id">
              <td>{{ s.asignatura?.nombre }}</td>
              <td>{{ s.periodo }}</td>
              <td><span v-for="g in s.grupos" :key="g.id" class="badge b-azul" style="margin-right:4px">{{ g.grupo }}</span></td>
              <td><span :class="['badge', badgeEstado(s.estado)]">{{ etiquetaEstado(s.estado) }}</span></td>
              <td>
                <div class="flex ic g2u">
                  <IconButton title="Abrir" @click="abrirEditor(s.id)"><ArrowRight :size="16" /></IconButton>
                  <IconButton title="Eliminar" variant="danger" :disabled="eliminandoId === s.id" @click="eliminar(s)"><Trash2 :size="16" /></IconButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <NuevaSecuenciaModal v-if="modalCrearAbierto" @close="modalCrearAbierto = false" @creada="onSecuenciaCreada" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, ArrowRight, Trash2 } from 'lucide-vue-next'
import IconButton from '@/components/IconButton.vue'
import NuevaSecuenciaModal from '@/views/secuencias/NuevaSecuenciaModal.vue'
import api from '@/services/api'
import router from '@/router'

const borradores = ref([])
const cargando = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const modalCrearAbierto = ref(false)
const eliminandoId = ref(null)

onMounted(cargar)

async function cargar() {
  cargando.value = true
  try {
    const { data } = await api.get('/docente/secuencias', { params: { estado: 'borrador' } })
    borradores.value = data
  } catch (e) {
    errorMsg.value = 'No se pudieron cargar tus borradores.'
  } finally {
    cargando.value = false
  }
}

function abrirEditor(id) {
  router.push({ name: 'secuencia-editor', params: { id } })
}

async function eliminar(secuencia) {
  if (!confirm(`¿Eliminar la secuencia de "${secuencia.asignatura?.nombre}" (${secuencia.periodo})? Esta acción no se puede deshacer.`)) return
  eliminandoId.value = secuencia.id
  errorMsg.value = ''
  try {
    await api.delete(`/docente/secuencias/${secuencia.id}`)
    successMsg.value = 'Secuencia eliminada.'
    await cargar()
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'No se pudo eliminar la secuencia.'
  } finally {
    eliminandoId.value = null
  }
}

function onSecuenciaCreada(secuencia) {
  modalCrearAbierto.value = false
  successMsg.value = 'Secuencia creada correctamente.'
  router.push({ name: 'secuencia-editor', params: { id: secuencia.id } })
}

function badgeEstado(estado) {
  return { borrador: 'b-gris', en_revision: 'b-amarillo', en_proceso_validacion: 'b-azul', validada: 'b-verde', rechazada: 'b-rojo' }[estado] ?? 'b-gris'
}
function etiquetaEstado(estado) {
  return { borrador: 'Borrador', en_revision: 'En revisión', en_proceso_validacion: 'En validación', validada: 'Validada', rechazada: 'Rechazada' }[estado] ?? estado
}
</script>
