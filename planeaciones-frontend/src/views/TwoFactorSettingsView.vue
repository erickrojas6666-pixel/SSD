<template>
  <AppShell>
    <div class="dashboard-layout">
      
      <!-- ENCABEZADO DEL MÓDULO (Acento Azul Seguridad) -->
      <header class="dash-header widget-contorno">
        <div class="header-icon blue-icon">
          <ShieldCheck :size="32" color="#3B82F6" stroke-width="2" />
        </div>
        <div class="header-info">
          <h2>Autenticación en dos pasos</h2>
          <p>Añade una capa extra de protección a tu cuenta configurando un segundo factor de acceso.</p>
        </div>
        <div class="header-deco-dots"></div>
      </header>

      <!-- CONTENEDOR CENTRAL DE AJUSTES -->
      <div class="settings-container">

        <div v-if="errorMsg" class="alert a-danger alert-bounce">{{ errorMsg }}</div>
        <div v-if="successMsg" class="alert a-success alert-bounce">{{ successMsg }}</div>

        <!-- =========================================
             TARJETA 1: ESTADO ACTUAL
        ========================================== -->
        <div class="widget-contorno settings-card">
          <div class="status-wrap">
            <div>
              <h3 class="ht-sm">Estado de seguridad</h3>
              <p class="sz-sm text-dim">Determina si tu cuenta está protegida actualmente.</p>
            </div>
            <div>
              <span v-if="dosFAActiva" class="badge-3d badge-active">
                <ShieldCheck :size="14" />
                Activada ({{ metodoActual === 'app' ? 'App' : 'Correo' }})
              </span>
              <span v-else class="badge-3d badge-inactive">
                <ShieldAlert :size="14" /> Desactivada
              </span>
            </div>
          </div>
        </div>

        <!-- =========================================
             TARJETA 2: CÓDIGOS DE RECUPERACIÓN
        ========================================== -->
        <div v-if="recoveryCodes.length" class="widget-contorno settings-card codes-vault">
          <div class="alert a-warning">
            <strong>¡Importante!</strong> Guarda estos códigos de recuperación en un lugar seguro. Cada uno solo se puede usar una vez y no volverán a mostrarse.
          </div>
          <div class="codes-grid">
            <code v-for="c in recoveryCodes" :key="c" class="recovery-code">{{ c }}</code>
          </div>
        </div>

        <!-- =========================================
             TARJETA 3: ACTIVAR 2FA
        ========================================== -->
        <div v-if="!dosFAActiva" class="widget-contorno settings-card">
          <h3 class="ht-sm mb4">Configurar nuevo método</h3>
          
          <!-- Paso 1: Elegir método -->
          <div v-if="!setupData" class="method-selection">
            <button class="btn-method-3d" :disabled="loading" @click="onEnable('app')">
              <div class="method-icon"><Smartphone :size="24" /></div>
              <div class="method-info">
                <strong>App autenticadora</strong>
                <span>Google Authenticator, Authy, etc.</span>
              </div>
            </button>

            <button class="btn-method-3d" :disabled="loading" @click="onEnable('email')">
              <div class="method-icon"><Mail :size="24" /></div>
              <div class="method-info">
                <strong>Código por correo</strong>
                <span>Recibe un PIN en tu bandeja de entrada.</span>
              </div>
            </button>
          </div>

          <!-- Paso 2: Confirmar configuración -->
          <div v-if="setupData" class="setup-confirmation">
            <!-- Instrucciones para App -->
            <div v-if="setupData.method === 'app'" class="setup-app-box">
              <p class="sz-sm">1. Escanea este código QR con tu aplicación autenticadora favorita.</p>
              <div class="qr-box" v-html="setupData.qr_svg"></div>
              <p class="fh mt3">O ingresa esta clave manualmente: <code class="secret-code">{{ setupData.secret }}</code></p>
            </div>

            <!-- Instrucciones para Correo -->
            <div v-else class="setup-email-box">
              <MailOpen :size="32" color="#00B64F" />
              <p class="sz-sm">Te hemos enviado un código de seguridad a tu correo institucional.</p>
            </div>

            <div class="div-soft"></div>

            <div class="field">
              <label class="fl">Código de confirmación<span class="req">*</span></label>
              <input 
                v-model.trim="confirmCode" 
                type="text" 
                class="input input-3d-lit text-center font-code" 
                placeholder="••••••" 
                maxlength="6"
              />
            </div>

            <button class="btn btn-primary btn-add-3d w-100" :disabled="loading" @click="onConfirm">
              Confirmar y activar 2FA
            </button>
          </div>
        </div>

        <!-- =========================================
             TARJETA 4: DESACTIVAR 2FA
        ========================================== -->
        <div v-if="dosFAActiva" class="widget-contorno settings-card danger-zone">
          <h3 class="ht-sm mb4 text-danger">Zona de Peligro: Desactivar 2FA</h3>
          <p class="sz-sm text-dim mb4">Al desactivar esta opción, tu cuenta será vulnerable a accesos no autorizados. Necesitamos tu contraseña para proceder.</p>
          
          <div class="field">
            <label class="fl text-danger">Confirma tu contraseña<span class="req">*</span></label>
            <input 
              v-model="passwordDisable" 
              type="password" 
              class="input input-3d-lit input-danger" 
              placeholder="••••••••" 
            />
          </div>
          
          <button class="btn btn-danger btn-danger-3d" :disabled="loading || !passwordDisable" @click="onDisable">
            <ShieldOff :size="18" /> Desactivar seguridad
          </button>
        </div>

      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ShieldCheck, ShieldAlert, ShieldOff, Smartphone, Mail, MailOpen } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const setupData = ref(null)
const confirmCode = ref('')
const passwordDisable = ref('')
const recoveryCodes = ref([])

const dosFAActiva = ref(false)
const metodoActual = ref(null)

onMounted(cargarEstado)

async function cargarEstado() {
  const { data } = await api.get('/me')
  dosFAActiva.value = data.two_factor_enabled
  metodoActual.value = data.two_factor_method
}

async function onEnable(method) {
  loading.value = true
  errorMsg.value = ''
  try {
    const { data } = await api.post('/2fa/enable', { method })
    setupData.value = data
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'No se pudo iniciar la activación.'
  } finally {
    loading.value = false
  }
}

async function onConfirm() {
  loading.value = true
  errorMsg.value = ''
  try {
    const { data } = await api.post('/2fa/confirm', { code: confirmCode.value })
    successMsg.value = data.message
    recoveryCodes.value = data.recovery_codes
    setupData.value = null
    await cargarEstado()
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Código incorrecto.'
  } finally {
    loading.value = false
  }
}

async function onDisable() {
  loading.value = true
  errorMsg.value = ''
  try {
    const { data } = await api.post('/2fa/disable', { password: passwordDisable.value })
    successMsg.value = data.message
    passwordDisable.value = ''
    recoveryCodes.value = []
    await cargarEstado()
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Contraseña incorrecta o error al desactivar.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ==================================================
   LAYOUT DASHBOARD & HEADER
================================================== */
.dashboard-layout {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* WIDGETS CONTORNO */
.widget-contorno {
  background: #FFFFFF;
  border: 3px solid rgba(59, 130, 246, 0.15); 
  border-radius: var(--r-xl);
  box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.15);
  position: relative;
  overflow: hidden;
  transition: transform 0.3s var(--ease-spring), box-shadow 0.3s ease, border-color 0.3s ease;
}
.widget-contorno:hover {
  border-color: rgba(59, 130, 246, 0.3);
  box-shadow: 0 15px 35px -10px rgba(59, 130, 246, 0.2);
  transform: translateY(-2px);
}

/* ENCABEZADO */
.dash-header {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 32px 40px;
  background: linear-gradient(90deg, #FFFFFF 0%, #EFF6FF 100%);
}
.header-icon {
  width: 64px;
  height: 64px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  transform: rotate(-3deg);
}
.blue-icon {
  background: rgba(59, 130, 246, 0.1);
  border: 2px solid rgba(59, 130, 246, 0.2);
  box-shadow: 0 6px 15px rgba(59, 130, 246, 0.15);
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
  background-image: radial-gradient(rgba(59, 130, 246, 0.2) 2px, transparent 2px);
  background-size: 10px 10px;
  opacity: 0.5;
}

/* ==================================================
   CONTENEDOR DE AJUSTES (CENTRAL)
================================================== */
.settings-container {
  max-width: 700px;
  width: 100%;
  margin: 0 auto; /* Centramos el contenido de ajustes */
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.settings-card {
  padding: 32px;
}

.text-dim { color: var(--text-500); }
.text-danger { color: #DC2626; }
.w-100 { width: 100%; justify-content: center; }

/* ── ESTADO ACTUAL ── */
.status-wrap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.badge-3d {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: var(--r-pill);
  font-size: 14px;
  font-weight: 700;
  box-shadow: 0 3px 0 rgba(0,0,0,0.1);
}
.badge-active {
  background: #ECFDF5;
  color: #059669;
  border: 2px solid #10B981;
  box-shadow: 0 4px 0 rgba(16, 185, 129, 0.2);
}
.badge-inactive {
  background: #F3F4F6;
  color: #6B7280;
  border: 2px solid #D1D5DB;
}

/* ── CÓDIGOS DE RECUPERACIÓN ── */
.codes-vault {
  border-color: rgba(245, 158, 11, 0.3); /* Contorno Ámbar */
}
.codes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 12px;
  margin-top: 16px;
}
.recovery-code {
  background: var(--bg-page);
  border: 2px dashed var(--border-soft);
  padding: 12px;
  text-align: center;
  border-radius: var(--r-md);
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 2px;
  color: var(--text-900);
}

/* ── SELECCIÓN DE MÉTODO (Botones 3D) ── */
.method-selection {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-top: 16px;
}

.btn-method-3d {
  background: var(--bg-page);
  border: 2px solid var(--border-soft);
  border-radius: var(--r-lg);
  padding: 24px 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 12px;
  cursor: pointer;
  box-shadow: 0 4px 0 var(--border-soft);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  transform: translateY(-2px);
}
.btn-method-3d:hover {
  background: #FFFFFF;
  border-color: var(--uth-verde-claro);
  box-shadow: 0 6px 0 rgba(0, 182, 79, 0.2), 0 10px 20px rgba(0,0,0,0.05);
  transform: translateY(-4px);
}
.btn-method-3d:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 rgba(0, 182, 79, 0.2);
}

.method-icon {
  width: 48px;
  height: 48px;
  background: #FFFFFF;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--uth-verde);
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.method-info strong {
  display: block;
  font-size: 15px;
  color: var(--text-900);
  margin-bottom: 4px;
}
.method-info span {
  display: block;
  font-size: 12px;
  color: var(--text-500);
}

@media (max-width: 600px) {
  .method-selection { grid-template-columns: 1fr; }
}

/* ── FORMULARIO DE CONFIRMACIÓN ── */
.setup-confirmation {
  animation: scaleIn 0.3s ease-out both;
  background: var(--bg-soft);
  border: 1px solid var(--border);
  padding: 24px;
  border-radius: var(--r-lg);
}

.setup-app-box, .setup-email-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 12px;
}

.qr-box {
  background: #FFFFFF;
  padding: 16px;
  border-radius: 16px;
  border: 2px dashed var(--border-soft);
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  width: 200px;
  height: 200px;
}

/* Asegurar que el SVG del QR escale bien */
.qr-box :deep(svg) {
  width: 100%;
  height: 100%;
}

.secret-code {
  background: var(--bg-page);
  padding: 4px 8px;
  border-radius: 6px;
  font-weight: 800;
  color: var(--text-900);
  border: 1px solid var(--border-soft);
}

.div-soft {
  height: 2px;
  background: var(--border-soft);
  margin: 24px 0;
  border-radius: var(--r-pill);
}

.font-code {
  font-size: 20px !important;
  letter-spacing: 6px;
  font-weight: 800;
}

/* Input 3D estándar */
.input-3d-lit {
  padding: 14px 16px;
  border-radius: var(--r-md);
  border: 2px solid var(--uth-verde-claro) !important;
  box-shadow: 0 0 0 3px var(--uth-verde-bg), inset 0 3px 6px rgba(0,0,0,0.04) !important;
  background: #FFFFFF !important;
  transition: all 0.2s ease;
  width: 100%;
}
.input-3d-lit:focus {
  border-color: var(--uth-verde) !important;
  box-shadow: 0 0 0 4px var(--uth-verde-ring), inset 0 2px 4px rgba(0,0,0,0.02) !important;
  outline: none;
}

/* Botón 3D Principal */
.btn-add-3d {
  background: var(--uth-verde);
  color: white;
  border: none;
  border-radius: var(--r-pill);
  padding: 14px;
  font-weight: 800;
  box-shadow: 0 6px 0 #007734, 0 10px 15px rgba(0, 182, 79, 0.3);
  transform: translateY(-2px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  cursor: pointer;
  margin-top: 16px;
}
.btn-add-3d:hover:not(:disabled) {
  transform: translateY(-4px);
  box-shadow: 0 8px 0 #007734, 0 14px 20px rgba(0, 182, 79, 0.4);
}
.btn-add-3d:active:not(:disabled) {
  transform: translateY(2px);
  box-shadow: 0 0 0 #007734;
}

/* ── ZONA DE PELIGRO (DESACTIVAR) ── */
.danger-zone {
  border-color: rgba(220, 38, 38, 0.15);
}
.danger-zone:hover {
  border-color: rgba(220, 38, 38, 0.3);
}

.input-danger {
  border-color: #FECACA !important;
  box-shadow: 0 0 0 3px #FEF2F2, inset 0 3px 6px rgba(0,0,0,0.04) !important;
}
.input-danger:focus {
  border-color: #EF4444 !important;
  box-shadow: 0 0 0 4px #FEE2E2 !important;
}

.btn-danger-3d {
  background: #EF4444;
  color: white;
  border: none;
  border-radius: var(--r-pill);
  padding: 12px 20px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 5px 0 #B91C1C, 0 8px 15px rgba(239, 68, 68, 0.3);
  transform: translateY(-2px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  margin-top: 12px;
}
.btn-danger-3d:hover:not(:disabled) {
  background: #DC2626;
  transform: translateY(-4px);
  box-shadow: 0 7px 0 #991B1B, 0 12px 20px rgba(239, 68, 68, 0.4);
}
.btn-danger-3d:active:not(:disabled) {
  transform: translateY(2px);
  box-shadow: 0 0 0 #991B1B;
}

/* Animaciones */
.alert-bounce {
  animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
</style>