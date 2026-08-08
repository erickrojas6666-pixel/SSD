<template>
  <Modal titulo="Carga masiva de asignaturas" @close="$emit('close')">
    <!-- Paso 1: datos generales + lista de nombres -->
    <template v-if="paso === 1">
      <div v-if="erroresForm.length" class="alert a-danger mb4">
        <div v-for="(e, i) in erroresForm" :key="i">{{ e }}</div>
      </div>

      <div class="field">
        <label class="fl">Cuatrimestre<span class="req">*</span></label>
        <select v-model="form.cuatrimestre_id" class="input">
          <option :value="null" disabled>Selecciona un cuatrimestre</option>
          <option v-for="c in cuatrimestres" :key="c.id" :value="c.id">
            {{ c.nombre || `Cuatrimestre ${c.numero}` }}
          </option>
        </select>
        <p class="fh">Todas las asignaturas nuevas de esta lista quedarán en este cuatrimestre.</p>
      </div>

      <div class="field">
        <label class="fl">Especialidades<span class="req">*</span></label>
        <div class="checklist">
          <label v-for="esp in especialidades" :key="esp.id" class="checklist-item">
            <input type="checkbox" :value="esp.id" v-model="form.especialidad_ids" />
            <span>{{ esp.nombre }} <span class="sz-xs" style="color:var(--text-300)">({{ esp.carrera?.nombre }})</span></span>
          </label>
        </div>
      </div>

      <div class="field">
        <label class="fl">Nombres de las asignaturas<span class="req">*</span></label>
        <textarea
          v-model="form.nombresTexto"
          class="input"
          rows="8"
          placeholder="Un nombre por línea, por ejemplo:&#10;Fundamentos de Programación&#10;Bases de Datos&#10;Estructura de Datos"
        ></textarea>
        <p class="fh">Se revisará cada nombre contra la base de datos antes de insertar.</p>
      </div>
    </template>

    <!-- Paso 2: resultados de la verificación -->
    <template v-else>
      <div v-if="erroresForm.length" class="alert a-danger mb4">
        <div v-for="(e, i) in erroresForm" :key="i">{{ e }}</div>
      </div>

      <p class="sz-sm mb4">
        Marca las que quieras <strong>vincular</strong> a la asignatura ya existente. Si desmarcas una, se creará como registro nuevo aunque el nombre coincida.
      </p>

      <div class="card">
        <div class="cp" style="overflow-x:auto">
          <table class="tt">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Estado</th>
                <th>Vincular</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r, i) in resultados" :key="i">
                <td>{{ r.nombre }}</td>
                <td>
                  <span v-if="r.existe" class="badge b-amarillo">
                    Ya existe: {{ r.asignatura_existente.nombre }} ({{ r.asignatura_existente.clave }})
                  </span>
                  <span v-else class="badge b-verde">Nueva</span>
                </td>
                <td>
                  <input type="checkbox" v-model="r.vincular" :disabled="!r.existe" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <template #footer>
      <template v-if="paso === 1">
        <button class="btn btn-ghost" @click="$emit('close')">Cancelar</button>
        <button class="btn btn-primary" :disabled="verificando" @click="verificar">
          {{ verificando ? 'Verificando…' : 'Verificar' }}
        </button>
      </template>
      <template v-else>
        <button class="btn btn-ghost" @click="paso = 1">Volver</button>
        <button class="btn btn-primary" :disabled="insertando" @click="confirmarInsercion">
          {{ insertando ? 'Insertando…' : 'Confirmar e insertar' }}
        </button>
      </template>
    </template>
  </Modal>
</template>

<script setup>
import { reactive, ref } from 'vue'
import Modal from '@/components/Modal.vue'
import api from '@/services/api'

defineProps({
  cuatrimestres: { type: Array, required: true },
  especialidades: { type: Array, required: true },
})
const emit = defineEmits(['close', 'completado'])

const paso = ref(1)
const verificando = ref(false)
const insertando = ref(false)
const erroresForm = ref([])
const resultados = ref([])

const form = reactive({
  cuatrimestre_id: null,
  especialidad_ids: [],
  nombresTexto: '',
})

function parsearNombres() {
  return form.nombresTexto
    .split('\n')
    .map((n) => n.trim())
    .filter((n) => n.length > 0)
}

async function verificar() {
  erroresForm.value = []

  const nombres = parsearNombres()
  if (!form.cuatrimestre_id) {
    erroresForm.value = ['Selecciona un cuatrimestre.']
    return
  }
  if (form.especialidad_ids.length === 0) {
    erroresForm.value = ['Selecciona al menos una especialidad.']
    return
  }
  if (nombres.length === 0) {
    erroresForm.value = ['Escribe al menos un nombre de asignatura.']
    return
  }

  verificando.value = true
  try {
    const { data } = await api.post('/admin/asignaturas/masivo/verificar', { nombres })
    resultados.value = data.map((r) => ({ ...r, vincular: r.existe }))
    paso.value = 2
  } catch (e) {
    erroresForm.value = [e.response?.data?.message || 'No se pudo verificar la lista.']
  } finally {
    verificando.value = false
  }
}

async function confirmarInsercion() {
  insertando.value = true
  erroresForm.value = []
  try {
    const items = resultados.value.map((r) => ({
      nombre: r.nombre,
      vincular_a_id: r.existe && r.vincular ? r.asignatura_existente.id : null,
    }))

    const { data } = await api.post('/admin/asignaturas/masivo', {
      cuatrimestre_id: form.cuatrimestre_id,
      especialidad_ids: form.especialidad_ids,
      items,
    })

    emit('completado', data.message)
  } catch (e) {
    erroresForm.value = [e.response?.data?.message || 'No se pudo completar la carga masiva.']
  } finally {
    insertando.value = false
  }
}
</script>

<style scoped>
.checklist {
  max-height: 160px;
  overflow-y: auto;
  border: 1px solid var(--border);
  border-radius: var(--r-sm);
  padding: var(--s3);
}
.checklist-item {
  display: flex;
  align-items: center;
  gap: var(--s2);
  padding: var(--s2) 0;
  font-size: var(--p-sm);
  cursor: pointer;
}
</style>
