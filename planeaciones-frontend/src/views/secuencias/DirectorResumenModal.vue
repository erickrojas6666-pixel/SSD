<template>
  <Modal titulo="Resumen de la secuencia" @close="$emit('close')">
    <div v-if="cargando" class="sz-sm" style="text-align:center;color:var(--text-300)">Cargando…</div>

    <template v-else-if="secuencia">
      <div v-if="mensajeError" class="alert a-danger mb4">{{ mensajeError }}</div>

      <div class="resumen-header mb4">
        <div>
          <h3 class="ht-sm">{{ secuencia.asignatura?.nombre }}</h3>
          <p class="sz-xs" style="color:var(--text-300)">{{ secuencia.especialidad?.nombre }} · {{ secuencia.periodo }}</p>
        </div>
        <span :class="['estado-badge', badgeEstado(secuencia.estado)]">{{ etiquetaEstado(secuencia.estado) }}</span>
      </div>

      <div class="field-row mb3">
        <div class="field"><label class="fl">Docente(s)</label><p class="sz-sm">{{ secuencia.autores.map(a => a.nombre_completo).join(', ') }}</p></div>
        <div class="field"><label class="fl">Grupo(s)</label><p class="sz-sm">{{ secuencia.grupos.map(g => g.grupo).join(', ') || '—' }}</p></div>
      </div>

      <div class="field mb3">
        <label class="fl">Propósito</label>
        <p class="sz-sm">{{ secuencia.caratula?.proposito_aprendizaje || '—' }}</p>
      </div>
      <div class="field mb3">
        <label class="fl">Competencia</label>
        <p class="sz-sm">{{ secuencia.caratula?.competencia || '—' }}</p>
      </div>

      <p class="sz-sm mb3"><strong>{{ secuencia.unidades.length }}</strong> unidades de aprendizaje registradas.</p>

      <!-- ═══ Ya validada: solo mostrar el documento firmado ═══ -->
      <div v-if="secuencia.estado === 'validada'" class="alert a-success">
        Esta secuencia ya fue validada.
        <a v-if="secuencia.documento_validacion_url" :href="secuencia.documento_validacion_url" target="_blank"
          rel="noopener" style="font-weight:600;margin-left:4px">Ver documento firmado</a>
      </div>

      <!-- ═══ En proceso de validación: flujo de descarga → firma → subida ═══ -->
      <template v-else-if="secuencia.estado === 'en_proceso_validacion'">
        <div class="field mb3">
          <label class="fl">1. Descarga el formato de validación</label>
          <p class="sz-xs mb2" style="color:var(--text-300)">
            Es el PDF oficial (UTH-ACA-DC-F-PVSD/14) con los datos de esta secuencia ya prellenados.
          </p>
          <button class="btn btn-outline btn-sm" :disabled="descargando" @click="descargarFormato">
            <Download :size="14" style="margin-right:4px" /> {{ descargando ? 'Descargando…' : 'Descargar formato' }}
          </button>
        </div>

        <div class="field mb3">
          <label class="fl">2. Firma y sube el documento de validación</label>

          <div class="firma-tabs mb2">
            <button type="button" class="btn btn-sm" :class="modoFirma === 'archivo' ? 'btn-primary' : 'btn-ghost'"
              @click="modoFirma = 'archivo'">Subir archivo firmado</button>
            <button type="button" class="btn btn-sm" :class="modoFirma === 'digital' ? 'btn-primary' : 'btn-ghost'"
              @click="modoFirma = 'digital'">Firmar digitalmente</button>
          </div>

          <div v-if="modoFirma === 'archivo'">
            <p class="sz-xs mb2" style="color:var(--text-300)">
              Imprime, firma y escanea (o firma en PDF) el formato descargado, y súbelo aquí (PDF, JPG o PNG).
            </p>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="input" @change="onArchivoSeleccionado" />
          </div>

          <div v-else>
            <p class="sz-xs mb2" style="color:var(--text-300)">
              Tu firma se estampará automáticamente sobre el formato de validación para generar el PDF final.
            </p>
            <FirmaDigitalPad ref="firmaPadRef" v-model="firmaDigital" />
          </div>
        </div>
      </template>

      <div v-if="secuencia.estado === 'en_proceso_validacion'" class="field mt3">
        <label class="fl">Comentario (opcional, para el docente)</label>
        <textarea v-model="comentario" class="input" rows="2" placeholder="Motivo de rechazo o notas…"></textarea>
      </div>
    </template>

    <template #footer>
      <button class="btn btn-ghost" @click="$emit('close')">Cerrar</button>
      <template v-if="secuencia?.estado === 'en_proceso_validacion'">
        <button class="btn btn-danger" :disabled="procesando" @click="rechazar">Rechazar</button>
        <button class="btn btn-primary" :disabled="procesando || !puedeValidar" @click="validar">
          {{ procesando ? 'Guardando…' : 'Validar' }}
        </button>
      </template>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Download } from 'lucide-vue-next'
import Modal from '@/components/Modal.vue'
import FirmaDigitalPad from '@/components/FirmaDigitalPad.vue'
import api from '@/services/api'

const props = defineProps({ secuenciaId: { type: [Number, String], required: true } })
const emit = defineEmits(['close', 'resuelta'])

const cargando = ref(true)
const secuencia = ref(null)
const comentario = ref('')
const procesando = ref(false)
const descargando = ref(false)
const mensajeError = ref('')

const modoFirma = ref('archivo')
const archivoFirmado = ref(null)
const firmaDigital = ref(null)
const firmaPadRef = ref(null)

const puedeValidar = computed(() => {
  if (modoFirma.value === 'archivo') return !!archivoFirmado.value
  return !!firmaDigital.value
})

onMounted(async () => {
  try {
    const { data } = await api.get(`/director/secuencias/${props.secuenciaId}/resumen`)
    secuencia.value = data
  } catch (e) {
    mensajeError.value = e.response?.data?.message || 'No se pudo cargar el resumen.'
  } finally {
    cargando.value = false
  }
})

function onArchivoSeleccionado(evento) {
  archivoFirmado.value = evento.target.files[0] || null
}

async function descargarFormato() {
  descargando.value = true
  mensajeError.value = ''
  try {
    const { data } = await api.get(`/director/secuencias/${props.secuenciaId}/formato-validacion`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
    const enlace = document.createElement('a')
    enlace.href = url
    enlace.download = `validacion-${secuencia.value.asignatura?.nombre || 'secuencia'}.pdf`
    document.body.appendChild(enlace)
    enlace.click()
    enlace.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    mensajeError.value = 'No se pudo descargar el formato de validación.'
  } finally {
    descargando.value = false
  }
}

async function validar() {
  procesando.value = true
  mensajeError.value = ''
  try {
    const fd = new FormData()
    if (modoFirma.value === 'archivo') {
      fd.append('documento', archivoFirmado.value)
    } else {
      fd.append('firma_digital', firmaDigital.value)
    }
    if (comentario.value) fd.append('comentario', comentario.value)

    await api.post(`/director/secuencias/${props.secuenciaId}/validar`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    emit('resuelta', 'La secuencia fue validada.')
  } catch (e) {
    mensajeError.value = e.response?.data?.message || 'No se pudo validar.'
  } finally {
    procesando.value = false
  }
}

async function rechazar() {
  procesando.value = true
  mensajeError.value = ''
  try {
    await api.post(`/director/secuencias/${props.secuenciaId}/rechazar`, { comentario: comentario.value || null })
    emit('resuelta', 'La secuencia fue rechazada.')
  } catch (e) {
    mensajeError.value = e.response?.data?.message || 'No se pudo rechazar.'
  } finally {
    procesando.value = false
  }
}

function badgeEstado(estado) {
  return { borrador: 'estado-En_desarrollo', en_revision: 'estado-En_revision', en_proceso_validacion: 'estado-En_proceso_validacion', validada: 'estado-Validada', rechazada: 'estado-Rechazada' }[estado] ?? 'estado-En_desarrollo'
}

function etiquetaEstado(estado) {
  return {
    borrador: 'Borrador', en_revision: 'En revisión', en_proceso_validacion: 'En validación',
    validada: 'Validada', rechazada: 'Rechazada',
  }[estado] || estado
}
</script>

<style scoped>
.resumen-header { display: flex; justify-content: space-between; align-items: center; }
.mb3 { margin-bottom: var(--s3); }
.mt3 { margin-top: var(--s3); }
.mb2 { margin-bottom: var(--s2, 8px); }
.firma-tabs { display: flex; gap: 8px; }
</style>
