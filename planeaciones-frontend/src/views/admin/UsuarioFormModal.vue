<template>
  <Modal :titulo="usuario ? 'Editar usuario' : 'Nuevo usuario'" @close="$emit('close')">
    <div v-if="erroresForm.length" class="alert a-danger mb4">
      <div v-for="(e, i) in erroresForm" :key="i">{{ e }}</div>
    </div>

    <div v-if="!usuario" class="alert a-info mb4">
      Se generará una contraseña temporal y se enviará por correo junto con un enlace de confirmación.
    </div>

    <div class="field-row">
      <div class="field">
        <label class="fl">Nombre(s)<span class="req">*</span></label>
        <input v-model.trim="form.nombre" type="text" class="input" placeholder="Ej. María" />
      </div>
      <div class="field">
        <label class="fl">Apellido paterno<span class="req">*</span></label>
        <input v-model.trim="form.apellido_paterno" type="text" class="input" placeholder="Ej. López" />
      </div>
    </div>

    <div class="field">
      <label class="fl">Apellido materno</label>
      <input v-model.trim="form.apellido_materno" type="text" class="input" placeholder="Ej. García" />
    </div>

    <div class="field">
      <label class="fl">Correo<span class="req">*</span></label>
      <input v-model.trim="form.email" type="email" class="input" placeholder="usuario@uth.edu.mx" />
    </div>

    <div class="field">
      <label class="fl">Roles<span class="req">*</span></label>
      <div class="checklist">
        <label v-for="r in catalogos.roles" :key="r.id" class="checklist-item">
          <input type="checkbox" :value="r.id" v-model="form.rol_ids" />
          <span>{{ r.nombre }}</span>
        </label>
      </div>
    </div>

    <!-- Docente: materias que puede impartir -->
    <div v-if="esDocente" class="field">
      <label class="fl">Materias que puede impartir</label>
      <div class="checklist">
        <label v-for="a in catalogos.asignaturas" :key="a.id" class="checklist-item">
          <input type="checkbox" :value="a.id" v-model="form.asignatura_ids" />
          <span>{{ a.nombre }} <span class="sz-xs" style="color:var(--text-300)">({{ a.clave }})</span></span>
        </label>
        <p v-if="catalogos.asignaturas.length === 0" class="sz-sm" style="color:var(--text-300)">
          Aún no hay asignaturas registradas.
        </p>
      </div>
    </div>

    <!-- Director: carrera que dirige -->
    <div v-if="esDirector" class="field">
      <label class="fl">Carrera que dirige</label>
      <select v-model="form.carrera_id" class="input">
        <option :value="null">Sin asignar</option>
        <option v-for="c in carrerasDisponiblesParaDirector" :key="c.id" :value="c.id">{{ c.nombre }}</option>
      </select>
      <p class="fh">Solo se listan carreras sin director asignado (o la que ya dirige, si estás editando).</p>
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
import { reactive, ref, computed } from 'vue'
import Modal from '@/components/Modal.vue'
import api from '@/services/api'

const props = defineProps({
  usuario: { type: Object, default: null },
  catalogos: { type: Object, required: true },
})
const emit = defineEmits(['close', 'guardado'])

const form = reactive({
  nombre: props.usuario?.nombre ?? '',
  apellido_paterno: props.usuario?.apellido_paterno ?? '',
  apellido_materno: props.usuario?.apellido_materno ?? '',
  email: props.usuario?.email ?? '',
  rol_ids: props.usuario?.roles?.map((r) => r.id) ?? [],
  asignatura_ids: props.usuario?.asignaturas?.map((a) => a.id) ?? [],
  carrera_id: props.usuario?.carreraDirigida?.id ?? null,
})

const guardando = ref(false)
const erroresForm = ref([])

const nombresRolesSeleccionados = computed(() =>
  props.catalogos.roles.filter((r) => form.rol_ids.includes(r.id)).map((r) => r.nombre)
)
const esDocente = computed(() => nombresRolesSeleccionados.value.includes('Docente'))
const esDirector = computed(() => nombresRolesSeleccionados.value.includes('Director'))

const carrerasDisponiblesParaDirector = computed(() =>
  props.catalogos.carreras.filter(
    (c) => !c.director_id || c.director_id === props.usuario?.id
  )
)

async function guardar() {
  guardando.value = true
  erroresForm.value = []
  try {
    const payload = {
      nombre: form.nombre,
      apellido_paterno: form.apellido_paterno,
      apellido_materno: form.apellido_materno || null,
      email: form.email,
      rol_ids: form.rol_ids,
      asignatura_ids: esDocente.value ? form.asignatura_ids : [],
      carrera_id: esDirector.value ? form.carrera_id : null,
    }

    if (props.usuario) {
      await api.put(`/admin/usuarios/${props.usuario.id}`, payload)
      emit('guardado', 'Usuario actualizado correctamente.')
    } else {
      await api.post('/admin/usuarios', payload)
      emit('guardado', 'Se creó el usuario y se envió un correo con sus credenciales.')
    }
  } catch (e) {
    const errores = e.response?.data?.errors
    erroresForm.value = errores ? Object.values(errores).flat() : [e.response?.data?.message || 'No se pudo guardar.']
  } finally {
    guardando.value = false
  }
}
</script>

<style scoped>
.field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--s4);
}
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
