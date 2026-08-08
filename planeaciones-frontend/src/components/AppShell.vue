<template>
  <div class="wrap">
    <aside class="sidebar">
      <div class="sb-brand">
        <div class="logo-cube">UTH</div>
        <div>
          <h1>Planeaciones</h1>
          <p>Didácticas UTH</p>
        </div>
      </div>

      <!-- Un bloque de navegación por cada rol que tenga el usuario -->
      <div v-for="grupo in menu" :key="grupo.rol" class="nav-sec">
        <div class="nav-lbl">{{ grupo.rol }}</div>
        <router-link
          v-for="item in grupo.items"
          :key="item.routeName"
          :to="{ name: item.routeName }"
          class="nav-a"
          :class="{ active: route.name === item.routeName }"
        >
          {{ item.label }}
        </router-link>
      </div>

      <div class="nav-sec sidebar-footer">
        <div class="nav-lbl">Mi cuenta</div>
        <router-link
          :to="{ name: 'perfil-2fa' }"
          class="nav-a"
          :class="{ active: route.name === 'perfil-2fa' }"
        >
          Seguridad (2FA)
        </router-link>
        <button class="nav-a nav-a-btn" @click="onLogout">Cerrar sesión</button>
      </div>
    </aside>

    <main class="content">
      <div class="flex jb ic mb4">
        <span class="sz-sm" style="color: var(--text-300)">
          Hola, <strong style="color: var(--text-700)">{{ auth.user?.nombre_completo }}</strong>
        </span>
      </div>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { menuFusionado } from '@/config/menus'
import router from '@/router'

const route = useRoute()
const auth = useAuthStore()
const menu = computed(() => menuFusionado(auth.roles))

async function onLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.sidebar {
  display: flex;
  flex-direction: column;
}
.sidebar-footer {
  margin-top: auto;
}
.nav-a-btn {
  width: 100%;
  text-align: left;
  border: none;
  background: none;
  cursor: pointer;
  font-family: gotham, 'Roboto', sans-serif;
}
</style>
