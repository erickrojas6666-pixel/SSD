import { createRouter, createWebHistory } from 'vue-router'
import { roleHomeName } from '@/config/menus'

import LoginView from '@/views/LoginView.vue'
import ForgotPasswordView from '@/views/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/ResetPasswordView.vue'
import TwoFactorChallengeView from '@/views/TwoFactorChallengeView.vue'
import TwoFactorSettingsView from '@/views/TwoFactorSettingsView.vue'
import ConfirmarCuentaView from '@/views/ConfirmarCuentaView.vue'
import AdminHome from '@/views/homes/AdminHome.vue'
import AcademicoView from '@/views/admin/AcademicoView.vue'
import UsuariosView from '@/views/admin/UsuariosView.vue'
import SecuenciaEditorView from '@/views/secuencias/SecuenciaEditorView.vue'
import SecuenciasDocenteView from '@/views/secuencias/docente/SecuenciasDocenteView.vue'
import SecuenciasRevisorView from '@/views/secuencias/revisor/SecuenciasRevisorView.vue'
import SecuenciasDirectorView from '@/views/secuencias/director/SecuenciasDirectorView.vue'
import DirectorHome from '@/views/homes/DirectorHome.vue'
import DocenteHome from '@/views/homes/DocenteHome.vue'
import RevisorHome from '@/views/homes/RevisorHome.vue'
import SecretarioHome from '@/views/homes/SecretarioHome.vue'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'login', component: LoginView, meta: { guestOnly: true } },
  { path: '/verificar-2fa', name: 'verificar-2fa', component: TwoFactorChallengeView, meta: { guestOnly: true } },
  { path: '/olvide-password', name: 'forgot-password', component: ForgotPasswordView, meta: { guestOnly: true } },
  { path: '/restablecer-password', name: 'reset-password', component: ResetPasswordView, meta: { guestOnly: true } },
  { path: '/confirmar-cuenta', name: 'confirmar-cuenta', component: ConfirmarCuentaView },

  { path: '/administrador', name: 'home-administrador', component: AdminHome, meta: { requiresAuth: true, roles: ['Administrador'] } },
  { path: '/administrador/academico', name: 'admin-academico', component: AcademicoView, meta: { requiresAuth: true, roles: ['Administrador'] } },
  { path: '/administrador/usuarios', name: 'admin-usuarios', component: UsuariosView, meta: { requiresAuth: true, roles: ['Administrador'] } },
  { path: '/director', name: 'home-director', component: DirectorHome, meta: { requiresAuth: true, roles: ['Director'] } },
  { path: '/director/secuencias', name: 'secuencias-director', component: SecuenciasDirectorView, meta: { requiresAuth: true, roles: ['Director'] } },
  { path: '/docente', name: 'home-docente', component: DocenteHome, meta: { requiresAuth: true, roles: ['Docente'] } },
  { path: '/docente/secuencias', name: 'secuencias-docente', component: SecuenciasDocenteView, meta: { requiresAuth: true, roles: ['Docente'] } },
  { path: '/revisor', name: 'home-revisor', component: RevisorHome, meta: { requiresAuth: true, roles: ['Revisor'] } },
  { path: '/revisor/secuencias', name: 'secuencias-revisor', component: SecuenciasRevisorView, meta: { requiresAuth: true, roles: ['Revisor'] } },
  { path: '/secretario', name: 'home-secretario', component: SecretarioHome, meta: { requiresAuth: true, roles: ['Secretario'] } },

  {
    path: '/secuencias/:id',
    name: 'secuencia-editor',
    component: SecuenciaEditorView,
    meta: { requiresAuth: true, roles: ['Docente', 'Revisor', 'Administrador'] },
  },

  // Cualquier usuario autenticado, sin importar su rol, puede configurar su propio 2FA
  { path: '/perfil/2fa', name: 'perfil-2fa', component: TwoFactorSettingsView, meta: { requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  const roles = JSON.parse(localStorage.getItem('roles') || '[]')

  if (to.meta.requiresAuth && !token) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && token && to.name !== 'verificar-2fa') {
    return { name: roleHomeName(roles) }
  }

  if (to.meta.roles && !to.meta.roles.some((r) => roles.includes(r))) {
    return { name: roleHomeName(roles) }
  }

  return true
})

export default router
