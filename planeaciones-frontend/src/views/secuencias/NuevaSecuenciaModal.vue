<template>
  <Modal titulo="Nueva secuencia didáctica" @close="$emit('close')">
    <div v-if="erroresForm.length" class="alert a-danger mb4">
      <div v-for="(e, i) in erroresForm" :key="i">{{ e }}</div>
    </div>
    <div v-if="pdfErrores.length" class="alert a-danger mb4">
      <strong>El PDF del plan de estudio no es válido:</strong>
      <div v-for="(e, i) in pdfErrores" :key="i">{{ e }}</div>
    </div>

    <div class="field">
      <label class="fl">Asignatura<span class="req">*</span></label>
      <select v-model="form.asignatura_id" class="input" @change="onCambioAsignatura">
        <option :value="null" disabled>Selecciona una asignatura</option>
        <option v-for="a in asignaturas" :key="a.id" :value="a.id">{{ a.nombre }} ({{ a.clave }})</option>
      </select>
    </div>

    <div v-if="asignaturaSeleccionada" class="field">
      <label class="fl">Especialidad<span class="req">*</span></label>
      <select v-model="form.especialidad_id" class="input" @change="onCambioEspecialidad">
        <option :value="null" disabled>Selecciona una especialidad</option>
        <option v-for="e in asignaturaSeleccionada.especialidades" :key="e.id" :value="e.id">
          {{ e.nombre }} ({{ e.carrera?.nombre }})
        </option>
      </select>
    </div>

    <div class="field">
      <label class="fl">Periodo<span class="req">*</span></label>
      <select v-model="form.periodo" class="input">
        <option value="" disabled>Selecciona un periodo</option>
        <option v-for="p in periodosDisponibles" :key="p" :value="p">{{ p }}</option>
      </select>
    </div>

    <div class="field">
      <label class="fl">Coautores</label>
      <div class="checklist">
        <label v-for="d in docentes" :key="d.id" class="checklist-item">
          <input type="checkbox" :value="d.id" v-model="form.coautor_ids" />
          <span>{{ d.nombre }} {{ d.apellido_paterno }}</span>
        </label>
      </div>
      <p class="fh">Tú ya quedas incluido automáticamente como autor principal.</p>
    </div>

    <div class="field">
      <label class="fl">Grupo(s)<span class="req">*</span></label>
      <div class="grupos-lista">
        <div v-for="(g, i) in form.grupos" :key="i" class="grupos-item">
          <input v-model.trim="form.grupos[i]" type="text" class="input" placeholder="Ej. ITI-3A" />
          <button type="button" class="btn-icon-mini" @click="form.grupos.splice(i, 1)">
            <X :size="14" />
          </button>
        </div>
      </div>
      <button type="button" class="btn btn-outline btn-sm" @click="form.grupos.push('')">
        <Plus :size="14" style="margin-right:4px" /> Agregar grupo
      </button>
    </div>

    <div v-if="asignaturaSeleccionada && !asignaturaSeleccionada.plan_estudio_url" class="field">
      <label class="fl">Plan de estudio (PDF)</label>
      <input type="file" accept="application/pdf" class="input" @change="onArchivo" />
      <p class="fh">
        Esta asignatura aún no tiene un plan de estudio cargado. Si subes el PDF aquí, el sistema
        prellenará automáticamente carátula, unidades, temas y bibliografía a partir de su contenido.
      </p>
    </div>
    <div v-else-if="asignaturaSeleccionada" class="alert a-info">
      Esta asignatura ya tiene un plan de estudio cargado — se usará para prellenar la secuencia automáticamente.
    </div>

    <template #footer>
      <button class="btn btn-ghost" @click="$emit('close')">Cancelar</button>
      <button class="btn btn-primary" :disabled="guardando" @click="guardar">
        {{ guardando ? 'Creando…' : 'Crear secuencia' }}
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { Plus, X } from 'lucide-vue-next'
import Modal from '@/components/Modal.vue'
import api from '@/services/api'

const emit = defineEmits(['close', 'creada'])

const asignaturas = ref([])
const docentes = ref([])
const archivo = ref(null)
const guardando = ref(false)
const erroresForm = ref([])
const pdfErrores = ref([])

// ── Periodo: cuatrimestre + año calculados automáticamente a partir de hoy,
// en vez de pedirle el año al usuario (es un dato derivable, no algo que
// deba escribir o seleccionar a mano).
const CUATRIMESTRES = ['Enero - Abril', 'Mayo - Agosto', 'Septiembre - Diciembre']

// Índice de cuatrimestre (0, 1 o 2) según el mes de una fecha dada.
function indiceCuatrimestre(fecha) {
  return Math.floor(fecha.getMonth() / 4) // meses 0-3 → 0, 4-7 → 1, 8-11 → 2
}

// Genera la lista de periodos en orden cronológico: uno anterior al actual
// (por si se registra una secuencia con retraso) + el actual + los
// siguientes tres, con el año ya resuelto para cada uno.
function generarPeriodosDisponibles() {
  const hoy = new Date()
  let idxGlobal = hoy.getFullYear() * 3 + indiceCuatrimestre(hoy) // periodo actual, en escala continua
  const inicio = idxGlobal - 1
  const fin = idxGlobal + 3
  const periodos = []
  for (let idx = inicio; idx <= fin; idx++) {
    const anio = Math.floor(idx / 3)
    const cuatrimestre = CUATRIMESTRES[((idx % 3) + 3) % 3]
    periodos.push(`${cuatrimestre} ${anio}`)
  }
  return periodos
}

const periodosDisponibles = generarPeriodosDisponibles()

const form = reactive({
  asignatura_id: null,
  especialidad_id: null,
  carrera_id: null,
  periodo: '',
  coautor_ids: [],
  grupos: [''],
})

const asignaturaSeleccionada = computed(() => asignaturas.value.find((a) => a.id === form.asignatura_id))

onMounted(async () => {
  const { data } = await api.get('/secuencias/catalogos')
  asignaturas.value = data.asignaturas
  docentes.value = data.docentes
})

function onCambioAsignatura() {
  form.especialidad_id = null
  form.carrera_id = null
}

function onCambioEspecialidad() {
  const especialidad = asignaturaSeleccionada.value?.especialidades.find((e) => e.id === form.especialidad_id)
  form.carrera_id = especialidad?.carrera?.id ?? especialidad?.carrera_id ?? null
}

function onArchivo(event) {
  archivo.value = event.target.files[0] ?? null
}

async function guardar() {
  guardando.value = true
  erroresForm.value = []
  pdfErrores.value = []

  const grupos = form.grupos.map((g) => g.trim()).filter(Boolean)
  if (!form.asignatura_id || !form.especialidad_id || !form.periodo || grupos.length === 0) {
    erroresForm.value = ['Completa asignatura, especialidad, periodo y al menos un grupo.']
    guardando.value = false
    return
  }

  try {
    const fd = new FormData()
    fd.append('asignatura_id', form.asignatura_id)
    fd.append('especialidad_id', form.especialidad_id)
    fd.append('carrera_id', form.carrera_id)
    fd.append('periodo', form.periodo)
    form.coautor_ids.forEach((id) => fd.append('coautor_ids[]', id))
    grupos.forEach((g) => fd.append('grupos[]', g))
    if (archivo.value) fd.append('plan_estudio', archivo.value)

    const { data } = await api.post('/docente/secuencias', fd)
    emit('creada', data)
  } catch (e) {
    const status = e.response?.status
    if (status === 422 && e.response.data.errores) {
      pdfErrores.value = e.response.data.errores
    } else {
      const errores = e.response?.data?.errors
      erroresForm.value = errores ? Object.values(errores).flat() : [e.response?.data?.message || 'No se pudo crear la secuencia.']
    }
  } finally {
    guardando.value = false
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

.grupos-lista {
  display: flex;
  flex-direction: column;
  gap: var(--s2);
  margin-bottom: var(--s2);
}

.grupos-item {
  display: flex;
  align-items: center;
  gap: var(--s2);
}

.btn-icon-mini {
  border: none;
  background: none;
  color: var(--danger);
  cursor: pointer;
  display: flex;
}
</style>