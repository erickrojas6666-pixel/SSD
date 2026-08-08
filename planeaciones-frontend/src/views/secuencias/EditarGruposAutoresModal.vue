<template>
  <Modal titulo="Editar grupos y coautores" @close="$emit('close')">
    <div v-if="erroresForm.length" class="alert a-danger mb4">
      <div v-for="(e, i) in erroresForm" :key="i">{{ e }}</div>
    </div>

    <div class="field">
      <label class="fl">Coautores</label>
      <div class="checklist">
        <label v-for="d in docentes" :key="d.id" class="checklist-item">
          <input type="checkbox" :value="d.id" v-model="form.coautor_ids" />
          <span>{{ d.nombre }} {{ d.apellido_paterno }}</span>
        </label>
      </div>
    </div>

    <div class="field">
      <label class="fl">Grupo(s)</label>
      <div class="grupos-lista">
        <div v-for="(g, i) in form.grupos" :key="i" class="grupos-item">
          <input v-model.trim="form.grupos[i]" type="text" class="input" />
          <button type="button" class="btn-icon-mini" @click="form.grupos.splice(i, 1)"><X :size="14" /></button>
        </div>
      </div>
      <button type="button" class="btn btn-outline btn-sm" @click="form.grupos.push('')">
        <Plus :size="14" style="margin-right:4px" /> Agregar grupo
      </button>
    </div>

    <template #footer>
      <button class="btn btn-ghost" @click="$emit('close')">Cancelar</button>
      <button class="btn btn-primary" :disabled="guardando" @click="guardar">
        {{ guardando ? 'Guardando…' : 'Guardar' }}
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { Plus, X } from 'lucide-vue-next'
import Modal from '@/components/Modal.vue'
import api from '@/services/api'

const props = defineProps({ secuencia: { type: Object, required: true } })
const emit = defineEmits(['close', 'actualizado'])

const docentes = ref([])
const guardando = ref(false)
const erroresForm = ref([])

const form = reactive({
  coautor_ids: props.secuencia.autores.map((a) => a.id),
  grupos: props.secuencia.grupos.map((g) => g.grupo),
})

onMounted(async () => {
  const { data } = await api.get('/secuencias/catalogos')
  docentes.value = data.docentes
})

async function guardar() {
  guardando.value = true
  erroresForm.value = []
  const grupos = form.grupos.map((g) => g.trim()).filter(Boolean)

  try {
    const { data } = await api.patch(`/docente/secuencias/${props.secuencia.id}/grupos-autores`, {
      coautor_ids: form.coautor_ids,
      grupos,
    })
    emit('actualizado', data)
  } catch (e) {
    const errores = e.response?.data?.errors
    erroresForm.value = errores ? Object.values(errores).flat() : [e.response?.data?.message || 'No se pudo guardar.']
  } finally {
    guardando.value = false
  }
}
</script>

<style scoped>
.checklist { max-height: 160px; overflow-y: auto; border: 1px solid var(--border); border-radius: var(--r-sm); padding: var(--s3); }
.checklist-item { display: flex; align-items: center; gap: var(--s2); padding: var(--s2) 0; font-size: var(--p-sm); cursor: pointer; }
.grupos-lista { display: flex; flex-direction: column; gap: var(--s2); margin-bottom: var(--s2); }
.grupos-item { display: flex; align-items: center; gap: var(--s2); }
.btn-icon-mini { border: none; background: none; color: var(--danger); cursor: pointer; display: flex; }
</style>
