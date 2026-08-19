<template>
  <div class="auth-wrap">
    <div class="auth-card widget-contorno">
      
      <!-- ESTADO: CARGANDO -->
      <div v-if="cargando" class="status-container">
        <div class="icon-pulse">
          <Loader2 :size="48" class="spin-slow" color="#00B64F" />
        </div>
        <h1 class="auth-title">Confirmando...</h1>
        <p class="auth-subtitle">Estamos verificando tu token de seguridad.</p>
      </div>

      <!-- ESTADO: ÉXITO -->
      <div v-else-if="exito" class="status-container">
        <div class="icon-bounce icon-success">
          <CheckCircle2 :size="56" color="#10B981" />
        </div>
        <h1 class="auth-title">¡Cuenta Confirmada!</h1>
        <p class="auth-subtitle success-text">{{ mensaje }}</p>
      </div>

      <!-- ESTADO: ERROR -->
      <div v-else class="status-container">
        <div class="icon-bounce icon-error">
          <XCircle :size="56" color="#EF4444" />
        </div>
        <h1 class="auth-title">Enlace Inválido</h1>
        <p class="auth-subtitle error-text">{{ mensaje }}</p>
      </div>

      <!-- BOTÓN DE ACCIÓN 3D -->
      <div class="auth-actions" v-if="!cargando">
        <router-link :to="{ name: 'login' }" class="btn auth-submit-3d">
          Ir a iniciar sesión <ArrowRight :size="18" />
        </router-link>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Loader2, CheckCircle2, XCircle, ArrowRight } from 'lucide-vue-next'
import api from '@/services/api'

const route = useRoute()
const cargando = ref(true)
const exito = ref(false)
const mensaje = ref('')

onMounted(async () => {
  const token = route.query.token
  if (!token) {
    exito.value = false
    mensaje.value = 'Falta el token de confirmación en el enlace.'
    cargando.value = false
    return
  }

  try {
    const { data } = await api.post('/confirmar-cuenta', { token })
    exito.value = true
    mensaje.value = data.message
  } catch (e) {
    exito.value = false
    mensaje.value = e.response?.data?.message || 'No se pudo confirmar la cuenta.'
  } finally {
    cargando.value = false
  }
})
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
  max-width: 420px;
  background: #FFFFFF;
  padding: 48px 32px;
  text-align: center;
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

/* ── CONTENIDO E ÍCONOS ── */
.status-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.auth-title {
  font-family: 'Sora', sans-serif;
  font-size: 24px;
  font-weight: 800;
  color: var(--text-900);
  margin: 0;
  letter-spacing: -0.5px;
}

.auth-subtitle {
  font-size: 15px;
  font-weight: 500;
  color: var(--text-500);
  line-height: 1.5;
  margin: 0;
}

.success-text {
  color: #047857; /* Verde más oscuro para legibilidad */
}

.error-text {
  color: #B91C1C; /* Rojo más oscuro para legibilidad */
}

/* ── ANIMACIONES DE ÍCONOS ── */
.spin-slow {
  animation: spin 3s linear infinite;
}

.icon-bounce {
  animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
  background: var(--bg-soft);
  border-radius: 50%;
  padding: 16px;
  margin-bottom: 8px;
}

.icon-success {
  background: #ECFDF5;
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
}

.icon-error {
  background: #FEF2F2;
  box-shadow: 0 8px 20px rgba(239, 68, 68, 0.2);
}

/* ── BOTÓN FÍSICO 3D ── */
.auth-actions {
  margin-top: 40px;
  width: 100%;
}

.auth-submit-3d {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px;
  font-size: 15.5px;
  border-radius: var(--r-pill);
  background: var(--uth-verde);
  color: white;
  font-weight: 800;
  text-decoration: none;
  border: none;
  /* Labio inferior oscuro y sombra de proyección */
  box-shadow: 0 6px 0 #007734, 0 10px 15px rgba(0, 182, 79, 0.3);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  transform: translateY(-4px);
  cursor: pointer;
}

.auth-submit-3d:hover {
  background: var(--uth-verde-hover);
  box-shadow: 0 8px 0 #007734, 0 14px 20px rgba(0, 182, 79, 0.4);
  transform: translateY(-6px);
}

/* Efecto de aplastamiento al hacer clic */
.auth-submit-3d:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 #007734;
}

/* ── KEYFRAMES LOCALES ── */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.8); }
  to { opacity: 1; transform: scale(1); }
}
</style>