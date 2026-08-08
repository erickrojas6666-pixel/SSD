<template>
  <div class="auth-wrap">
    <div class="card auth-card">
      <div class="cp">
        <div class="auth-brand">
          <div class="logo-cube">UTH</div>
          <div>
            <h1 class="ht-sm">Verificación en dos pasos</h1>
            <p class="sz-xs" style="color: var(--text-300)">
              {{ metodo === 'app' ? 'Ingresa el código de tu app autenticadora' : 'Ingresa el código enviado a tu correo' }}
            </p>
          </div>
        </div>

        <div v-if="errorMsg" class="alert a-danger">{{ errorMsg }}</div>
        <div v-if="infoMsg" class="alert a-info">{{ infoMsg }}</div>

        <form @submit.prevent="onSubmit" novalidate>
          <div class="field">
            <label class="fl">Código de verificación<span class="req">*</span></label>
            <input
              v-model.trim="code"
              type="text"
              inputmode="numeric"
              class="input"
              placeholder="123456"
              autocomplete="one-time-code"
              maxlength="12"
              required
            />
            <p class="fh">También puedes usar uno de tus códigos de recuperación.</p>
          </div>

          <button type="submit" class="btn btn-primary btn-lg auth-submit" :disabled="loading">
            {{ loading ? 'Verificando…' : 'Verificar' }}
          </button>
        </form>

        <div v-if="metodo === 'email'" class="auth-links">
          <button class="btn btn-ghost btn-sm" :disabled="reenviando" @click="onResend">
            {{ reenviando ? 'Enviando…' : 'Reenviar código' }}
          </button>
        </div>

        <div class="auth-links">
          <router-link :to="{ name: 'login' }">Cancelar y volver al login</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
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
  // Si no hay un reto pendiente (ej. se recargó la página), regresa al login
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
    errorMsg.value = e.response?.data?.message || 'Código incorrecto.'
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
.auth-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-page);
  padding: var(--s5);
}
.auth-card {
  width: 100%;
  max-width: 380px;
}
.auth-brand {
  display: flex;
  align-items: center;
  gap: var(--s3);
  margin-bottom: var(--s6);
}
.auth-submit {
  width: 100%;
  justify-content: center;
  margin-top: var(--s2);
}
.auth-links {
  text-align: center;
  margin-top: var(--s4);
  font-size: var(--p-sm);
}
.auth-links a {
  color: var(--uth-verde);
  text-decoration: none;
}
.auth-links a:hover {
  text-decoration: underline;
}
</style>
