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
            style="max-width: 220px"
            @input="buscarConDebounce"
          />
          <select v-model="filtros.cuatrimestre_id" class="input" style="max-width: 180px" @change="cargar(1)">
            <option value="">Todos los cuatrimestres</option>
            <option v-for="c in cuatrimestres" :key="c.id" :value="c.id">{{ c.nombre || `Cuatrimestre ${c.numero}` }}</option>
          </select>
          <select v-model="filtros.activo" class="input" style="max-width: 150px" @change="cargar(1)">
            <option value="">Todas</option>
            <option value="1">Activas</option>
            <option value="0">Inactivas</option>
          </select>
        </div>
        <div class="flex g2u">
          <button class="btn btn-outline" @click="abrirMasivo">
            <ListPlus :size="16" style="margin-right: 4px" /> Carga masiva
          </button>
          <button class="btn btn-primary" @click="abrirCrear">
            <Plus :size="16" style="margin-right: 4px" /> Nueva asignatura
          </button>
        </div>
      </div>
    </div>

    <div v-if="errorMsg" class="alert a-danger mb4">{{ errorMsg }}</div>
    <div v-if="successMsg" class="alert a-success mb4">{{ successMsg }}</div>

    <!-- Tabla -->
    <div class="card">
      <div class="cp" style="overflow-x:auto">
        <table class="tt">
          <thead>
            <tr>
              <th>Clave</th>
              <th>Nombre</th>
              <th>Cuatrimestre</th>
              <th>Especialidades</th>
              <th>Plan de estudio</th>
              <th>Estado</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="cargando">
              <td colspan="7" class="sz-sm" style="text-align:center; color:var(--text-300)">Cargando…</td>
            </tr>
            <tr v-else-if="asignaturas.length === 0">
              <td colspan="7" class="sz-sm" style="text-align:center; color:var(--text-300)">
                No se encontraron asignaturas.
              </td>
            </tr>
            <tr v-for="a in asignaturas" :key="a.id">
              <td>{{ a.clave }}</td>
              <td>{{ a.nombre }}</td>
              <td>{{ a.cuatrimestre?.nombre || `Cuatrimestre ${a.cuatrimestre?.numero}` }}</td>
              <td>
                <div class="flex fw g2u">
                  <span v-for="esp in a.especialidades" :key="esp.id" class="badge b-azul">{{ esp.clave }}</span>
                </div>
              </td>
              <td>
                <a v-if="a.plan_estudio_url" :href="a.plan_estudio_url" target="_blank" rel="noopener" class="plan-link">
                  <FileText :size="16" /> Ver PDF
                </a>
                <span v-else class="sz-xs" style="color:var(--text-300)">Sin archivo</span>
              </td>
              <td>
                <span :class="['badge', a.activo ? 'b-verde' : 'b-gris']">
                  {{ a.activo ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td>
                <div class="flex ic g2u">
                  <IconButton title="Editar" @click="abrirEditar(a)">
                    <Pencil :size="16" />
                  </IconButton>
                  <IconButton
                    :title="a.activo ? 'Desactivar' : 'Activar'"
                    :variant="a.activo ? 'danger' : 'primary'"
                    @click="toggleActivo(a)"
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
import { Plus, ListPlus, Pencil, Power, FileText } from 'lucide-vue-next'
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
.plan-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--uth-verde);
  text-decoration: none;
  font-size: var(--p-sm);
}
.plan-link:hover {
  text-decoration: underline;
}
</style>
