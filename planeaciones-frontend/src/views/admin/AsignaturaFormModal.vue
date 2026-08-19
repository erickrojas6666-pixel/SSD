<template>
  <Modal :titulo="asignatura ? 'Editar asignatura' : 'Nueva asignatura'" @close="$emit('close')">

    <!-- =========================================
         PASO NORMAL: FORMULARIO
    ========================================== -->
    <template v-if="!duplicadoInfo">

      <!-- Alertas de Error -->
      <div v-if="erroresForm.length" class="alert a-danger mb4 alert-bounce">
        <div class="flex items-start gap-2">
          <AlertCircle :size="18" class="shrink-0 mt-1" />
          <div>
            <div v-for="(e, i) in erroresForm" :key="i">{{ e }}</div>
          </div>
        </div>
      </div>

      <div v-if="pdfErrores.length" class="alert a-danger mb4 alert-bounce">
        <div class="flex items-start gap-2">
          <FileWarning :size="18" class="shrink-0 mt-1" />
          <div>
            <strong>El PDF del plan de estudio no es válido:</strong>
            <ul class="error-list">
              <li v-for="(e, i) in pdfErrores" :key="i">{{ e }}</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Campo Clave (Solo lectura) -->
      <div v-if="asignatura" class="field">
        <label class="fl">Clave de la asignatura</label>
        <div class="input-readonly-wrap">
          <Key :size="16" class="icon-readonly" />
          <input :value="asignatura.clave" type="text" class="input input-readonly" readonly disabled />
        </div>
      </div>

      <!-- Nombre -->
      <div class="field">
        <label class="fl">Nombre de la asignatura<span class="req">*</span></label>
        <input v-model.trim="form.nombre" type="text" class="input input-3d-lit font-bold"
          placeholder="Ej. Bases de Datos" />
      </div>

      <!-- Cuatrimestre -->
      <div class="field">
        <label class="fl">Cuatrimestre<span class="req">*</span></label>
        <select v-model="form.cuatrimestre_id" class="input input-3d-lit font-bold">
          <option :value="null" disabled>— Selecciona un cuatrimestre —</option>
          <option v-for="c in cuatrimestres" :key="c.id" :value="c.id">
            {{ c.nombre || `Cuatrimestre ${c.numero}` }}
          </option>
        </select>
      </div>

      <!-- Especialidades (Checklist) -->
      <div class="field">
        <label class="fl">Especialidades<span class="req">*</span></label>
        <div class="checklist-3d">
          <label v-for="esp in especialidades" :key="esp.id" class="checklist-item">
            <div class="checkbox-wrap">
              <input type="checkbox" :value="esp.id" v-model="form.especialidad_ids" class="custom-checkbox" />
              <div class="checkbox-box">
                <Check :size="14" class="check-icon" />
              </div>
            </div>
            <div class="esp-info">
              <span class="esp-nombre">{{ esp.nombre }}</span>
              <span class="esp-carrera">{{ esp.carrera?.nombre }}</span>
            </div>
          </label>
        </div>
        <p class="fh">
          <Info :size="12" style="display:inline; margin-right:4px" /> Una asignatura puede pertenecer a varias
          especialidades.
        </p>
      </div>

      <!-- Archivo PDF -->
      <div class="field">
        <label class="fl">Plan de estudio (Opcional)</label>

        <div class="file-upload-3d" :class="{ 'has-file': archivo || asignatura?.plan_estudio_url }">
          <input type="file" id="pdf-upload" accept="application/pdf" class="hidden-input"
            @change="onArchivoSeleccionado" />
          <label for="pdf-upload" class="file-label">
            <div class="file-icon-wrap">
              <UploadCloud v-if="!archivo && !asignatura?.plan_estudio_url" :size="24" />
              <FileCheck v-else :size="24" color="#00B64F" />
            </div>
            <div class="file-text">
              <span class="file-title">
                {{ archivo ? archivo.name : (asignatura?.plan_estudio_url ? 'PDF actual cargado' : 'Subir archivo PDF')
                }}
              </span>
              <span class="file-desc">
                {{ archivo || asignatura?.plan_estudio_url ? 'Clic para reemplazar' : 'Se validará su estructura automáticamente' }}
              </span>
            </div>
          </label>
        </div>
      </div>
    </template>

    <!-- =========================================
         PASO DUPLICADO: DETECCIÓN DE CONFLICTO
    ========================================== -->
    <template v-else>
      <div class="alert a-warning mb4 alert-bounce flex items-start gap-2">
        <AlertTriangle :size="20" class="shrink-0 mt-1" />
        <div>
          <strong style="display:block; margin-bottom:4px;">¡Atención! Posible duplicado</strong>
          Hemos detectado que ya existe una asignatura registrada con el mismo nombre exacto en el sistema.
        </div>
      </div>

      <div class="widget-contorno mb4 bg-soft p-4">
        <div class="flex items-center gap-3">
          <div class="icon-wrap-3d-small white-bg">
            <BookOpen :size="20" color="#D97706" />
          </div>
          <div>
            <p class="sz-sm font-bold m-0" style="color:var(--text-900)">{{ duplicadoInfo.nombre }}</p>
            <p class="sz-xs m-0 mt-1" style="color:var(--text-500)">
              <strong>Clave:</strong> {{ duplicadoInfo.clave }} · <strong>Cuatrimestre:</strong> {{
                duplicadoInfo.cuatrimestre?.nombre || duplicadoInfo.cuatrimestre?.numero }}
            </p>
          </div>
        </div>
      </div>

      <p class="sz-sm font-bold text-center">¿Cómo deseas proceder?</p>
    </template>

    <!-- =========================================
         FOOTER DE ACCIONES
    ========================================== -->
    <template #footer>
      <div class="footer-actions">
        <template v-if="!duplicadoInfo">
          <button class="btn btn-ghost btn-cancel" @click="$emit('close')">Cancelar</button>
          <button class="btn btn-add-3d" :disabled="guardando" @click="guardar">
            <Loader2 v-if="guardando" :size="16" class="spin" style="margin-right:6px" />
            <Save v-else :size="16" style="margin-right:6px" />
            {{ guardando ? 'Guardando...' : 'Guardar asignatura' }}
          </button>
        </template>

        <template v-else>
          <button class="btn btn-ghost btn-cancel" @click="duplicadoInfo = null">Volver</button>
          <button class="btn btn-outline btn-page-3d" :disabled="guardando" @click="vincularExistente"
            title="Añade las especialidades seleccionadas a la asignatura existente">
            <LinkIcon :size="16" style="margin-right:6px" /> Vincular a la existente
          </button>
          <button class="btn btn-add-3d" :disabled="guardando" @click="crearComoNueva"
            title="Ignora la advertencia y crea un registro totalmente nuevo">
            <PlusCircle :size="16" style="margin-right:6px" /> Forzar como nueva
          </button>
        </template>
      </div>
    </template>

  </Modal>
</template>

<script setup>
import { reactive, ref } from 'vue'
import {
  AlertCircle, FileWarning, Key, Info, Check, UploadCloud,
  FileCheck, AlertTriangle, BookOpen, Loader2, Save, Link as LinkIcon, PlusCircle
} from 'lucide-vue-next'
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
/* Utilidades rápidas */
.flex {
  display: flex;
}

.items-start {
  align-items: flex-start;
}

.items-center {
  align-items: center;
}

.gap-2 {
  gap: 8px;
}

.gap-3 {
  gap: 12px;
}

.shrink-0 {
  flex-shrink: 0;
}

.mt-1 {
  margin-top: 4px;
}

.mb4 {
  margin-bottom: 16px;
}

.p-4 {
  padding: 16px;
}

.m-0 {
  margin: 0;
}

.font-bold {
  font-weight: 800;
}

.text-center {
  text-align: center;
}

/* ── Animaciones ── */
.spin {
  animation: girar 0.8s linear infinite;
}

@keyframes girar {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

.alert-bounce {
  animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }

  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* ── Inputs 3D Soft UI ── */
.input-3d-lit {
  width: 100%;
  padding: 12px 14px;
  border-radius: var(--r-md);
  border: 2px solid transparent !important;
  background: var(--bg-soft) !important;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
  color: var(--text-900);
  transition: all 0.2s ease;
  font-size: 14px;
}

.input-3d-lit:hover:not(:disabled),
.input-3d-lit:focus:not(:disabled) {
  border-color: var(--uth-verde) !important;
  background: #FFFFFF !important;
  box-shadow: 0 0 0 4px var(--uth-verde-ring), inset 0 2px 4px rgba(0, 0, 0, 0.02) !important;
  outline: none;
}

/* Input Readonly (Clave) */
.input-readonly-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.icon-readonly {
  position: absolute;
  left: 12px;
  color: var(--text-400);
}

.input-readonly {
  width: 100%;
  padding: 10px 14px 10px 36px;
  background: var(--bg-page);
  border: 2px dashed var(--border-soft);
  color: var(--text-500);
  border-radius: var(--r-md);
  font-weight: 700;
  cursor: not-allowed;
}

/* ── Checklist 3D ── */
.checklist-3d {
  max-height: 200px;
  overflow-y: auto;
  border: 2px solid var(--border-soft);
  border-radius: var(--r-md);
  padding: 8px;
  background: var(--bg-soft);
  box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.02);
}

.checklist-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 12px;
  border-radius: var(--r-sm);
  cursor: pointer;
  transition: background 0.2s;
}

.checklist-item:hover {
  background: #FFFFFF;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.04);
}

/* Custom Checkbox */
.checkbox-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 2px;
}

.custom-checkbox {
  opacity: 0;
  position: absolute;
  width: 100%;
  height: 100%;
  cursor: pointer;
  z-index: 2;
}

.checkbox-box {
  width: 18px;
  height: 18px;
  border: 2px solid var(--text-300);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  background: #FFFFFF;
}

.check-icon {
  opacity: 0;
  color: white;
  transform: scale(0.5);
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.custom-checkbox:checked+.checkbox-box {
  background: var(--uth-verde);
  border-color: var(--uth-verde);
}

.custom-checkbox:checked+.checkbox-box .check-icon {
  opacity: 1;
  transform: scale(1);
}

.esp-info {
  display: flex;
  flex-direction: column;
}

.esp-nombre {
  font-weight: 700;
  font-size: 13.5px;
  color: var(--text-800);
}

.esp-carrera {
  font-size: 11.5px;
  color: var(--text-400);
}

/* ── File Upload 3D ── */
.file-upload-3d {
  position: relative;
  border: 2px dashed var(--border);
  border-radius: var(--r-md);
  background: #FFFFFF;
  transition: all 0.2s;
}

.file-upload-3d:hover {
  border-color: var(--uth-verde-claro);
  background: rgba(0, 182, 79, 0.02);
}

.file-upload-3d.has-file {
  border-style: solid;
  border-color: rgba(0, 182, 79, 0.3);
  background: rgba(0, 182, 79, 0.05);
}

.hidden-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
}

.file-label {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  cursor: pointer;
  width: 100%;
}

.file-icon-wrap {
  width: 40px;
  height: 40px;
  background: var(--bg-soft);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-500);
}

.has-file .file-icon-wrap {
  background: #FFFFFF;
  box-shadow: 0 4px 10px rgba(0, 182, 79, 0.15);
}

.file-text {
  display: flex;
  flex-direction: column;
}

.file-title {
  font-weight: 800;
  font-size: 14px;
  color: var(--text-900);
}

.file-desc {
  font-size: 12px;
  color: var(--text-400);
}

.has-file .file-title {
  color: var(--uth-verde);
}

/* ── Estilos Tarjeta Duplicado ── */
.widget-contorno {
  border: 2px solid rgba(217, 119, 6, 0.3);
  /* Naranja/Ambar suave */
  border-radius: var(--r-lg);
  box-shadow: 0 8px 20px rgba(217, 119, 6, 0.08);
}

.icon-wrap-3d-small {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transform: rotate(-3deg);
  background: rgba(217, 119, 6, 0.1);
  border: 2px solid rgba(217, 119, 6, 0.2);
}

.white-bg {
  background: #FFFFFF;
}

/* ── Botones Footer ── */
.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  width: 100%;
}

.btn-cancel {
  font-weight: 700;
  color: var(--text-500);
}

.btn-cancel:hover {
  background: var(--bg-soft);
  color: var(--text-900);
}

.btn-add-3d {
  background: var(--uth-verde);
  color: white;
  border: none;
  border-radius: var(--r-pill);
  padding: 10px 20px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 0 #007734, 0 8px 15px rgba(0, 182, 79, 0.3);
  transform: translateY(-2px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  cursor: pointer;
}

.btn-add-3d:hover:not(:disabled) {
  transform: translateY(-4px);
  box-shadow: 0 6px 0 #007734, 0 12px 20px rgba(0, 182, 79, 0.4);
}

.btn-add-3d:active:not(:disabled) {
  transform: translateY(2px);
  box-shadow: 0 0 0 #007734;
}

.btn-add-3d:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: translateY(0);
  box-shadow: none;
}

.btn-page-3d {
  border-radius: var(--r-pill);
  border: 2px solid rgba(0, 182, 79, 0.3);
  color: var(--uth-verde);
  background: transparent;
  font-weight: 800;
  box-shadow: 0 4px 0 rgba(0, 182, 79, 0.15);
  transform: translateY(-2px);
  display: inline-flex;
  align-items: center;
  padding: 10px 20px;
  cursor: pointer;
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.btn-page-3d:hover:not(:disabled) {
  background: var(--uth-verde);
  color: #FFFFFF;
  border-color: var(--uth-verde);
  box-shadow: 0 6px 0 #007734;
  transform: translateY(-4px);
}

.btn-page-3d:active:not(:disabled) {
  transform: translateY(2px);
  box-shadow: 0 0 0 #007734;
}

.error-list {
  margin: 4px 0 0;
  padding-left: 20px;
  font-size: 13px;
}
</style>