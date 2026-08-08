<template>
  <AppShell>
    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num">
          <Users :size="20" />
        </div>
        <div>
          <h2>Usuarios</h2>
          <p>Administra las cuentas del sistema, sus roles, materias y carreras.</p>
        </div>
      </div>

      <!-- Barra de acciones -->
      <div class="card mb4">
        <div class="cp flex jb ic fw g3u">
          <div class="flex ic g3u fw">
            <input v-model.trim="filtros.q" type="text" class="input" placeholder="Buscar por nombre o correo…"
              style="max-width: 240px" @input="buscarConDebounce" />
            <select v-model="filtros.rol" class="input" style="max-width: 180px" @change="cargar(1)">
              <option value="">Todos los roles</option>
              <option v-for="r in catalogos.roles" :key="r.id" :value="r.nombre">{{ r.nombre }}</option>
            </select>
            <select v-model="filtros.activo" class="input" style="max-width: 150px" @change="cargar(1)">
              <option value="">Todos</option>
              <option value="1">Activos</option>
              <option value="0">Inactivos</option>
            </select>
          </div>
          <button class="btn btn-primary" @click="abrirCrear">
            <Plus :size="16" style="margin-right: 4px" /> Nuevo usuario
          </button>
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
                <th>Nombre</th>
                <th>Correo</th>
                <th>Roles</th>
                <th>Confirmado</th>
                <th>Estado</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="cargando">
                <td colspan="6" class="sz-sm" style="text-align:center; color:var(--text-300)">Cargando…</td>
              </tr>
              <tr v-else-if="usuarios.length === 0">
                <td colspan="6" class="sz-sm" style="text-align:center; color:var(--text-300)">
                  No se encontraron usuarios.
                </td>
              </tr>
              <tr v-for="u in usuarios" :key="u.id">
                <td>{{ nombreCompleto(u) }}</td>
                <td>{{ u.email }}</td>
                <td>
                  <div class="flex fw g2u">
                    <span v-for="r in u.roles" :key="r.id" class="badge b-azul">{{ r.nombre }}</span>
                  </div>
                </td>
                <td>
                  <span :class="['badge', u.email_verified_at ? 'b-verde' : 'b-amarillo']">
                    {{ u.email_verified_at ? 'Sí' : 'Pendiente' }}
                  </span>
                </td>
                <td>
                  <span :class="['badge', u.activo ? 'b-verde' : 'b-gris']">
                    {{ u.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td>
                  <div class="flex ic g2u">
                    <IconButton title="Editar" @click="abrirEditar(u)">
                      <Pencil :size="16" />
                    </IconButton>
                    <IconButton title="Reenviar credenciales" @click="reenviarCredenciales(u)"
                      :disabled="!!u.email_verified_at">
                      <Send :size="16" />
                    </IconButton>
                    <IconButton :title="u.activo ? 'Desactivar' : 'Activar'" :variant="u.activo ? 'danger' : 'primary'"
                      @click="toggleActivo(u)">
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
            <button class="btn btn-outline btn-sm" :disabled="paginacion.current_page <= 1"
              @click="cargar(paginacion.current_page - 1)">
              Anterior
            </button>
            <button class="btn btn-outline btn-sm" :disabled="paginacion.current_page >= paginacion.last_page"
              @click="cargar(paginacion.current_page + 1)">
              Siguiente
            </button>
          </div>
        </div>
      </div>
    </div>

    <UsuarioFormModal v-if="formAbierto" :usuario="editando" :catalogos="catalogos" @close="formAbierto = false"
      @guardado="onGuardado" />
  </AppShell>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Users, Plus, Pencil, Send, Power } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
import IconButton from '@/components/IconButton.vue'
import UsuarioFormModal from './UsuarioFormModal.vue'
import api from '@/services/api'

const usuarios = ref([])
const catalogos = reactive({ roles: [], asignaturas: [], carreras: [] })
const paginacion = reactive({ current_page: 1, last_page: 1 })
const cargando = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

const filtros = reactive({ q: '', rol: '', activo: '' })
let debounceTimer = null

const formAbierto = ref(false)
const editando = ref(null)

onMounted(async () => {
  await cargarCatalogos()
  await cargar(1)
})

async function cargarCatalogos() {
  const { data } = await api.get('/admin/usuarios/catalogos')
  catalogos.roles = data.roles
  catalogos.asignaturas = data.asignaturas
  catalogos.carreras = data.carreras
}

function buscarConDebounce() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => cargar(1), 400)
}

async function cargar(pagina = 1) {
  cargando.value = true
  errorMsg.value = ''
  try {
    const { data } = await api.get('/admin/usuarios', {
      params: {
        q: filtros.q || undefined,
        rol: filtros.rol || undefined,
        activo: filtros.activo || undefined,
        page: pagina,
      },
    })
    usuarios.value = data.data
    paginacion.current_page = data.current_page
    paginacion.last_page = data.last_page
  } catch (e) {
    errorMsg.value = 'No se pudieron cargar los usuarios.'
  } finally {
    cargando.value = false
  }
}

function abrirCrear() {
  editando.value = null
  formAbierto.value = true
}

function abrirEditar(usuario) {
  editando.value = usuario
  formAbierto.value = true
}

async function onGuardado(mensaje) {
  formAbierto.value = false
  successMsg.value = mensaje
  await cargar(paginacion.current_page)
}

async function toggleActivo(usuario) {
  try {
    await api.patch(`/admin/usuarios/${usuario.id}/toggle-activo`)
    await cargar(paginacion.current_page)
  } catch (e) {
    errorMsg.value = 'No se pudo cambiar el estado.'
  }
}

async function reenviarCredenciales(usuario) {
  errorMsg.value = ''
  successMsg.value = ''
  try {
    const { data } = await api.post(`/admin/usuarios/${usuario.id}/reenviar-credenciales`)
    successMsg.value = data.message
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'No se pudieron reenviar las credenciales.'
  }
}

function nombreCompleto(u) {
  return [u.nombre, u.apellido_paterno, u.apellido_materno].filter(Boolean).join(' ')
}
</script>
