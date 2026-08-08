<template>
  <div class="auth-wrap">
    <div class="card auth-card">
      <div class="cp">
        <div class="auth-brand">
          <div class="logo-cube">UTH</div>
          <div>
            <h1 class="ht-sm">Planeaciones Didácticas</h1>
            <p class="sz-xs" style="color: var(--text-300)">Universidad Tecnológica de Huejotzingo</p>
          </div>
        </div>

        <div v-if="errorMsg" class="alert a-danger">{{ errorMsg }}</div>

        <form @submit.prevent="onSubmit" novalidate>
          <div class="field">
            <label class="fl">Correo institucional<span class="req">*</span></label>
            <input
              v-model.trim="email"
              type="email"
              class="input"
              :class="{ 'input-err': errorMsg }"
              placeholder="usuario@uth.edu.mx"
              autocomplete="username"
              required
            />
          </div>

          <div class="field">
            <label class="fl">Contraseña<span class="req">*</span></label>
            <input
              v-model="password"
              type="password"
              class="input"
              :class="{ 'input-err': errorMsg }"
              placeholder="••••••••"
              autocomplete="current-password"
              required
            />
          </div>

          <button type="submit" class="btn btn-primary btn-lg auth-submit" :disabled="loading">
            {{ loading ? 'Ingresando…' : 'Iniciar sesión' }}
          </button>
        </form>

        <div class="auth-links">
          <router-link :to="{ name: 'forgot-password' }">¿Olvidaste tu contraseña?</router-link>
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
