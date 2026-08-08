<template>
  <Modal :titulo="asignatura ? 'Editar asignatura' : 'Nueva asignatura'" @close="$emit('close')">
    <!-- Paso normal: formulario -->
    <template v-if="!duplicadoInfo">
      <div v-if="erroresForm.length" class="alert a-danger mb4">
        <div v-for="(e, i) in erroresForm" :key="i">{{ e }}</div>
      </div>

      <div v-if="pdfErrores.length" class="alert a-danger mb4">
        <strong>El PDF del plan de estudio no es válido:</strong>
        <div v-for="(e, i) in pdfErrores" :key="i">{{ e }}</div>
      </div>

      <div v-if="asignatura" class="field">
        <label class="fl">Clave</label>
        <input :value="asignatura.clave" type="text" class="input" />
      </div>

      <div class="field">
        <label class="fl">Nombre<span class="req">*</span></label>
        <input v-model.trim="form.nombre" type="text" class="input" placeholder="Ej. Bases de Datos" />
      </div>

      <div class="field">
        <label class="fl">Cuatrimestre<span class="req">*</span></label>
        <select v-model="form.cuatrimestre_id" class="input">
          <option :value="null" disabled>Selecciona un cuatrimestre</option>
          <option v-for="c in cuatrimestres" :key="c.id" :value="c.id">
            {{ c.nombre || `Cuatrimestre ${c.numero}` }}
          </option>
        </select>
      </div>

      <div class="field">
        <label class="fl">Especialidades<span class="req">*</span></label>
        <div class="checklist">
          <label v-for="esp in especialidades" :key="esp.id" class="checklist-item">
            <input type="checkbox" :value="esp.id" v-model="form.especialidad_ids" />
            <span>{{ esp.nombre }} <span class="sz-xs" style="color:var(--text-300)">({{ esp.carrera?.nombre }})</span></span>
          </label>
        </div>
        <p class="fh">Una asignatura puede pertenecer a varias especialidades.</p>
      </div>

      <div class="field">
        <label class="fl">Plan de estudio (PDF) — opcional</label>
        <input type="file" accept="application/pdf" class="input" @change="onArchivoSeleccionado" />
        <p v-if="asignatura?.plan_estudio_url" class="fh">
          Ya existe un archivo cargado. Si subes uno nuevo, se reemplazará el anterior.
        </p>
        <p class="fh">Se valida automáticamente que tenga la estructura de un programa de asignatura.</p>
      </div>
    </template>

    <!-- Paso de duplicado: se detectó una asignatura con el mismo nombre -->
    <template v-else>
      <div class="alert a-warning mb4">
        Ya existe una asignatura con este nombre.
      </div>
      <div class="card mb4">
        <div class="cp">
          <p class="sz-sm"><strong>{{ duplicadoInfo.nombre }}</strong></p>
          <p class="sz-xs" style="color:var(--text-300)">
            Clave: {{ duplicadoInfo.clave }} · Cuatrimestre: {{ duplicadoInfo.cuatrimestre?.nombre || duplicadoInfo.cuatrimestre?.numero }}
          </p>
        </div>
      </div>
      <p class="sz-sm">¿Qué deseas hacer?</p>
    </template>

    <template #footer>
      <template v-if="!duplicadoInfo">
        <button class="btn btn-ghost" @click="$emit('close')">Cancelar</button>
        <button class="btn btn-primary" :disabled="guardando" @click="guardar">
          {{ guardando ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
      <template v-else>
        <button class="btn btn-ghost" @click="duplicadoInfo = null">Cancelar</button>
        <button class="btn btn-outline" :disabled="guardando" @click="vincularExistente">
          Vincular a la existente
        </button>
        <button class="btn btn-primary" :disabled="guardando" @click="crearComoNueva">
          Crear como nueva
        </button>
      </template>
    </template>
  </Modal>
</template>

<script setup>
import { reactive, ref } from 'vue'
import Modal from '@/components/Modal.vue'
import api from '@/services/api'

const props = defineProps({
  asignatura: { type: Object, default: null },
  cuatrimestres: { type: Array, required: true },
  especialidades: { type: Array, required: true },
})
const emit = defineEmits(['close', 'guardada'])

const form = reactive({
  nombre: props.asignatura?.nombre ?? '',
  cuatrimestre_id: props.asignatura?.cuatrimestre_id ?? null,
  especialidad_ids: props.asignatura?.especialidades?.map((e) => e.id) ?? [],
})
const archivo = ref(null)

const guardando = ref(false)
const erroresForm = ref([])
const pdfErrores = ref([])
const duplicadoInfo = ref(null)

function onArchivoSeleccionado(event) {
  archivo.value = event.target.files[0] ?? null
}

function construirFormData(forzar = false) {
  const fd = new FormData()
  fd.append('nombre', form.nombre)
  fd.append('cuatrimestre_id', form.cuatrimestre_id)
  form.especialidad_ids.forEach((id) => fd.append('especialidad_ids[]', id))
  if (archivo.value) fd.append('plan_estudio', archivo.value)
  if (forzar) fd.append('forzar', '1')
  if (props.asignatura) fd.append('_method', 'PUT')
  return fd
}

async function enviar(forzar = false) {
  guardando.value = true
  erroresForm.value = []
  pdfErrores.value = []
  try {
    const fd = construirFormData(forzar)
    if (props.asignatura) {
      await api.post(`/admin/asignaturas/${props.asignatura.id}`, fd)
    } else {
      await api.post('/admin/asignaturas', fd)
    }
    emit('guardada')
  } catch (e) {
    const status = e.response?.status

    if (status === 409) {
      duplicadoInfo.value = e.response.data.asignatura_existente
      return
    }

    if (status === 422 && e.response.data.errores) {
      pdfErrores.value = e.response.data.errores
      return
    }

    const errores = e.response?.data?.errors
    erroresForm.value = errores ? Object.values(errores).flat() : [e.response?.data?.message || 'No se pudo guardar.']
  } finally {
    guardando.value = false
  }
}

function guardar() {
  enviar(false)
}

function crearComoNueva() {
  enviar(true)
}

async function vincularExistente() {
  guardando.value = true
  try {
    await api.patch(`/admin/asignaturas/${duplicadoInfo.value.id}/vincular-especialidades`, {
      especialidad_ids: form.especialidad_ids,
    })
    emit('guardada')
  } catch (e) {
    erroresForm.value = [e.response?.data?.message || 'No se pudo vincular la asignatura.']
    duplicadoInfo.value = null
  } finally {
    guardando.value = false
  }
}
</script>

<style scoped>
.checklist {
  max-height: 180px;
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
