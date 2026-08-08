<template>
  <div class="auth-wrap">
    <div class="card auth-card">
      <div class="cp">
        <div class="auth-brand">
          <div class="logo-cube">UTH</div>
          <div>
            <h1 class="ht-sm">Recuperar contraseña</h1>
            <p class="sz-xs" style="color: var(--text-300)">Te enviaremos un enlace a tu correo</p>
          </div>
        </div>

        <div v-if="successMsg" class="alert a-success">{{ successMsg }}</div>
        <div v-if="errorMsg" class="alert a-danger">{{ errorMsg }}</div>

        <form v-if="!successMsg" @submit.prevent="onSubmit" novalidate>
          <div class="field">
            <label class="fl">Correo institucional<span class="req">*</span></label>
            <input
              v-model.trim="email"
              type="email"
              class="input"
              placeholder="usuario@uth.edu.mx"
              autocomplete="username"
              required
            />
            <p class="fh">Ingresa el correo con el que te registraste en el sistema.</p>
          </div>

          <button type="submit" class="btn btn-primary btn-lg auth-submit" :disabled="loading">
            {{ loading ? 'Enviando…' : 'Enviar enlace de recuperación' }}
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
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const email = ref('')
const loading = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

async function onSubmit() {
  loading.value = true
  errorMsg.value = ''
  try {
    const data = await auth.forgotPassword(email.value)
    successMsg.value = data.message
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Ocurrió un error, intenta de nuevo.'
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
