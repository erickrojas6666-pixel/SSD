<template>
  <span
    ref="anclaRef"
    class="info-tooltip"
    @mouseenter="mostrar"
    @mouseleave="ocultar"
    @focusin="mostrar"
    @focusout="ocultar"
    tabindex="0"
  >
    <Info :size="13" class="info-tooltip-icon" />
  </span>

  <Teleport to="body">
    <div v-if="visible" class="info-tooltip-box" :style="posicion">{{ texto }}</div>
  </Teleport>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Info } from 'lucide-vue-next'

defineProps({ texto: { type: String, required: true } })

const anclaRef = ref(null)
const visible = ref(false)
const posicion = reactive({ top: '0px', left: '0px' })

// Se calcula la posición real en pantalla (no relativa a ningún contenedor),
// así que nunca se recorta por el overflow-x/auto de las tablas responsivas.
function mostrar() {
  const rect = anclaRef.value.getBoundingClientRect()
  const ANCHO_TOOLTIP = 260
  let left = rect.left + rect.width / 2 - ANCHO_TOOLTIP / 2
  left = Math.max(8, Math.min(left, window.innerWidth - ANCHO_TOOLTIP - 8))

  posicion.left = `${left}px`
  posicion.top = `${rect.top - 8}px`
  posicion.transform = 'translateY(-100%)'
  visible.value = true
}

function ocultar() {
  visible.value = false
}
</script>

<style scoped>
.info-tooltip {
  position: relative;
  display: inline-flex;
  align-items: center;
  margin-left: 4px;
  cursor: help;
  outline: none;
}
.info-tooltip-icon {
  color: var(--text-300);
}
.info-tooltip:hover .info-tooltip-icon,
.info-tooltip:focus .info-tooltip-icon {
  color: var(--uth-verde);
}
</style>

<style>
/* Sin scoped: vive en un <Teleport> fuera del árbol de este componente */
.info-tooltip-box {
  position: fixed;
  z-index: 9999;
  width: 260px;
  background: #1a1a1a;
  color: #f2f2f2;
  font-size: 11.5px;
  font-weight: 400;
  line-height: 1.5;
  padding: 8px 10px;
  border-radius: var(--r-sm, 6px);
  box-shadow: var(--sh-md, 0 6px 20px rgba(0, 0, 0, .3));
  white-space: normal;
  pointer-events: none;
}
</style>
