<template>
  <div class="auth-wrap">
    <div class="auth-card widget-contorno">
      
      <!-- ENCABEZADO -->
      <div class="auth-header">
        <div class="icon-wrap-3d">
          <LockKeyhole :size="32" color="#00B64F" stroke-width="2" />
        </div>
        <h1 class="auth-title">Restablecer contraseña</h1>
        <p class="auth-subtitle">Crea una nueva contraseña para tu cuenta.</p>
      </div>

      <!-- MENSAJES DE ESTADO -->
      <div v-if="successMsg" class="alert a-success alert-bounce">{{ successMsg }}</div>
      <div v-if="errorMsg" class="alert a-danger alert-bounce">{{ errorMsg }}</div>

      <!-- FORMULARIO -->
      <form v-if="!successMsg" @submit.prevent="onSubmit" novalidate class="form-spacing">
        <div class="field">
          <label class="fl">Correo institucional</label>
          <!-- Input de solo lectura -->
          <input 
            v-model.trim="email" 
            type="email" 
            class="input input-3d-lit input-readonly" 
            readonly 
          />
        </div>

        <div class="field">
          <label class="fl">Nueva contraseña<span class="req">*</span></label>
          <input
            v-model="password"
            type="password"
            class="input input-3d-lit"
            placeholder="Mínimo 8 caracteres"
            autocomplete="new-password"
            required
            minlength="8"
          />
        </div>

        <div class="field">
          <label class="fl">Confirmar contraseña<span class="req">*</span></label>
          <input
            v-model="passwordConfirmation"
            type="password"
            class="input input-3d-lit"
            placeholder="Repite la contraseña"
            autocomplete="new-password"
            required
            minlength="8"
          />
        </div>

        <button type="submit" class="btn auth-submit-3d" :disabled="loading">
          {{ loading ? 'Guardando...' : 'Guardar nueva contraseña' }}
        </button>
      </form>

      <!-- ENLACES -->
      <div class="auth-links">
        <router-link :to="{ name: 'login' }" class="back-link">
          <ArrowLeft :size="16" /> Volver a iniciar sesión
        </router-link>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { LockKeyhole, ArrowLeft } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const auth = useAuthStore()

const token = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

onMounted(() => {
  // El enlace del correo llega como /restablecer-password?token=...&email=...
  token.value = route.query.token || ''
  email.value = route.query.email || ''
})

async function onSubmit() {
  loading.value = true
  errorMsg.value = ''
  try {
    const data = await auth.resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    successMsg.value = data.message
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'El enlace no es válido o ya expiró.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ── CONTENEDOR PRINCIPAL ── */
.auth-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-page);
  padding: 24px;
}

/* ── TARJETA ESTILO SOFT UI ── */
.auth-card {
  width: 100%;
  max-width: 440px;
  background: #FFFFFF;
  padding: 48px 40px;
  animation: scaleIn var(--tsl) var(--ease-spring) both;
}

.widget-contorno {
  border: 3px solid rgba(0, 182, 79, 0.15); 
  border-radius: var(--r-xl);
  box-shadow: 0 14px 30px -10px rgba(0, 182, 79, 0.2);
  transition: transform 0.3s var(--ease-spring), box-shadow 0.3s ease;
}

.widget-contorno:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px -10px rgba(0, 182, 79, 0.25);
}

/* ── ENCABEZADO E ÍCONO ── */
.auth-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-bottom: 32px;
}

.icon-wrap-3d {
  width: 64px;
  height: 64px;
  background: rgba(0, 182, 79, 0.1);
  border: 2px solid rgba(0, 182, 79, 0.2);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
  box-shadow: 0 6px 15px rgba(0, 182, 79, 0.15);
  transform: rotate(-3deg);
  transition: transform 0.3s var(--ease-spring);
}

.auth-card:hover .icon-wrap-3d {
  transform: rotate(5deg) scale(1.05);
}

.auth-title {
  font-family: 'Sora', sans-serif;
  font-size: 24px;
  font-weight: 800;
  color: var(--text-900);
  margin: 0 0 8px 0;
  letter-spacing: -0.5px;
}

.auth-subtitle {
  font-size: 14.5px;
  font-weight: 500;
  color: var(--text-500);
  line-height: 1.5;
  margin: 0;
}

/* ── FORMULARIO E INPUTS ── */
.form-spacing {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.field {
  margin-bottom: 0;
}

.fl {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-700);
  margin-bottom: 8px;
}

.req {
  color: var(--danger);
  margin-left: 4px;
}

/* Input permanentemente iluminado */
.input-3d-lit {
  width: 100%;
  padding: 14px 16px;
  border-radius: var(--r-md);
  border: 2px solid var(--uth-verde-claro) !important;
  box-shadow: 0 0 0 3px var(--uth-verde-bg), inset 0 3px 6px rgba(0, 0, 0, 0.04) !important;
  background: #FFFFFF !important;
  font-family: var(--font) !important;
  font-size: var(--p-sm);
  color: var(--text-900);
  transition: all 0.2s ease;
}

.input-3d-lit:hover:not(.input-readonly), 
.input-3d-lit:focus:not(.input-readonly) {
  border-color: var(--uth-verde) !important;
  box-shadow: 0 0 0 4px var(--uth-verde-ring), inset 0 2px 4px rgba(0, 0, 0, 0.02) !important;
  outline: none;
}

/* Input bloqueado visualmente */
.input-readonly {
  background: var(--bg-soft) !important;
  color: var(--text-500);
  cursor: not-allowed;
  border-color: var(--border-soft) !important;
  box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.02) !important;
}

/* ── BOTÓN FÍSICO 3D ── */
.auth-submit-3d {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  font-size: 15px;
  border-radius: var(--r-pill);
  background: var(--uth-verde);
  color: white;
  font-weight: 800;
  border: none;
  box-shadow: 0 6px 0 #007734, 0 10px 15px rgba(0, 182, 79, 0.3);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  transform: translateY(-4px);
  cursor: pointer;
  margin-top: 8px;
}

.auth-submit-3d:hover:not(:disabled) {
  background: var(--uth-verde-hover);
  box-shadow: 0 8px 0 #007734, 0 14px 20px rgba(0, 182, 79, 0.4);
  transform: translateY(-6px);
}

.auth-submit-3d:active:not(:disabled) {
  transform: translateY(2px);
  box-shadow: 0 0 0 #007734;
}

.auth-submit-3d:disabled {
  background: var(--text-300);
  box-shadow: 0 4px 0 var(--text-500);
  transform: translateY(0);
  cursor: not-allowed;
  opacity: 0.8;
}

/* ── ALERTAS ── */
.alert-bounce {
  animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
  margin-bottom: 24px;
}

/* ── ENLACES INFERIORES ── */
.auth-links {
  text-align: center;
  margin-top: 32px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-500);
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s ease;
}

.back-link:hover {
  color: var(--uth-verde);
  transform: translateX(-4px);
}

/* ── KEYFRAMES LOCALES ── */
@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}
</style>