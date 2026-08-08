<template>
  <AppShell>
    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num"><ShieldCheck :size="20" /></div>
        <div>
          <h2>Autenticación en dos pasos</h2>
          <p>Protege tu cuenta con un segundo factor al iniciar sesión.</p>
        </div>
      </div>

      <div v-if="errorMsg" class="alert a-danger">{{ errorMsg }}</div>
      <div v-if="successMsg" class="alert a-success">{{ successMsg }}</div>

      <!-- Estado actual -->
      <div class="card">
        <div class="cp">
          <p class="sz-md">
            Estado actual:
            <span v-if="dosFAActiva" class="badge b-verde">Activada ({{ metodoActual === 'app' ? 'App autenticadora' : 'Correo' }})</span>
            <span v-else class="badge b-gris">Desactivada</span>
          </p>
        </div>
      </div>

      <!-- Desactivar -->
      <div v-if="dosFAActiva" class="card">
        <div class="cp">
          <h3 class="ht-sm mb4">Desactivar 2FA</h3>
          <div class="field">
            <label class="fl">Confirma tu contraseña<span class="req">*</span></label>
            <input v-model="passwordDisable" type="password" class="input" placeholder="••••••••" />
          </div>
          <button class="btn btn-danger" :disabled="loading" @click="onDisable">Desactivar</button>
        </div>
      </div>

      <!-- Activar -->
      <div v-else class="card">
        <div class="cp">
          <h3 class="ht-sm mb4">Elige un método</h3>

          <div v-if="!setupData" class="br mb4">
            <button class="btn btn-outline" :disabled="loading" @click="onEnable('app')">App autenticadora</button>
            <button class="btn btn-outline" :disabled="loading" @click="onEnable('email')">Código por correo</button>
          </div>

          <div v-if="setupData">
            <div v-if="setupData.method === 'app'" class="mb4">
              <p class="sz-sm mb4">Escanea este código QR con Google Authenticator, Authy, etc.</p>
              <div v-html="setupData.qr_svg" style="width:200px"></div>
              <p class="fh">O ingresa manualmente: <code>{{ setupData.secret }}</code></p>
            </div>
            <div v-else class="mb4">
              <p class="sz-sm">Te enviamos un código de confirmación a tu correo.</p>
            </div>

            <div class="field">
              <label class="fl">Código de confirmación<span class="req">*</span></label>
              <input v-model.trim="confirmCode" type="text" class="input" placeholder="123456" />
            </div>

            <button class="btn btn-primary" :disabled="loading" @click="onConfirm">Confirmar y activar</button>
          </div>
        </div>
      </div>

      <!-- Códigos de recuperación (solo se muestran una vez, tras confirmar) -->
      <div v-if="recoveryCodes.length" class="card">
        <div class="cp">
          <div class="alert a-warning">
            Guarda estos códigos de recuperación en un lugar seguro. Cada uno solo se puede usar una vez y no volverán a mostrarse.
          </div>
          <div class="g2">
            <code v-for="c in recoveryCodes" :key="c" class="code" style="text-align:center">{{ c }}</code>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ShieldCheck } from 'lucide-vue-next'
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
    errorMsg.value = e.response?.data?.message || 'No se pudo desactivar.'
  } finally {
    loading.value = false
  }
}
</script>
