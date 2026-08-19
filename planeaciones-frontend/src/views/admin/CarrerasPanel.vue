<template>
  <div class="dashboard-layout">
    
    <!-- ENCABEZADO DEL MÓDULO -->
    <header class="dash-header widget-contorno">
      <div class="header-icon">
        <GraduationCap :size="32" color="#008C3D" stroke-width="2" />
      </div>
      <div class="header-info">
        <h2>Carreras y Especialidades</h2>
        <p>Gestiona, consulta y controla los catálogos académicos desde este panel.</p>
      </div>
      <!-- Decoración caricaturesca de fondo -->
      <div class="header-deco"></div>
    </header>

    <div class="dash-grid">
      <!-- =========================================
           COLUMNA IZQUIERDA: PANEL DE CONTROL
      ========================================== -->
      <aside class="dash-controls widget-contorno">
        <div class="control-head">
          <Settings :size="18" class="text-verde" />
          <h3>Panel de Control</h3>
        </div>
        
        <div class="div-soft"></div>

        <!-- Buscador -->
        <div class="field">
          <label class="fl">
            <Search :size="14" /> Buscar carrera
          </label>
          <input
            v-model.trim="filtros.q"
            type="text"
            class="input input-contorno"
            placeholder="Nombre o clave..."
            @input="buscarConDebounce"
          />
        </div>

        <!-- Filtro de Estado -->
        <div class="field">
          <label class="fl">
            <Filter :size="14" /> Filtrar estado
          </label>
          <select v-model="filtros.activo" class="input input-contorno sel" @change="cargar(1)">
            <option value="">Todas las carreras</option>
            <option value="1">Solo Activas</option>
            <option value="0">Solo Inactivas</option>
          </select>
        </div>

        <div class="div-soft"></div>

        <!-- Botón de Acción Principal -->
        <button class="btn btn-primary btn-add-max" @click="abrirCrear">
          <Plus :size="20" stroke-width="3" />
          <span>Nueva Carrera</span>
        </button>
      </aside>

      <!-- =========================================
           COLUMNA DERECHA: TABLA DE DATOS
      ========================================== -->
      <main class="dash-table-wrapper widget-contorno">
        
        <div v-if="errorMsg" class="alert a-danger mb4">{{ errorMsg }}</div>

        <div class="table-responsive">
          <table class="tt table-contorno">
            <thead>
              <tr>
                <th>Clave</th>
                <th>Nombre</th>
                <th>Director</th>
                <th>Especialidades</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="cargando">
                <td colspan="6" class="empty-state">
                  <div class="spinner mx-auto mb2"></div>
                  Cargando información...
                </td>
              </tr>
              <tr v-else-if="carreras.length === 0">
                <td colspan="6" class="empty-state">No se encontraron carreras.</td>
              </tr>
              <tr
                v-for="c in carreras"
                :key="c.id"
                class="row-open table-row-3d"
                title="Doble clic para ver el detalle"
                @dblclick="abrirDetalle(c)"
              >
                <td class="font-bold">{{ c.clave }}</td>
                <td class="font-main">{{ c.nombre }}</td>
                <td>{{ c.director ? nombreCompleto(c.director) : '—' }}</td>
                <td>
                  <span class="pill-counter">{{ c.especialidades_count }}</span>
                </td>
                <td>
                  <span :class="['badge', c.activo ? 'b-verde' : 'b-gris']">
                    {{ c.activo ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>
                <td>
                  <div class="flex ic g2u">
                    <IconButton title="Ver detalle" class="icon-btn-3d" @click.stop="abrirDetalle(c)">
                      <Eye :size="16" />
                    </IconButton>
                    <IconButton title="Editar" class="icon-btn-3d" @click.stop="abrirEditar(c)">
                      <Pencil :size="16" />
                    </IconButton>
                    <IconButton
                      :title="c.activo ? 'Desactivar' : 'Activar'"
                      :variant="c.activo ? 'danger' : 'primary'"
                      class="icon-btn-3d"
                      @click.stop="toggleActivo(c)"
                    >
                      <Power :size="16" />
                    </IconButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer de Tabla (Tip y Paginación) -->
        <div class="table-footer">
          <p v-if="carreras.length" class="tbl-hint">
            <BookOpen :size="14" style="color: var(--uth-verde)"/>
            Tip: doble clic en una fila para ver el detalle de la carrera.
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

    <!-- MODAL CREAR/EDITAR (Se mantiene igual en funcionalidad, hereda los estilos globales) -->
    <Modal v-if="modalAbierto" :titulo="editando ? 'Editar carrera' : 'Nueva carrera'" @close="cerrarModal">
      <div v-if="erroresForm.length" class="alert a-danger mb4">
        <div v-for="(e, i) in erroresForm" :key="i">{{ e }}</div>
      </div>
      <div class="field">
        <label class="fl">Nombre<span class="req">*</span></label>
        <input v-model.trim="form.nombre" type="text" class="input input-contorno" placeholder="Ej. Ingeniería en TI e Innovación Digital" />
      </div>
      <div class="field">
        <label class="fl">Clave<span class="req">*</span></label>
        <input v-model.trim="form.clave" type="text" class="input input-contorno" placeholder="Ej. ITI" />
      </div>
      <div class="field">
        <label class="fl">Director</label>
        <select v-model="form.director_id" class="input input-contorno sel">
          <option :value="null">Sin asignar</option>
          <option v-for="d in directoresDisponibles" :key="d.id" :value="d.id">
            {{ nombreCompleto(d) }}
          </option>
        </select>
        <p class="fh">Solo se listan usuarios con rol Director que no dirigen ya otra carrera.</p>
      </div>
      <template #footer>
        <button class="btn btn-ghost" @click="cerrarModal">Cancelar</button>
        <button class="btn btn-primary btn-add-3d" :disabled="guardando" @click="guardar">
          {{ guardando ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </Modal>

    <!-- MODAL DE DETALLE -->
    <Modal v-if="detalleAbierto" titulo="Detalle de la carrera" @close="detalleAbierto = false">
      <div v-if="cargandoDetalle" class="empty-state sz-sm">Cargando…</div>
      <div v-else-if="detalleCarrera" class="tree">
        <div class="tree-root-3d">
          <div class="root-icon-box">
            <GraduationCap :size="24" color="#008C3D" stroke-width="2.5" />
          </div>
          <div class="tree-root-info">
            <strong class="root-title">{{ detalleCarrera.nombre }}</strong>
            <span class="sz-xs tree-sub">
              <strong>Clave:</strong> {{ detalleCarrera.clave }} &nbsp;•&nbsp;
              <strong>Director:</strong> {{ detalleCarrera.director ? nombreCompleto(detalleCarrera.director) : 'Sin asignar' }}
            </span>
          </div>
          <span :class="['badge', detalleCarrera.activo ? 'b-verde' : 'b-gris']">
            {{ detalleCarrera.activo ? 'Activa' : 'Inactiva' }}
          </span>
        </div>
        <div class="div-soft"></div>
        <p v-if="detalleCarrera.especialidades.length === 0" class="empty-state sz-sm">
          Esta carrera no tiene especialidades registradas.
        </p>
        <div v-for="esp in detalleCarrera.especialidades" :key="esp.id" class="tree-node">
          <button class="tree-toggle-3d" type="button" @click="toggleExpandido(esp.id)">
            <ChevronRight :size="16" class="tree-caret" :class="{ open: expandidos[esp.id] }" />
            <div class="icon-wrap-small">
              <BookOpen :size="16" color="#008C3D" />
            </div>
            <span class="node-title">{{ esp.nombre }}</span>
            <span class="sz-xs tree-sub node-clave">({{ esp.clave }})</span>
            <span :class="['badge', esp.activo ? 'b-verde' : 'b-gris', 'tree-badge']">
              {{ esp.activo ? 'Activa' : 'Inactiva' }}
            </span>
          </button>
          <div v-if="expandidos[esp.id]" class="tree-children-3d">
            <p v-if="esp.asignaturas.length === 0" class="empty-state sz-xs mt3">
              Sin asignaturas registradas.
            </p>
            <div class="leaf-grid">
              <div v-for="a in esp.asignaturas" :key="a.id" class="tree-leaf-3d">
                <FileText :size="14" color="#475569" />
                <span>{{ a.nombre }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <button class="btn btn-primary btn-add-3d" @click="detalleAbierto = false" style="width: 100%; justify-content: center;">
          Entendido
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { GraduationCap, BookOpen, FileText, ChevronRight, Eye, Pencil, Power, Plus, Search, Filter, Settings } from 'lucide-vue-next'
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
/* ==================================================
   NUEVO LAYOUT DASHBOARD (GRID)
================================================== */
.dashboard-layout {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* WIDGETS CON CONTORNO FUERTE */
.widget-contorno {
  background: #FFFFFF;
  /* Contorno pronunciado solicitado */
  border: 3px solid rgba(0, 182, 79, 0.15); 
  border-radius: var(--r-xl);
  box-shadow: 0 10px 30px -10px rgba(0, 182, 79, 0.15);
  position: relative;
  overflow: hidden;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.widget-contorno:hover {
  border-color: rgba(0, 182, 79, 0.3);
  box-shadow: 0 15px 35px -10px rgba(0, 182, 79, 0.25);
}

/* ── ENCABEZADO SUPERIOR ── */
.dash-header {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 32px 40px;
}
.header-icon {
  width: 64px;
  height: 64px;
  background: rgba(0, 182, 79, 0.1);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid rgba(0, 182, 79, 0.2);
  transform: rotate(-3deg);
  box-shadow: 0 6px 15px rgba(0, 182, 79, 0.1);
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

/* ── GRID PRINCIPAL (Panel Izq vs Tabla Der) ── */
.dash-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
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
  margin-bottom: 16px;
}
.control-head h3 {
  font-family: 'Sora', sans-serif;
  font-size: 18px;
  font-weight: 700;
  color: var(--text-900);
}
.text-verde { color: var(--uth-verde); }

/* Inputs con Contorno */
.input-contorno {
  padding: 12px 16px;
  border-radius: var(--r-md);
  border: 2px solid rgba(0, 182, 79, 0.2);
  background: var(--bg-page);
  width: 100%;
  transition: all 0.2s ease;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
.input-contorno:focus {
  background: #FFFFFF;
  border-color: var(--uth-verde);
  box-shadow: 0 0 0 4px var(--uth-verde-bg);
}
.fl {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--text-700);
  font-weight: 700;
}

.div-soft {
  height: 2px;
  background: var(--border-soft);
  margin: 20px 0;
  border-radius: var(--r-pill);
}

/* Botón gigante de "Nueva Carrera" */
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
  background: var(--uth-verde);
  box-shadow: 0 6px 0 #007734, 0 10px 20px rgba(0, 182, 79, 0.3);
  transform: translateY(-4px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.btn-add-max:hover {
  transform: translateY(-6px);
  box-shadow: 0 8px 0 #007734, 0 14px 25px rgba(0, 182, 79, 0.4);
}
.btn-add-max:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 #007734;
}

/* ── CONTENEDOR DE LA TABLA (Derecha) ── */
.dash-table-wrapper {
  display: flex;
  flex-direction: column;
  padding: 0; /* Quitamos el padding para que la tabla llegue a los bordes */
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
  background: rgba(0, 182, 79, 0.05); /* Cabecera tintada de verde */
  color: var(--uth-verde-deep);
  border-bottom: 2px solid rgba(0, 182, 79, 0.15);
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
  background: rgba(0, 182, 79, 0.03);
}
.font-bold { font-weight: 800; color: var(--uth-verde-hover); }
.font-main { font-weight: 600; color: var(--text-900); }

.pill-counter {
  background: rgba(0, 182, 79, 0.1);
  border: 1px solid rgba(0, 182, 79, 0.2);
  color: var(--uth-verde-deep);
  font-weight: 800;
  padding: 4px 12px;
  border-radius: var(--r-pill);
  font-size: 12px;
}

.table-footer {
  padding: 24px;
  background: #FFFFFF;
  border-top: 2px solid rgba(0, 182, 79, 0.15);
}

/* Paginación Contorno 3D */
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
  border: 2px solid rgba(0, 182, 79, 0.1);
}
.btn-page-3d {
  border-radius: var(--r-pill);
  border: 2px solid rgba(0, 182, 79, 0.2);
  color: var(--uth-verde);
  font-weight: 700;
  box-shadow: 0 4px 0 rgba(0, 182, 79, 0.15);
  transform: translateY(-2px);
}
.btn-page-3d:hover:not(:disabled) {
  background: var(--uth-verde);
  color: #FFFFFF;
  border-color: var(--uth-verde);
  box-shadow: 0 6px 0 #007734;
  transform: translateY(-4px);
}
.btn-page-3d:active:not(:disabled) {
  transform: translateY(2px);
  box-shadow: 0 0 0 #007734;
}

/* Adaptación Responsiva */
@media (max-width: 1024px) {
  .dash-grid {
    grid-template-columns: 1fr; /* Apilamos panel arriba y tabla abajo */
  }
  .btn-add-max {
    flex-direction: row;
    justify-content: center;
    padding: 12px;
  }
}

/* ── MODALES DETALLE (Heredado de la configuración anterior) ── */
.tree-root-3d {
  display: flex; align-items: center; gap: var(--s4);
  background: var(--bg-page); padding: 20px; border-radius: var(--r-xl);
  border: 2px solid rgba(0, 182, 79, 0.2); box-shadow: inset 0 3px 6px rgba(0,0,0,0.03);
}
.root-icon-box {
  width: 54px; height: 54px; background: #FFFFFF; border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 6px 15px rgba(0, 182, 79, 0.2); flex-shrink: 0;
}
.tree-root-info { display: flex; flex-direction: column; flex: 1; }
.root-title { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: var(--text-900); margin-bottom: 4px; }

.tree-node { margin-bottom: 16px; }
.tree-toggle-3d {
  display: flex; align-items: center; gap: 12px; width: 100%; padding: 14px 18px;
  border: 2px solid rgba(0, 182, 79, 0.15); background: #FFFFFF; cursor: pointer;
  border-radius: var(--r-pill); font-family: var(--font); text-align: left;
  box-shadow: 0 4px 0 rgba(0, 182, 79, 0.1); transform: translateY(-2px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.tree-toggle-3d:hover {
  border-color: rgba(0, 182, 79, 0.3); box-shadow: 0 6px 0 rgba(0, 182, 79, 0.2); transform: translateY(-4px);
}
.tree-toggle-3d:active { transform: translateY(2px); box-shadow: 0 0 0 rgba(0, 182, 79, 0.2); }

.icon-wrap-small {
  width: 32px; height: 32px; background: var(--bg-page); border-radius: 10px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.node-title { font-weight: 700; color: var(--text-900); font-size: 15px; }
.tree-caret { transition: transform 0.2s var(--ease-spring); color: var(--text-300); flex-shrink: 0; }
.tree-caret.open { transform: rotate(90deg); color: var(--uth-verde); }
.tree-badge { margin-left: auto; }

.tree-children-3d { margin-top: 12px; margin-left: 26px; padding-left: 20px; border-left: 3px solid rgba(0, 182, 79, 0.15); }
.leaf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 12px; }
.tree-leaf-3d {
  display: flex; align-items: center; gap: 10px; padding: 10px 14px;
  background: var(--bg-soft); border: 2px solid var(--border-soft); border-radius: var(--r-pill);
  font-size: 13px; font-weight: 600; color: var(--text-700); transition: transform 0.2s ease, background 0.2s ease;
}
.tree-leaf-3d:hover {
  background: #FFFFFF; border-color: rgba(0, 182, 79, 0.2); transform: scale(1.02); box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
</style>