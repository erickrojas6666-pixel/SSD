<template>
  <div class="auth-wrap">
    <div class="auth-card widget-contorno">
      
      <!-- ENCABEZADO -->
      <div class="auth-header">
        <div class="icon-wrap-3d">
          <ShieldCheck :size="32" color="#00B64F" stroke-width="2" />
        </div>
        <h1 class="auth-title">Seguridad en dos pasos</h1>
        <p class="auth-subtitle">
          {{ metodo === 'app' ? 'Ingresa el código de tu app autenticadora.' : 'Ingresa el código que enviamos a tu correo.' }}
        </p>
      </div>

      <!-- MENSAJES DE ESTADO -->
      <div v-if="errorMsg" class="alert a-danger alert-bounce">{{ errorMsg }}</div>
      <div v-if="infoMsg" class="alert a-info alert-bounce">{{ infoMsg }}</div>

      <!-- FORMULARIO -->
      <form @submit.prevent="onSubmit" novalidate class="form-spacing">
        <div class="field">
          <label class="fl">Código de verificación<span class="req">*</span></label>
          <input
            v-model.trim="code"
            type="text"
            inputmode="numeric"
            class="input input-3d-lit code-input"
            placeholder="••••••"
            autocomplete="one-time-code"
            maxlength="12"
            required
            autofocus
          />
          <p class="fh">También puedes usar uno de tus códigos de recuperación de emergencia.</p>
        </div>

        <button type="submit" class="btn auth-submit-3d" :disabled="loading">
          {{ loading ? 'Verificando...' : 'Verificar acceso' }}
        </button>
      </form>

      <!-- ACCIONES SECUNDARIAS -->
      <div class="auth-links-stack">
        <!-- Reenviar código (Solo si es por correo) -->
        <button 
          v-if="metodo === 'email'" 
          class="resend-btn" 
          :disabled="reenviando" 
          @click="onResend"
        >
          <RefreshCw :size="14" :class="{ 'spin-anim': reenviando }" />
          {{ reenviando ? 'Enviando nuevo código...' : 'Reenviar código al correo' }}
        </button>

        <!-- Cancelar -->
        <router-link :to="{ name: 'login' }" class="back-link">
          <ArrowLeft :size="16" /> Cancelar y volver al inicio
        </router-link>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ShieldCheck, ArrowLeft, RefreshCw } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { roleHomeName } from '@/config/menus'
import router from '@/router'

const auth = useAuthStore()
const code = ref('')
const loading = ref(false)
const reenviando = ref(false)
const errorMsg = ref('')
const infoMsg = ref('')
const metodo = ref(auth.twoFactorMethod)

onMounted(() => {
  // Si no hay un reto pendiente (ej. se recargó la página sin loguearse antes), regresa al login
  if (!auth.twoFactorChallenge) {
    router.replace({ name: 'login' })
  }
})

async function onSubmit() {
  loading.value = true
  errorMsg.value = ''
  try {
    const data = await auth.verifyTwoFactor(code.value)
    router.push({ name: roleHomeName(data.roles) })
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Código incorrecto o expirado.'
  } finally {
    loading.value = false
  }
}

async function onResend() {
  reenviando.value = true
  infoMsg.value = ''
  errorMsg.value = ''
  try {
    await auth.resendTwoFactorCode()
    infoMsg.value = 'Te enviamos un nuevo código a tu correo.'
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'No se pudo reenviar el código.'
  } finally {
    reenviando.value = false
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

.fh {
  font-size: 12.5px;
  color: var(--text-400);
  margin-top: 10px;
  font-weight: 500;
  text-align: center;
}

/* Input especial para códigos (Centrado y con tracking) */
.code-input {
  text-align: center;
  font-size: 20px !important;
  font-weight: 800;
  letter-spacing: 6px;
  padding: 16px !important;
}

.code-input::placeholder {
  letter-spacing: 4px;
  font-weight: 600;
  color: var(--text-300);
}

.input-3d-lit {
  width: 100%;
  border-radius: var(--r-md);
  border: 2px solid var(--uth-verde-claro) !important;
  box-shadow: 0 0 0 3px var(--uth-verde-bg), inset 0 3px 6px rgba(0, 0, 0, 0.04) !important;
  background: #FFFFFF !important;
  font-family: var(--font) !important;
  color: var(--text-900);
  transition: all 0.2s ease;
}

.input-3d-lit:hover, 
.input-3d-lit:focus {
  border-color: var(--uth-verde) !important;
  box-shadow: 0 0 0 4px var(--uth-verde-ring), inset 0 2px 4px rgba(0, 0, 0, 0.02) !important;
  outline: none;
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

/* ── ENLACES Y BOTONES SECUNDARIOS ── */
.auth-links-stack {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  margin-top: 28px;
}

.resend-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  color: var(--text-600);
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  padding: 8px 16px;
  border-radius: var(--r-pill);
  transition: all 0.2s ease;
}

.resend-btn:hover:not(:disabled) {
  background: var(--bg-soft);
  color: var(--text-900);
}

.resend-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--uth-verde);
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s ease;
}

.back-link:hover {
  color: var(--uth-verde-hover);
  transform: translateX(-4px);
}

/* ── ANIMACIONES ── */
.alert-bounce {
  animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
  margin-bottom: 24px;
}

.spin-anim {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}
</style>