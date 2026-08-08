<template>
  <div class="auth-wrap">
    <div class="card auth-card">
      <div class="cp">
        <div class="auth-brand">
          <div class="logo-cube">UTH</div>
          <div>
            <h1 class="ht-sm">Confirmación de cuenta</h1>
          </div>
        </div>

        <div v-if="cargando" class="sz-sm" style="text-align:center; color:var(--text-300)">
          Confirmando tu cuenta…
        </div>

        <div v-else-if="exito" class="alert a-success">{{ mensaje }}</div>
        <div v-else class="alert a-danger">{{ mensaje }}</div>

        <div class="auth-links">
          <router-link :to="{ name: 'login' }">Ir a iniciar sesión</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
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
