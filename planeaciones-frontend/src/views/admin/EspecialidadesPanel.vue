<template>
  <div>
    <!-- Barra de acciones -->
    <div class="card mb4">
      <div class="cp flex jb ic fw g3u">
        <div class="flex ic g3u fw">
          <input
            v-model.trim="filtros.q"
            type="text"
            class="input"
            placeholder="Buscar por nombre o clave…"
            style="max-width: 240px"
            @input="buscarConDebounce"
          />
          <select v-model="filtros.carrera_id" class="input" style="max-width: 220px" @change="cargar(1)">
            <option value="">Todas las carreras</option>
            <option v-for="c in carrerasDisponibles" :key="c.id" :value="c.id">{{ c.nombre }}</option>
          </select>
          <select v-model="filtros.activo" class="input" style="max-width: 150px" @change="cargar(1)">
            <option value="">Todas</option>
            <option value="1">Activas</option>
            <option value="0">Inactivas</option>
          </select>
        </div>
        <button class="btn btn-primary" @click="abrirCrear">
          <Plus :size="16" style="margin-right: 4px" /> Nueva especialidad
        </button>
      </div>
    </div>

    <div v-if="errorMsg" class="alert a-danger mb4">{{ errorMsg }}</div>

    <!-- Tabla -->
    <div class="card">
      <div class="cp" style="overflow-x:auto">
        <table class="tt">
          <thead>
            <tr>
              <th>Clave</th>
              <th>Nombre</th>
              <th>Carrera</th>
              <th>Estado</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="cargando">
              <td colspan="5" class="sz-sm" style="text-align:center; color:var(--text-300)">Cargando…</td>
            </tr>
            <tr v-else-if="especialidades.length === 0">
              <td colspan="5" class="sz-sm" style="text-align:center; color:var(--text-300)">
                No se encontraron especialidades.
              </td>
            </tr>
            <tr v-for="e in especialidades" :key="e.id">
              <td>{{ e.clave }}</td>
              <td>{{ e.nombre }}</td>
              <td>{{ e.carrera?.nombre }}</td>
              <td>
                <span :class="['badge', e.activo ? 'b-verde' : 'b-gris']">
                  {{ e.activo ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td>
                <div class="flex ic g2u">
                  <IconButton title="Editar" @click="abrirEditar(e)">
                    <Pencil :size="16" />
                  </IconButton>
                  <IconButton
                    :title="e.activo ? 'Desactivar' : 'Activar'"
                    :variant="e.activo ? 'danger' : 'primary'"
                    @click="toggleActivo(e)"
                  >
                    <Power :size="16" />
                  </IconButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="paginacion.last_page > 1" class="flex jb ic" style="padding: var(--s4)">
        <span class="sz-sm" style="color:var(--text-300)">
          Página {{ paginacion.current_page }} de {{ paginacion.last_page }}
        </span>
        <div class="flex g2u">
          <button class="btn btn-outline btn-sm" :disabled="paginacion.current_page <= 1" @click="cargar(paginacion.current_page - 1)">
            Anterior
          </button>
          <button class="btn btn-outline btn-sm" :disabled="paginacion.current_page >= paginacion.last_page" @click="cargar(paginacion.current_page + 1)">
            Siguiente
          </button>
        </div>
      </div>
    </div>

    <!-- Modal crear/editar -->
    <Modal v-if="modalAbierto" :titulo="editando ? 'Editar especialidad' : 'Nueva especialidad'" @close="cerrarModal">
      <div v-if="erroresForm.length" class="alert a-danger mb4">
        <div v-for="(err, i) in erroresForm" :key="i">{{ err }}</div>
      </div>

      <div class="field">
        <label class="fl">Nombre<span class="req">*</span></label>
        <input v-model.trim="form.nombre" type="text" class="input" placeholder="Ej. TSU en Desarrollo de Software Multiplataforma" />
      </div>

      <div class="field">
        <label class="fl">Clave<span class="req">*</span></label>
        <input v-model.trim="form.clave" type="text" class="input" placeholder="Ej. DSM" />
      </div>

      <div class="field">
        <label class="fl">Carrera<span class="req">*</span></label>
        <select v-model="form.carrera_id" class="input">
          <option :value="null" disabled>Selecciona una carrera</option>
          <option v-for="c in carrerasDisponibles" :key="c.id" :value="c.id">{{ c.nombre }}</option>
        </select>
      </div>

      <template #footer>
        <button class="btn btn-ghost" @click="cerrarModal">Cancelar</button>
        <button class="btn btn-primary" :disabled="guardando" @click="guardar">
          {{ guardando ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Pencil, Power, Plus } from 'lucide-vue-next'
import Modal from '@/components/Modal.vue'
import IconButton from '@/components/IconButton.vue'
import api from '@/services/api'

const especialidades = ref([])
const carrerasDisponibles = ref([])
const paginacion = reactive({ current_page: 1, last_page: 1 })
const cargando = ref(false)
const errorMsg = ref('')

const filtros = reactive({ q: '', carrera_id: '', activo: '' })
let debounceTimer = null

const modalAbierto = ref(false)
const editando = ref(null)
const guardando = ref(false)
const erroresForm = ref([])

const form = reactive({ nombre: '', clave: '', carrera_id: null })

onMounted(async () => {
  await cargarCarrerasDisponibles()
  await cargar(1)
})

function buscarConDebounce() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => cargar(1), 400)
}

async function cargarCarrerasDisponibles() {
  const { data } = await api.get('/admin/especialidades/carreras-disponibles')
  carrerasDisponibles.value = data
}

async function cargar(pagina = 1) {
  cargando.value = true
  errorMsg.value = ''
  try {
    const { data } = await api.get('/admin/especialidades', {
      params: {
        q: filtros.q || undefined,
        carrera_id: filtros.carrera_id || undefined,
        activo: filtros.activo || undefined,
        page: pagina,
      },
    })
    especialidades.value = data.data
    paginacion.current_page = data.current_page
    paginacion.last_page = data.last_page
  } catch (e) {
    errorMsg.value = 'No se pudieron cargar las especialidades.'
  } finally {
    cargando.value = false
  }
}

function abrirCrear() {
  editando.value = null
  form.nombre = ''
  form.clave = ''
  form.carrera_id = carrerasDisponibles.value[0]?.id ?? null
  erroresForm.value = []
  modalAbierto.value = true
}

function abrirEditar(especialidad) {
  editando.value = especialidad
  form.nombre = especialidad.nombre
  form.clave = especialidad.clave
  form.carrera_id = especialidad.carrera_id
  erroresForm.value = []
  modalAbierto.value = true
}

function cerrarModal() {
  modalAbierto.value = false
}

async function guardar() {
  guardando.value = true
  erroresForm.value = []
  try {
    if (editando.value) {
      await api.put(`/admin/especialidades/${editando.value.id}`, form)
    } else {
      await api.post('/admin/especialidades', form)
    }
    modalAbierto.value = false
    await cargar(paginacion.current_page)
  } catch (e) {
    const errores = e.response?.data?.errors
    erroresForm.value = errores ? Object.values(errores).flat() : [e.response?.data?.message || 'No se pudo guardar.']
  } finally {
    guardando.value = false
  }
}

async function toggleActivo(especialidad) {
  try {
    await api.patch(`/admin/especialidades/${especialidad.id}/toggle-activo`)
    await cargar(paginacion.current_page)
  } catch (e) {
    errorMsg.value = 'No se pudo cambiar el estado.'
  }
}
</script>
