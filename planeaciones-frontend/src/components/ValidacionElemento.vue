<template>
  <!-- Variante "fila": para celdas dentro de una tabla (temas, evidencias, fases, referencias) -->
  <td v-if="variante === 'fila'" class="val-col-cell" :class="claseFila">
    <template v-if="revision?.aprobado === true">
      <span class="val-pill val-pill-ok"><CheckCircle2 :size="10" /> Validado</span>
      <div v-if="revision.comentario" class="val-comment-ok-display">{{ revision.comentario }}</div>
      <div v-if="puedeValidar" class="val-action-btns">
        <button class="btn-val-reject" @click="enviar(false)"><XCircle :size="10" /> Revertir</button>
      </div>
    </template>
    <template v-else-if="revision?.aprobado === false">
      <span class="val-pill val-pill-rejected"><XCircle :size="10" /> Rechazado</span>
      <div v-if="revision.comentario" class="val-comment-display">{{ revision.comentario }}</div>
      <template v-if="puedeValidar">
        <textarea v-model="comentario" class="val-comment-input" placeholder="Comentario…"></textarea>
        <div class="val-action-btns">
          <button class="btn-val-ok" @click="enviar(true)"><CheckCircle2 :size="10" /> Validar</button>
          <button class="btn-val-reject" @click="enviar(false)"><XCircle :size="10" /> Actualizar</button>
        </div>
      </template>
    </template>
    <template v-else>
      <template v-if="puedeValidar">
        <span style="font-size:9px;color:#856404;display:block;margin-bottom:3px;">Sin validar</span>
        <textarea v-model="comentario" class="val-comment-input" placeholder="Comentario (opcional)…"></textarea>
        <div class="val-action-btns">
          <button class="btn-val-ok" @click="enviar(true)"><CheckCircle2 :size="10" /> Validar</button>
          <button class="btn-val-reject" @click="enviar(false)"><XCircle :size="10" /> Rechazar</button>
        </div>
      </template>
      <span v-else style="font-size:9px;color:#999;">Sin validar</span>
    </template>
  </td>

  <!-- Variante "barra": para el encabezado de unidad/evaluación -->
  <div v-else class="val-section-bar" :class="claseBarra">
    <template v-if="revision?.aprobado === true">
      <span class="val-pill val-pill-ok"><CheckCircle2 :size="12" /> Validado</span>
      <span v-if="revision.comentario" class="val-comment-ok-display">{{ revision.comentario }}</span>
      <div v-if="puedeValidar" class="val-action-btns">
        <button class="btn-val-reject" @click="enviar(false)"><XCircle :size="11" /> Revertir</button>
      </div>
    </template>
    <template v-else-if="revision?.aprobado === false">
      <span class="val-pill val-pill-rejected"><XCircle :size="12" /> Rechazado</span>
      <span v-if="revision.comentario" class="val-comment-display">{{ revision.comentario }}</span>
      <template v-if="puedeValidar">
        <textarea v-model="comentario" class="val-comment-input" placeholder="Actualizar comentario…"></textarea>
        <div class="val-action-btns">
          <button class="btn-val-ok" @click="enviar(true)"><CheckCircle2 :size="11" /> Validar</button>
          <button class="btn-val-reject" @click="enviar(false)"><XCircle :size="11" /> Actualizar</button>
        </div>
      </template>
    </template>
    <template v-else-if="puedeValidar">
      <span style="font-size:10.5px;color:#856404;">Sin validar</span>
      <textarea v-model="comentario" class="val-comment-input" placeholder="Comentario de rechazo (opcional)…"></textarea>
      <div class="val-action-btns">
        <button class="btn-val-ok" @click="enviar(true)"><CheckCircle2 :size="11" /> Validar</button>
        <button class="btn-val-reject" @click="enviar(false)"><XCircle :size="11" /> Rechazar</button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { CheckCircle2, XCircle } from 'lucide-vue-next'
import api from '@/services/api'

const props = defineProps({
  tipo: { type: String, required: true }, // unidad|tema|evaluacion|evidencia|fase|referencia
  elementoId: { type: [Number, String], required: true },
  revision: { type: Object, default: null },
  puedeValidar: { type: Boolean, default: false },
  variante: { type: String, default: 'fila' }, // fila | barra
})
const emit = defineEmits(['actualizado'])

const comentario = ref(props.revision?.comentario ?? '')

const claseFila = computed(() => {
  if (props.revision?.aprobado === true) return 'vr-ok'
  if (props.revision?.aprobado === false) return 'vr-rejected'
  return 'vr-pending'
})
const claseBarra = computed(() => {
  if (props.revision?.aprobado === true) return 'vsb-ok'
  if (props.revision?.aprobado === false) return 'vsb-rejected'
  return 'vsb-pending'
})

async function enviar(aprobado) {
  try {
    const { data } = await api.patch(`/revisor/validacion/${props.tipo}/${props.elementoId}`, {
      aprobado,
      comentario: comentario.value || null,
    })
    emit('actualizado', data)
  } catch (e) {
    alert(e.response?.data?.message || 'No se pudo actualizar la validación.')
  }
}
</script>
