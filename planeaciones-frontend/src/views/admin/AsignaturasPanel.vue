<template>
  <div class="dashboard-layout">
    
    <!-- ENCABEZADO DEL MÓDULO (Variación con acento Violeta) -->
    <header class="dash-header widget-contorno">
      <div class="header-icon violet-icon">
        <Library :size="32" color="#8B5CF6" stroke-width="2" />
      </div>
      <div class="header-info">
        <h2>Asignaturas</h2>
        <p>Gestiona las materias, planes de estudio (PDF) y carga masiva del currículo.</p>
      </div>
      <div class="header-deco-dots"></div>
    </header>

    <div class="dash-grid">
      <!-- =========================================
           COLUMNA IZQUIERDA: CONTROLES Y FILTROS
      ========================================== -->
      <aside class="dash-controls widget-contorno">
        <div class="control-head">
          <Layers :size="18" color="#8B5CF6" />
          <h3>Panel de Control</h3>
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

          <!-- Filtro de Cuatrimestre -->
          <div class="field">
            <label class="fl"><Calendar :size="14" /> Cuatrimestre</label>
            <select v-model="filtros.cuatrimestre_id" class="input input-contorno sel" @change="cargar(1)">
              <option value="">Todos los cuatrimestres</option>
              <option v-for="c in cuatrimestres" :key="c.id" :value="c.id">
                {{ c.nombre || `Cuatrimestre ${c.numero}` }}
              </option>
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

        <!-- Botones de Acción 3D Apilados -->
        <div class="action-buttons-stack">
          <button class="btn btn-primary btn-add-max violet-btn" @click="abrirCrear">
            <Plus :size="20" stroke-width="3" />
            <span>Nueva Asignatura</span>
          </button>
          
          <button class="btn btn-outline btn-massive-3d" @click="abrirMasivo">
            <ListPlus :size="18" stroke-width="2.5" />
            <span>Carga Masiva</span>
          </button>
        </div>
      </aside>

      <!-- =========================================
           COLUMNA DERECHA: TABLA DE DATOS
      ========================================== -->
      <main class="dash-table-wrapper widget-contorno">
        
        <div v-if="errorMsg" class="alert a-danger mx-4 mt-4">{{ errorMsg }}</div>
        <div v-if="successMsg" class="alert a-success mx-4 mt-4">{{ successMsg }}</div>

        <div class="table-responsive">
          <table class="tt table-contorno">
            <thead>
              <tr>
                <th>Clave</th>
                <th>Nombre</th>
                <th>Cuatrimestre</th>
                <th>Especialidades</th>
                <th>Plan (PDF)</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="cargando">
                <td colspan="7" class="empty-state">
                  <div class="spinner mx-auto mb2"></div>
                  Cargando información...
                </td>
              </tr>
              <tr v-else-if="asignaturas.length === 0">
                <td colspan="7" class="empty-state">No se encontraron asignaturas.</td>
              </tr>
              <tr
                v-for="a in asignaturas"
                :key="a.id"
                class="row-open table-row-3d"
                title="Doble clic para editar"
                @dblclick="abrirEditar(a)"
              >
                <td class="font-bold violet-text">{{ a.clave }}</td>
                <td class="font-main">{{ a.nombre }}</td>
                <td class="sz-sm">{{ a.cuatrimestre?.nombre || `Cuatrimestre ${a.cuatrimestre?.numero}` }}</td>
                <td>
                  <div class="flex fw g2u">
                    <span v-for="esp in a.especialidades" :key="esp.id" class="badge b-azul">{{ esp.clave }}</span>
                  </div>
                </td>
                <td>
                  <a v-if="a.plan_estudio_url" :href="a.plan_estudio_url" target="_blank" rel="noopener" class="plan-link-3d" @click.stop>
                    <FileText :size="14" /> Ver PDF
                  </a>
                  <span v-else class="sz-xs" style="color:var(--text-300); font-weight:600;">Sin archivo</span>
                </td>
                <td>
                  <span :class="['badge', a.activo ? 'b-verde' : 'b-gris']">
                    {{ a.activo ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>
                <td>
                  <div class="flex ic g2u">
                    <IconButton title="Editar" class="icon-btn-3d" @click.stop="abrirEditar(a)">
                      <Pencil :size="16" />
                    </IconButton>
                    <IconButton
                      :title="a.activo ? 'Desactivar' : 'Activar'"
                      :variant="a.activo ? 'danger' : 'primary'"
                      class="icon-btn-3d"
                      @click.stop="toggleActivo(a)"
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
          <p v-if="asignaturas.length" class="tbl-hint">
            <Pencil :size="14" color="#8B5CF6"/>
            Tip: doble clic en una fila para editar la asignatura.
          </p>

          <!-- Paginación -->
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

    <!-- Modales Externos (Heredan los estilos globales automáticamente) -->
    <AsignaturaFormModal
      v-if="formAbierto"
      :asignatura="editando"
      :cuatrimestres="cuatrimestres"
      :especialidades="especialidades"
      @close="formAbierto = false"
      @guardada="onGuardada"
    />

    <AsignaturaMasivaModal
      v-if="masivoAbierto"
      :cuatrimestres="cuatrimestres"
      :especialidades="especialidades"
      @close="masivoAbierto = false"
      @completado="onMasivoCompletado"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Plus, ListPlus, Pencil, Power, FileText, Search, Filter, Calendar, Layers, Library } from 'lucide-vue-next'
import IconButton from '@/components/IconButton.vue'
import AsignaturaFormModal from './AsignaturaFormModal.vue'
import AsignaturaMasivaModal from './AsignaturaMasivaModal.vue'
import api from '@/services/api'

const asignaturas = ref([])
const cuatrimestres = ref([])
const especialidades = ref([])
const paginacion = reactive({ current_page: 1, last_page: 1 })
const cargando = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

const filtros = reactive({ q: '', cuatrimestre_id: '', activo: '' })
let debounceTimer = null

const formAbierto = ref(false)
const editando = ref(null)
const masivoAbierto = ref(false)

onMounted(async () => {
  await cargarCatalogos()
  await cargar(1)
})

async function cargarCatalogos() {
  const { data } = await api.get('/admin/asignaturas/catalogos')
  cuatrimestres.value = data.cuatrimestres
  especialidades.value = data.especialidades
}

function buscarConDebounce() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => cargar(1), 400)
}

async function cargar(pagina = 1) {
  cargando.value = true
  errorMsg.value = ''
  try {
    const { data } = await api.get('/admin/asignaturas', {
      params: {
        q: filtros.q || undefined,
        cuatrimestre_id: filtros.cuatrimestre_id || undefined,
        activo: filtros.activo || undefined,
        page: pagina,
      },
    })
    asignaturas.value = data.data
    paginacion.current_page = data.current_page
    paginacion.last_page = data.last_page
  } catch (e) {
    errorMsg.value = 'No se pudieron cargar las asignaturas.'
  } finally {
    cargando.value = false
  }
}

function abrirCrear() {
  editando.value = null
  formAbierto.value = true
}

function abrirEditar(asignatura) {
  editando.value = asignatura
  formAbierto.value = true
}

function abrirMasivo() {
  masivoAbierto.value = true
}

async function onGuardada() {
  formAbierto.value = false
  successMsg.value = 'Asignatura guardada correctamente.'
  await cargar(paginacion.current_page)
}

async function onMasivoCompletado(mensaje) {
  masivoAbierto.value = false
  successMsg.value = mensaje
  await cargar(1)
}

async function toggleActivo(asignatura) {
  try {
    await api.patch(`/admin/asignaturas/${asignatura.id}/toggle-activo`)
    await cargar(paginacion.current_page)
  } catch (e) {
    errorMsg.value = 'No se pudo cambiar el estado.'
  }
}
</script>

<style scoped>
/* ==================================================
   NUEVO LAYOUT DASHBOARD (GRID) - ASIGNATURAS
================================================== */
.dashboard-layout {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* WIDGETS CON CONTORNO FUERTE */
.widget-contorno {
  background: #FFFFFF;
  border: 3px solid rgba(0, 182, 79, 0.15); 
  border-radius: var(--r-xl);
  box-shadow: 0 10px 30px -10px rgba(0, 182, 79, 0.15);
  position: relative;
  overflow: hidden;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.widget-contorno:hover {
  border-color: rgba(139, 92, 246, 0.3); /* Contorno violeta al hacer hover */
  box-shadow: 0 15px 35px -10px rgba(139, 92, 246, 0.25);
}

/* ── ENCABEZADO SUPERIOR (Variación Violeta) ── */
.dash-header {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 32px 40px;
  background: linear-gradient(90deg, #FFFFFF 0%, #F5F3FF 100%);
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
.violet-icon {
  background: rgba(139, 92, 246, 0.1);
  border: 2px solid rgba(139, 92, 246, 0.2);
  box-shadow: 0 6px 15px rgba(139, 92, 246, 0.15);
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
  background-image: radial-gradient(rgba(139, 92, 246, 0.2) 2px, transparent 2px);
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
  border-color: #8B5CF6; /* Foco Violeta */
  box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
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

/* ── BOTONES DE ACCIÓN 3D ── */
.action-buttons-stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Botón Violeta Principal */
.btn-add-max {
  width: 100%;
  padding: 16px;
  border-radius: var(--r-lg);
  font-size: 15.5px;
  font-weight: 800;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  color: #FFFFFF;
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  transform: translateY(-4px);
}
.violet-btn {
  background: #8B5CF6;
  border: none;
  box-shadow: 0 6px 0 #5B21B6, 0 10px 20px rgba(139, 92, 246, 0.3);
}
.violet-btn:hover {
  background: #7C3AED;
  transform: translateY(-6px);
  box-shadow: 0 8px 0 #5B21B6, 0 14px 25px rgba(139, 92, 246, 0.4);
}
.violet-btn:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 #5B21B6;
}

/* Botón Outline Carga Masiva */
.btn-massive-3d {
  width: 100%;
  padding: 12px;
  border-radius: var(--r-lg);
  font-size: 14.5px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #FFFFFF;
  color: #8B5CF6;
  border: 2px solid #8B5CF6;
  box-shadow: 0 4px 0 #8B5CF6;
  transform: translateY(-2px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.btn-massive-3d:hover {
  background: #F5F3FF;
  transform: translateY(-4px);
  box-shadow: 0 6px 0 #8B5CF6, 0 8px 15px rgba(139, 92, 246, 0.2);
}
.btn-massive-3d:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 #8B5CF6;
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
  background: rgba(139, 92, 246, 0.05); /* Tintado violeta sutil */
  color: #5B21B6;
  border-bottom: 2px solid rgba(139, 92, 246, 0.15);
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
  background: rgba(139, 92, 246, 0.04);
}
.font-bold { font-weight: 800; }
.violet-text { color: #8B5CF6; }
.font-main { font-weight: 600; color: var(--text-900); }

/* Enlace a PDF tipo pastilla 3D */
.plan-link-3d {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--bg-soft);
  color: var(--text-700);
  border: 1px solid var(--border);
  padding: 6px 12px;
  border-radius: var(--r-pill);
  font-size: 12.5px;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 2px 0 var(--border-med);
  transition: all 0.15s ease;
}
.plan-link-3d:hover {
  background: #F5F3FF;
  color: #8B5CF6;
  border-color: #8B5CF6;
  box-shadow: 0 3px 0 #8B5CF6;
  transform: translateY(-1px);
}
.plan-link-3d:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 transparent;
}

/* Footer de Tabla */
.table-footer {
  padding: 24px;
  background: #FFFFFF;
  border-top: 2px solid rgba(139, 92, 246, 0.15);
}

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

.empty-state {
  text-align: center;
  color: var(--text-300);
  padding: 32px !important;
  font-weight: 600;
}

/* ── Espaciado de Alertas en Grid ── */
.mx-4 { margin-left: 24px; margin-right: 24px; }
.mt-4 { margin-top: 24px; }

/* Adaptación Responsiva */
@media (max-width: 1024px) {
  .dash-grid { grid-template-columns: 1fr; }
  .btn-add-max { flex-direction: row; justify-content: center; padding: 12px; }
}
</style>