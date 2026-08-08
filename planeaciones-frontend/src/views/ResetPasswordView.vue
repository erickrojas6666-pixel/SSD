<template>
  <div class="auth-wrap">
    <div class="card auth-card">
      <div class="cp">
        <div class="auth-brand">
          <div class="logo-cube">UTH</div>
          <div>
            <h1 class="ht-sm">Restablecer contraseña</h1>
            <p class="sz-xs" style="color: var(--text-300)">Elige tu nueva contraseña</p>
          </div>
        </div>

        <div v-if="successMsg" class="alert a-success">{{ successMsg }}</div>
        <div v-if="errorMsg" class="alert a-danger">{{ errorMsg }}</div>

        <form v-if="!successMsg" @submit.prevent="onSubmit" novalidate>
          <div class="field">
            <label class="fl">Correo institucional<span class="req">*</span></label>
            <input v-model.trim="email" type="email" class="input" readonly />
          </div>

          <div class="field">
            <label class="fl">Nueva contraseña<span class="req">*</span></label>
            <input
              v-model="password"
              type="password"
              class="input"
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
              class="input"
              placeholder="Repite la contraseña"
              autocomplete="new-password"
              required
              minlength="8"
            />
          </div>

          <button type="submit" class="btn btn-primary btn-lg auth-submit" :disabled="loading">
            {{ loading ? 'Guardando…' : 'Restablecer contraseña' }}
          </button>
        </form>

        <div class="auth-links">
          <router-link :to="{ name: 'login' }">Volver a iniciar sesión</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
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
  margin-top: var(--s5);
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
