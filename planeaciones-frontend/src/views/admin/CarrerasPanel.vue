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
            style="max-width: 260px"
            @input="buscarConDebounce"
          />
          <select v-model="filtros.activo" class="input" style="max-width: 160px" @change="cargar(1)">
            <option value="">Todas</option>
            <option value="1">Activas</option>
            <option value="0">Inactivas</option>
          </select>
        </div>
        <button class="btn btn-primary" @click="abrirCrear">
          <Plus :size="16" style="margin-right: 4px" /> Nueva carrera
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
              <th>Director</th>
              <th>Especialidades</th>
              <th>Estado</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="cargando">
              <td colspan="6" class="sz-sm" style="text-align:center; color:var(--text-300)">Cargando…</td>
            </tr>
            <tr v-else-if="carreras.length === 0">
              <td colspan="6" class="sz-sm" style="text-align:center; color:var(--text-300)">
                No se encontraron carreras.
              </td>
            </tr>
            <tr v-for="c in carreras" :key="c.id">
              <td>{{ c.clave }}</td>
              <td>{{ c.nombre }}</td>
              <td>{{ c.director ? nombreCompleto(c.director) : '—' }}</td>
              <td>{{ c.especialidades_count }}</td>
              <td>
                <span :class="['badge', c.activo ? 'b-verde' : 'b-gris']">
                  {{ c.activo ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td>
                <div class="flex ic g2u">
                  <IconButton title="Ver detalle" @click="abrirDetalle(c)">
                    <Eye :size="16" />
                  </IconButton>
                  <IconButton title="Editar" @click="abrirEditar(c)">
                    <Pencil :size="16" />
                  </IconButton>
                  <IconButton
                    :title="c.activo ? 'Desactivar' : 'Activar'"
                    :variant="c.activo ? 'danger' : 'primary'"
                    @click="toggleActivo(c)"
                  >
                    <Power :size="16" />
                  </IconButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
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
    <Modal v-if="modalAbierto" :titulo="editando ? 'Editar carrera' : 'Nueva carrera'" @close="cerrarModal">
      <div v-if="erroresForm.length" class="alert a-danger mb4">
        <div v-for="(e, i) in erroresForm" :key="i">{{ e }}</div>
      </div>

      <div class="field">
        <label class="fl">Nombre<span class="req">*</span></label>
        <input v-model.trim="form.nombre" type="text" class="input" placeholder="Ej. Ingeniería en TI e Innovación Digital" />
      </div>

      <div class="field">
        <label class="fl">Clave<span class="req">*</span></label>
        <input v-model.trim="form.clave" type="text" class="input" placeholder="Ej. ITI" />
      </div>

      <div class="field">
        <label class="fl">Director</label>
        <select v-model="form.director_id" class="input">
          <option :value="null">Sin asignar</option>
          <option v-for="d in directoresDisponibles" :key="d.id" :value="d.id">
            {{ nombreCompleto(d) }}
          </option>
        </select>
        <p class="fh">Solo se listan usuarios con rol Director que no dirigen ya otra carrera.</p>
      </div>

      <template #footer>
        <button class="btn btn-ghost" @click="cerrarModal">Cancelar</button>
        <button class="btn btn-primary" :disabled="guardando" @click="guardar">
          {{ guardando ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </Modal>

    <!-- Modal de detalle: árbol Carrera → Especialidades → Asignaturas -->
    <Modal v-if="detalleAbierto" titulo="Detalle de la carrera" @close="detalleAbierto = false">
      <div v-if="cargandoDetalle" class="sz-sm" style="text-align:center; color:var(--text-300)">Cargando…</div>

      <div v-else-if="detalleCarrera" class="tree">
        <div class="tree-root">
          <GraduationCap :size="20" class="tree-icon" />
          <div class="tree-root-info">
            <strong>{{ detalleCarrera.nombre }}</strong>
            <span class="sz-xs tree-sub">
              Clave: {{ detalleCarrera.clave }} ·
              Director: {{ detalleCarrera.director ? nombreCompleto(detalleCarrera.director) : 'Sin asignar' }}
            </span>
          </div>
          <span :class="['badge', detalleCarrera.activo ? 'b-verde' : 'b-gris']">
            {{ detalleCarrera.activo ? 'Activa' : 'Inactiva' }}
          </span>
        </div>

        <div class="div"></div>

        <p v-if="detalleCarrera.especialidades.length === 0" class="sz-sm" style="color: var(--text-300)">
          Esta carrera no tiene especialidades registradas.
        </p>

        <div v-for="esp in detalleCarrera.especialidades" :key="esp.id" class="tree-node">
          <button class="tree-toggle" type="button" @click="toggleExpandido(esp.id)">
            <ChevronRight :size="14" class="tree-caret" :class="{ open: expandidos[esp.id] }" />
            <BookOpen :size="16" class="tree-icon" />
            <span>{{ esp.nombre }}</span>
            <span class="sz-xs tree-sub">({{ esp.clave }})</span>
            <span :class="['badge', esp.activo ? 'b-verde' : 'b-gris', 'tree-badge']">
              {{ esp.activo ? 'Activa' : 'Inactiva' }}
            </span>
          </button>

          <div v-if="expandidos[esp.id]" class="tree-children">
            <p v-if="esp.asignaturas.length === 0" class="sz-xs tree-sub tree-empty">
              Sin asignaturas registradas.
            </p>
            <div v-for="a in esp.asignaturas" :key="a.id" class="tree-leaf">
              <FileText :size="14" class="tree-icon" />
              <span>{{ a.nombre }}</span>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button class="btn btn-ghost" @click="detalleAbierto = false">Cerrar</button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { GraduationCap, BookOpen, FileText, ChevronRight, Eye, Pencil, Power, Plus } from 'lucide-vue-next'
import Modal from '@/components/Modal.vue'
import IconButton from '@/components/IconButton.vue'
import api from '@/services/api'

const carreras = ref([])
const paginacion = reactive({ current_page: 1, last_page: 1 })
const cargando = ref(false)
const errorMsg = ref('')

const filtros = reactive({ q: '', activo: '' })
let debounceTimer = null

const modalAbierto = ref(false)
const editando = ref(null)
const guardando = ref(false)
const erroresForm = ref([])
const directoresDisponibles = ref([])

const detalleAbierto = ref(false)
const detalleCarrera = ref(null)
const cargandoDetalle = ref(false)
const expandidos = reactive({})

const form = reactive({ nombre: '', clave: '', director_id: null })

onMounted(() => cargar(1))

function buscarConDebounce() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => cargar(1), 400)
}

async function cargar(pagina = 1) {
  cargando.value = true
  errorMsg.value = ''
  try {
    const { data } = await api.get('/admin/carreras', {
      params: { q: filtros.q || undefined, activo: filtros.activo || undefined, page: pagina },
    })
    carreras.value = data.data
    paginacion.current_page = data.current_page
    paginacion.last_page = data.last_page
  } catch (e) {
    errorMsg.value = 'No se pudieron cargar las carreras.'
  } finally {
    cargando.value = false
  }
}

async function cargarDirectoresDisponibles(exceptCarreraId = null) {
  const { data } = await api.get('/admin/carreras/directores-disponibles', {
    params: { except_carrera_id: exceptCarreraId || undefined },
  })
  directoresDisponibles.value = data
}

function abrirCrear() {
  editando.value = null
  form.nombre = ''
  form.clave = ''
  form.director_id = null
  erroresForm.value = []
  modalAbierto.value = true
  cargarDirectoresDisponibles()
}

function abrirEditar(carrera) {
  editando.value = carrera
  form.nombre = carrera.nombre
  form.clave = carrera.clave
  form.director_id = carrera.director?.id ?? null
  erroresForm.value = []
  modalAbierto.value = true
  cargarDirectoresDisponibles(carrera.id)
}

function cerrarModal() {
  modalAbierto.value = false
}

async function guardar() {
  guardando.value = true
  erroresForm.value = []
  try {
    if (editando.value) {
      await api.put(`/admin/carreras/${editando.value.id}`, form)
    } else {
      await api.post('/admin/carreras', form)
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

async function toggleActivo(carrera) {
  try {
    await api.patch(`/admin/carreras/${carrera.id}/toggle-activo`)
    await cargar(paginacion.current_page)
  } catch (e) {
    errorMsg.value = 'No se pudo cambiar el estado.'
  }
}

async function abrirDetalle(carrera) {
  detalleAbierto.value = true
  cargandoDetalle.value = true
  detalleCarrera.value = null
  try {
    const { data } = await api.get(`/admin/carreras/${carrera.id}`)
    detalleCarrera.value = data
    data.especialidades.forEach((esp) => {
      expandidos[esp.id] = true
    })
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'No se pudo cargar el detalle de la carrera.'
    detalleAbierto.value = false
  } finally {
    cargandoDetalle.value = false
  }
}

function toggleExpandido(especialidadId) {
  expandidos[especialidadId] = !expandidos[especialidadId]
}

function nombreCompleto(u) {
  return `${u.nombre} ${u.apellido_paterno}`
}
</script>

<style scoped>
.tree-root {
  display: flex;
  align-items: center;
  gap: var(--s3);
}
.tree-root-info {
  display: flex;
  flex-direction: column;
  flex: 1;
}
.tree-sub {
  color: var(--text-300);
}
.tree-icon {
  flex-shrink: 0;
  color: var(--text-500);
}
.tree-node {
  margin-top: var(--s2);
}
.tree-toggle {
  display: flex;
  align-items: center;
  gap: var(--s2);
  width: 100%;
  padding: var(--s2) var(--s2);
  border: none;
  background: none;
  cursor: pointer;
  border-radius: var(--r-sm);
  font-family: var(--font);
  font-size: var(--p-sm);
  color: var(--text-700);
  text-align: left;
}
.tree-toggle:hover {
  background: var(--bg-soft);
}
.tree-caret {
  transition: transform var(--tf) var(--ease);
  color: var(--text-300);
  flex-shrink: 0;
}
.tree-caret.open {
  transform: rotate(90deg);
}
.tree-badge {
  margin-left: auto;
}
.tree-children {
  margin-left: 34px;
  padding-left: var(--s3);
  border-left: 2px solid var(--border);
}
.tree-leaf {
  display: flex;
  align-items: center;
  gap: var(--s2);
  padding: 6px 0;
  font-size: var(--p-sm);
  color: var(--text-700);
}
.tree-empty {
  padding: 6px 0;
}
</style>
