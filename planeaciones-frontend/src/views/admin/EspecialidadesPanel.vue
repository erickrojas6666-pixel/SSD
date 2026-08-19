<template>
  <div class="dashboard-layout">
    
    <!-- ENCABEZADO DEL MÓDULO (Acento Cian) -->
    <header class="dash-header widget-contorno">
      <div class="header-icon teal-icon">
        <BookOpen :size="32" color="#06B6D4" stroke-width="2" />
      </div>
      <div class="header-info">
        <h2>Especialidades</h2>
        <p>Administra las ramas de especialización asignadas a cada carrera institucional.</p>
      </div>
      <div class="header-deco-dots"></div>
    </header>

    <div class="dash-grid">
      <!-- =========================================
           COLUMNA IZQUIERDA: CONTROLES Y FILTROS
      ========================================== -->
      <aside class="dash-controls widget-contorno">
        <div class="control-head">
          <Layers :size="18" color="#06B6D4" />
          <h3>Filtros de Búsqueda</h3>
        </div>
        
        <div class="div-soft"></div>

        <div class="filters-stack">
          <!-- Buscador -->
          <div class="field">
            <label class="fl"><Search :size="14" /> Buscar</label>
            <input
              v-model.trim="filtros.q"
              type="text"
              class="input input-contorno"
              placeholder="Nombre o clave..."
              @input="buscarConDebounce"
            />
          </div>

          <!-- Filtro de Carrera -->
          <div class="field">
            <label class="fl"><GraduationCap :size="14" /> Carrera</label>
            <select v-model="filtros.carrera_id" class="input input-contorno sel" @change="cargar(1)">
              <option value="">Todas las carreras</option>
              <option v-for="c in carrerasDisponibles" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
          </div>

          <!-- Filtro de Estado -->
          <div class="field">
            <label class="fl"><Filter :size="14" /> Estado</label>
            <select v-model="filtros.activo" class="input input-contorno sel" @change="cargar(1)">
              <option value="">Todas</option>
              <option value="1">Activas</option>
              <option value="0">Inactivas</option>
            </select>
          </div>
        </div>

        <div class="div-soft"></div>

        <!-- Botón de Acción Principal 3D (Cian) -->
        <button class="btn btn-add-max teal-btn" @click="abrirCrear">
          <Plus :size="20" stroke-width="3" />
          <span>Nueva Especialidad</span>
        </button>
      </aside>

      <!-- =========================================
           COLUMNA DERECHA: TABLA DE DATOS
      ========================================== -->
      <main class="dash-table-wrapper widget-contorno">
        
        <div v-if="errorMsg" class="alert a-danger mb4 mx-4 mt-4">{{ errorMsg }}</div>

        <div class="table-responsive">
          <table class="tt table-contorno">
            <thead>
              <tr>
                <th>Clave</th>
                <th>Nombre</th>
                <th>Carrera</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="cargando">
                <td colspan="5" class="empty-state">
                  <div class="spinner mx-auto mb2"></div>
                  Cargando información...
                </td>
              </tr>
              <tr v-else-if="especialidades.length === 0">
                <td colspan="5" class="empty-state">No se encontraron especialidades.</td>
              </tr>
              <tr
                v-for="e in especialidades"
                :key="e.id"
                class="row-open table-row-3d"
                title="Doble clic para editar"
                @dblclick="abrirEditar(e)"
              >
                <td class="font-bold teal-text">{{ e.clave }}</td>
                <td class="font-main">{{ e.nombre }}</td>
                <td class="sz-sm">{{ e.carrera?.nombre }}</td>
                <td>
                  <span :class="['badge', e.activo ? 'b-verde' : 'b-gris']">
                    {{ e.activo ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>
                <td>
                  <div class="flex ic g2u">
                    <IconButton title="Editar" class="icon-btn-3d" @click.stop="abrirEditar(e)">
                      <Pencil :size="16" />
                    </IconButton>
                    <IconButton
                      :title="e.activo ? 'Desactivar' : 'Activar'"
                      :variant="e.activo ? 'danger' : 'primary'"
                      class="icon-btn-3d"
                      @click.stop="toggleActivo(e)"
                    >
                      <Power :size="16" />
                    </IconButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer de Tabla -->
        <div class="table-footer">
          <p v-if="especialidades.length" class="tbl-hint">
            <Pencil :size="14" color="#06B6D4"/>
            Tip: doble clic en una fila para editar la especialidad al instante.
          </p>

          <!-- Paginación con Contornos -->
          <div v-if="paginacion.last_page > 1" class="pagination-wrapper">
            <span class="page-info">
              Página {{ paginacion.current_page }} de {{ paginacion.last_page }}
            </span>
            <div class="flex g2u">
              <button class="btn btn-outline btn-page-3d" :disabled="paginacion.current_page <= 1" @click="cargar(paginacion.current_page - 1)">
                Anterior
              </button>
              <button class="btn btn-outline btn-page-3d" :disabled="paginacion.current_page >= paginacion.last_page" @click="cargar(paginacion.current_page + 1)">
                Siguiente
              </button>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- MODAL CREAR/EDITAR (Estilo Soft UI Cian) -->
    <Modal v-if="modalAbierto" :titulo="editando ? 'Editar especialidad' : 'Nueva especialidad'" @close="cerrarModal">
      <div v-if="erroresForm.length" class="alert a-danger mb4">
        <div v-for="(err, i) in erroresForm" :key="i">{{ err }}</div>
      </div>

      <div class="field">
        <label class="fl">Nombre<span class="req">*</span></label>
        <input v-model.trim="form.nombre" type="text" class="input input-contorno" placeholder="Ej. TSU en Desarrollo de Software Multiplataforma" />
      </div>

      <div class="field">
        <label class="fl">Clave<span class="req">*</span></label>
        <input v-model.trim="form.clave" type="text" class="input input-contorno" placeholder="Ej. DSM" />
      </div>

      <div class="field">
        <label class="fl">Carrera<span class="req">*</span></label>
        <select v-model="form.carrera_id" class="input input-contorno sel">
          <option :value="null" disabled>Selecciona una carrera</option>
          <option v-for="c in carrerasDisponibles" :key="c.id" :value="c.id">{{ c.nombre }}</option>
        </select>
      </div>

      <template #footer>
        <button class="btn btn-ghost" @click="cerrarModal">Cancelar</button>
        <button class="btn btn-add-3d" :disabled="guardando" @click="guardar">
          {{ guardando ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Pencil, Power, Plus, Search, Filter, BookOpen, Layers, GraduationCap } from 'lucide-vue-next'
// ¡ELIMINADA LA IMPORTACIÓN DE APPSHELL DE AQUÍ!
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

<style scoped>
/* ==================================================
   LAYOUT DASHBOARD (GRID) - ESPECIALIDADES (CIAN/TEAL)
================================================== */
.dashboard-layout {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* WIDGETS CON CONTORNO FUERTE CIAN */
.widget-contorno {
  background: #FFFFFF;
  border: 3px solid rgba(6, 182, 212, 0.15); 
  border-radius: var(--r-xl);
  box-shadow: 0 10px 30px -10px rgba(6, 182, 212, 0.15);
  position: relative;
  overflow: hidden;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.widget-contorno:hover {
  border-color: rgba(6, 182, 212, 0.3);
  box-shadow: 0 15px 35px -10px rgba(6, 182, 212, 0.25);
}

/* ── ENCABEZADO SUPERIOR ── */
.dash-header {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 32px 40px;
  background: linear-gradient(90deg, #FFFFFF 0%, #ECFEFF 100%);
}
.header-icon {
  width: 64px;
  height: 64px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  transform: rotate(-3deg);
  transition: transform 0.3s var(--ease-spring);
}
.dash-header:hover .header-icon {
  transform: rotate(5deg) scale(1.05);
}
.teal-icon {
  background: rgba(6, 182, 212, 0.1);
  border: 2px solid rgba(6, 182, 212, 0.2);
  box-shadow: 0 6px 15px rgba(6, 182, 212, 0.15);
}
.header-info h2 {
  font-family: 'Sora', sans-serif;
  font-size: 28px;
  font-weight: 800;
  color: var(--text-900);
  margin-bottom: 4px;
}
.header-info p {
  color: var(--text-500);
  font-size: 15px;
  margin: 0;
}
.header-deco-dots {
  position: absolute;
  right: 20px;
  top: 20px;
  width: 60px;
  height: 60px;
  background-image: radial-gradient(rgba(6, 182, 212, 0.2) 2px, transparent 2px);
  background-size: 10px 10px;
  opacity: 0.5;
}

/* ── GRID PRINCIPAL ── */
.dash-grid {
  display: grid;
  grid-template-columns: 310px 1fr;
  gap: 24px;
  align-items: start;
}

/* ── PANEL DE CONTROL (Izquierda) ── */
.dash-controls {
  padding: 28px 24px;
  display: flex;
  flex-direction: column;
}

.control-head {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}
.control-head h3 {
  font-family: 'Sora', sans-serif;
  font-size: 18px;
  font-weight: 700;
  color: var(--text-900);
}

.filters-stack {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* Inputs con Contorno Cian */
.input-contorno {
  padding: 12px 16px;
  border-radius: var(--r-md);
  border: 2px solid rgba(6, 182, 212, 0.2);
  background: var(--bg-page);
  width: 100%;
  transition: all 0.2s ease;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
.input-contorno:focus {
  background: #FFFFFF;
  border-color: #06B6D4;
  box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15);
}
.fl {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-700);
  font-weight: 700;
}

.div-soft {
  height: 2px;
  background: var(--border-soft);
  margin: 20px 0;
  border-radius: var(--r-pill);
}

/* Botón gigante 3D (Cian) */
.btn-add-max {
  width: 100%;
  padding: 16px;
  border-radius: var(--r-lg);
  font-size: 16px;
  font-weight: 800;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  transform: translateY(-4px);
}
.teal-btn {
  background: #06B6D4;
  color: #FFFFFF;
  border: none;
  box-shadow: 0 6px 0 #0891B2, 0 10px 20px rgba(6, 182, 212, 0.3);
}
.teal-btn:hover {
  background: #0891B2;
  transform: translateY(-6px);
  box-shadow: 0 8px 0 #0E7490, 0 14px 25px rgba(6, 182, 212, 0.4);
}
.teal-btn:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 #0E7490;
}

/* Botones para los modales (Cian) */
.btn-add-3d {
  background: #06B6D4;
  color: #FFFFFF;
  border: none;
  border-radius: var(--r-pill);
  padding: 11px 24px;
  font-weight: 600;
  box-shadow: 0 4px 0 #0891B2, 0 8px 15px rgba(6, 182, 212, 0.3);
  transform: translateY(-2px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  cursor: pointer;
}
.btn-add-3d:hover {
  background: #0891B2;
  transform: translateY(-4px);
  box-shadow: 0 6px 0 #0E7490, 0 12px 20px rgba(6, 182, 212, 0.4);
}
.btn-add-3d:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 #0E7490;
}
.btn-add-3d:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: translateY(0);
  box-shadow: 0 0 0 transparent;
}

/* ── CONTENEDOR DE LA TABLA (Derecha) ── */
.dash-table-wrapper {
  display: flex;
  flex-direction: column;
  padding: 0; 
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.table-contorno {
  width: 100%;
}
.table-contorno th {
  padding: 20px 24px;
  background: rgba(6, 182, 212, 0.05);
  color: #0891B2;
  border-bottom: 2px solid rgba(6, 182, 212, 0.15);
}
.table-contorno td {
  padding: 16px 24px;
  border-bottom: 1px solid var(--border-soft);
}

/* Estilos de las filas 3D */
.table-row-3d td {
  transition: all 0.2s ease;
}
.table-row-3d:hover td {
  background: rgba(6, 182, 212, 0.05); /* Hover con tinte cian */
}
.font-bold { font-weight: 800; }
.teal-text { color: #06B6D4; } 
.font-main { font-weight: 600; color: var(--text-900); }

.table-footer {
  padding: 24px;
  background: #FFFFFF;
  border-top: 2px solid rgba(6, 182, 212, 0.15);
}

/* Paginación Contorno 3D Cian */
.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 16px;
}
.page-info {
  font-weight: 700;
  color: var(--text-500);
  background: var(--bg-page);
  padding: 6px 14px;
  border-radius: var(--r-pill);
  border: 2px solid rgba(6, 182, 212, 0.1);
}
.btn-page-3d {
  border-radius: var(--r-pill);
  border: 2px solid rgba(6, 182, 212, 0.2);
  color: #06B6D4;
  background: transparent;
  font-weight: 700;
  box-shadow: 0 4px 0 rgba(6, 182, 212, 0.15);
  transform: translateY(-2px);
  transition: all 0.15s ease;
}
.btn-page-3d:hover:not(:disabled) {
  background: #06B6D4;
  color: #FFFFFF;
  border-color: #06B6D4;
  box-shadow: 0 6px 0 #0891B2;
  transform: translateY(-4px);
}
.btn-page-3d:active:not(:disabled) {
  transform: translateY(2px);
  box-shadow: 0 0 0 #0891B2;
}
.btn-page-3d:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: translateY(0);
  box-shadow: 0 0 0 transparent;
}

.empty-state {
  text-align: center;
  color: var(--text-300);
  padding: 32px !important;
  font-weight: 600;
}

/* Espaciados */
.mx-4 { margin-left: 24px; margin-right: 24px; }
.mt-4 { margin-top: 24px; }

/* Adaptación Responsiva */
@media (max-width: 1024px) {
  .dash-grid {
    grid-template-columns: 1fr; 
  }
  .btn-add-max {
    flex-direction: row;
    justify-content: center;
    padding: 12px;
  }
}
</style>