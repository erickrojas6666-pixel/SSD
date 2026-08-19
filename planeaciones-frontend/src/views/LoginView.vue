<template>
  <!-- Agregamos los eventos del ratón al contenedor principal -->
  <div class="auth-wrap" @mousemove="handleMouseMove" @mouseleave="handleMouseLeave">
    
    <!-- ref="shellRef" para aplicar el 3D con JS -->
    <div class="auth-shell" ref="shellRef">
      
      <!-- Panel de marca (Lado Izquierdo) -->
      <div class="auth-brand-panel">
        <div class="auth-brand-blob auth-brand-blob-1"></div>
        <div class="auth-brand-blob auth-brand-blob-2"></div>
        <div class="auth-brand-content">
          <!-- Logo y cápsulas eliminadas para un diseño más limpio -->
          <h1 class="auth-brand-title">Planeaciones<br /><em>Didácticas</em></h1>
          <p class="auth-brand-sub">Universidad Tecnológica de Huejotzingo</p>
        </div>
      </div>

      <!-- Panel de formulario (Lado Derecho) -->
      <div class="auth-form-panel">
        <div class="auth-form-inner">
          <h2 class="auth-title">Iniciar sesión</h2>
          <p class="auth-subtitle">Ingresa con tu correo institucional para continuar.</p>

          <div v-if="errorMsg" class="alert a-danger">{{ errorMsg }}</div>

          <form @submit.prevent="onSubmit" novalidate>
            <div class="field">
              <label class="fl">Correo Institucional<span class="req">*</span></label>
              <!-- Campo permanentemente iluminado -->
              <input
                v-model.trim="email"
                type="email"
                class="input input-3d-lit"
                :class="{ 'input-err': errorMsg }"
                placeholder="usuario@uth.edu.mx"
                autocomplete="username"
                required
              />
            </div>

            <div class="field">
              <label class="fl">Contraseña<span class="req">*</span></label>
              <!-- Campo permanentemente iluminado -->
              <input
                v-model="password"
                type="password"
                class="input input-3d-lit"
                :class="{ 'input-err': errorMsg }"
                placeholder="••••••••"
                autocomplete="current-password"
                required
              />
            </div>

            <button type="submit" class="btn auth-submit-3d" :disabled="loading">
              {{ loading ? 'Ingresando…' : 'Iniciar sesión' }}
            </button>
          </form>

          <div class="auth-links">
            <router-link :to="{ name: 'forgot-password' }">¿Olvidaste tu contraseña?</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { roleHomeName } from '@/config/menus'
import router from '@/router'

const auth = useAuthStore()
const email = ref('')
const password = ref('')
const loading = ref(false)
const errorMsg = ref('')

// Referencia a la tarjeta del Login
const shellRef = ref(null)

// Lógica para el Paralaje 3D en todo el Login
function handleMouseMove(e) {
  const el = shellRef.value
  if (!el) return
  
  const rect = el.getBoundingClientRect()
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  const centerX = rect.width / 2
  const centerY = rect.height / 2
  
  // Factores de rotación suaves para que no sea molesto a la vista
  const rotateX = ((y - centerY) / centerY) * -3.5
  const rotateY = ((x - centerX) / centerX) * 3.5

  el.style.transform = `perspective(1200px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`
}

function handleMouseLeave() {
  const el = shellRef.value
  if (!el) return
  el.style.transform = 'perspective(1200px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)'
}

async function onSubmit() {
  loading.value = true
  errorMsg.value = ''
  try {
    const data = await auth.login(email.value, password.value)
    if (data.requires_2fa) {
      router.push({ name: 'verificar-2fa' })
      return
    }
    router.push({ name: roleHomeName(data.roles) })
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'No se pudo iniciar sesión. Intenta de nuevo.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ── Contenedor Principal ── */
.auth-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-page);
  padding: 24px;
  /* Damos perspectiva desde el contenedor padre */
  perspective: 1500px; 
}

.auth-shell {
  width: 100%;
  max-width: 960px;
  min-height: 580px;
  display: grid;
  grid-template-columns: 1.15fr 1fr;
  background: var(--bg-white);
  border-radius: var(--r-xl);
  overflow: hidden;
  box-shadow: var(--sh-lg);
  border: 1px solid var(--border-soft);
  
  /* Transición fluida para el efecto paralaje */
  transition: transform 0.15s cubic-bezier(0.25, 0.46, 0.45, 0.94), box-shadow 0.3s ease;
  will-change: transform;
  transform-style: preserve-3d;
}

.auth-shell:hover {
  box-shadow: 0 35px 60px -15px rgba(0, 182, 79, 0.25);
}

/* ── Panel de marca (Izquierda) ── */
.auth-brand-panel {
  position: relative;
  background: linear-gradient(135deg, #00B64F 0%, #007734 100%);
  overflow: hidden;
  display: flex;
  align-items: center;
  padding: 48px;
  border-right: 1px solid var(--border);
}

.auth-brand-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(25px);
}
.auth-brand-blob-1 {
  width: 350px;
  height: 350px;
  top: -100px;
  right: -100px;
  background: rgba(255, 255, 255, 0.15);
  animation: floatSlow 9s var(--ease) infinite;
}
.auth-brand-blob-2 {
  width: 250px;
  height: 250px;
  bottom: -80px;
  left: -40px;
  background: rgba(0, 0, 0, 0.1);
  animation: floatSlow 11s var(--ease) infinite reverse;
}

.auth-brand-content {
  position: relative;
  z-index: 1;
  /* Empujamos el contenido un poco hacia nosotros en el 3D */
  transform: translateZ(30px);
}

.auth-brand-title {
  font-family: 'Sora', sans-serif;
  font-size: 44px;
  font-weight: 800;
  color: #FFFFFF;
  line-height: 1.1;
  margin-bottom: 16px;
  letter-spacing: -1px;
  text-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
}
.auth-brand-title em {
  color: #A3E635;
  font-style: normal;
}

.auth-brand-sub {
  font-size: 15px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.85);
  margin-bottom: 40px;
  line-height: 1.5;
}

/* ── Panel de formulario (Derecha) ── */
.auth-form-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px;
  background: var(--bg-white);
  /* Empujamos el formulario hacia adelante */
  transform: translateZ(20px);
}

.auth-form-inner {
  width: 100%;
  max-width: 330px;
  animation: fadeInUp var(--tsl) var(--ease-out) both 100ms;
}

.auth-title {
  font-family: 'Sora', sans-serif;
  font-size: 26px;
  font-weight: 800;
  color: var(--text-900);
  margin-bottom: 8px;
  letter-spacing: -0.5px;
}

.auth-subtitle {
  font-size: 14px;
  font-weight: 500;
  color: var(--text-500);
  margin-bottom: 32px;
  line-height: 1.5;
}

/* ── CAMPOS ILUMINADOS PERMANENTEMENTE ── */
.field {
  margin-bottom: 20px;
}
.fl {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-700);
  margin-bottom: 8px;
}
.req {
  color: var(--danger);
  margin-left: 4px;
}

/* Modificamos los inputs para que siempre estén "encendidos" */
.input-3d-lit {
  padding: 14px 16px;
  /* Forzamos el borde y sombra verde pastel siempre activos */
  border-color: var(--uth-verde-claro) !important;
  box-shadow: 0 0 0 3px var(--uth-verde-bg), inset 0 3px 6px rgba(0, 0, 0, 0.04) !important;
  background: #FFFFFF !important;
}

.input-3d-lit:hover, 
.input-3d-lit:focus {
  /* Al seleccionarlos, el verde se hace más fuerte */
  border-color: var(--uth-verde) !important;
  box-shadow: 0 0 0 4px var(--uth-verde-ring), inset 0 2px 4px rgba(0, 0, 0, 0.02) !important;
}

/* ── BOTÓN FÍSICO 3D ── */
.auth-submit-3d {
  width: 100%;
  justify-content: center;
  margin-top: 20px;
  padding: 14px;
  font-size: 15.5px;
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

/* Enlaces inferiores */
.auth-links {
  text-align: center;
  margin-top: 32px;
  font-size: 14px;
  font-weight: 700;
}
.auth-links a {
  color: var(--uth-verde);
  text-decoration: none;
  transition: color 0.2s ease;
}
.auth-links a:hover {
  color: var(--uth-verde-hover);
  text-decoration: underline;
}

/* ── Responsivo ── */
@media (max-width: 768px) {
  .auth-shell {
    grid-template-columns: 1fr;
    min-height: auto;
    transform: none !important; /* Apagamos el 3D en móviles */
  }
  .auth-brand-panel {
    padding: 32px;
    border-right: none;
    border-bottom: 1px solid var(--border);
  }
  .auth-form-panel {
    padding: 32px 24px;
    transform: none;
  }
}
</style>