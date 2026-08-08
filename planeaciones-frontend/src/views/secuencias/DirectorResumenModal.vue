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

      <p class="sz-sm"><strong>{{ secuencia.unidades.length }}</strong> unidades de aprendizaje registradas.</p>

      <div class="field mt3">
        <label class="fl">Comentario (opcional, para el docente)</label>
        <textarea v-model="comentario" class="input" rows="3" placeholder="Motivo de rechazo o notas…"></textarea>
      </div>
    </template>

    <template #footer>
      <button class="btn btn-ghost" @click="$emit('close')">Cerrar</button>
      <button class="btn btn-danger" :disabled="procesando" @click="rechazar">Rechazar</button>
      <button class="btn btn-primary" :disabled="procesando" @click="validar">Validar</button>
    </template>
  </Modal>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Modal from '@/components/Modal.vue'
import api from '@/services/api'

const props = defineProps({ secuenciaId: { type: [Number, String], required: true } })
const emit = defineEmits(['close', 'resuelta'])

const cargando = ref(true)
const secuencia = ref(null)
const comentario = ref('')
const procesando = ref(false)
const mensajeError = ref('')

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

async function validar() {
  procesando.value = true
  try {
    await api.post(`/director/secuencias/${props.secuenciaId}/validar`, { comentario: comentario.value || null })
    emit('resuelta', 'La secuencia fue validada.')
  } catch (e) {
    mensajeError.value = e.response?.data?.message || 'No se pudo validar.'
  } finally {
    procesando.value = false
  }
}

async function rechazar() {
  procesando.value = true
  try {
    await api.post(`/director/secuencias/${props.secuenciaId}/rechazar`, { comentario: comentario.value || null })
    emit('resuelta', 'La secuencia fue rechazada.')
  } catch (e) {
    mensajeError.value = e.response?.data?.message || 'No se pudo rechazar.'
  } finally {
    procesando.value = false
  }
}
</script>

<style scoped>
.resumen-header { display: flex; justify-content: space-between; align-items: center; }
.mb3 { margin-bottom: var(--s3); }
.mt3 { margin-top: var(--s3); }
</style>
