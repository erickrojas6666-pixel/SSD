<template>
  <div class="wrap light-3d-wrap">
    <!-- SIDEBAR VERDE FUERTE CON PROFUNDIDAD 3D FIJADA A LA PANTALLA -->
    <aside class="sidebar strong-green-sidebar">
      <div class="sb-brand">
        <!-- Cubo 3D Exagerado -->
        <div class="logo-cube bg-white">
          <GraduationCap :size="24" color="#008C3D" stroke-width="2.5" />
        </div>
        <div>
          <h1 class="brand-text">Planeaciones</h1>
          <p class="brand-sub">Didácticas UTH</p>
        </div>
      </div>

      <!-- MENÚ DE NAVEGACIÓN (Con Scroll Interno) -->
      <div class="nav-scroll">
        <div v-for="grupo in menu" :key="grupo.rol" class="nav-sec">
          <div class="nav-lbl">{{ grupo.rol }}</div>
          <router-link
            v-for="item in grupo.items"
            :key="item.routeName"
            :to="{ name: item.routeName }"
            class="nav-a pill-nav-3d"
            :class="{ active: route.name === item.routeName }"
          >
            <component :is="getItemIcon(item.routeName)" :size="20" class="nav-icon" />
            {{ item.label }}
          </router-link>
        </div>
      </div>

      <!-- FOOTER DEL SIDEBAR (Anclado siempre abajo) -->
      <div class="sidebar-footer">
        <div class="nav-lbl">Mi cuenta</div>
        
        <!-- Botón 3D Seguridad -->
        <router-link :to="{ name: 'perfil-2fa' }" class="nav-a pill-nav-3d" :class="{ active: route.name === 'perfil-2fa' }">
          <ShieldCheck :size="20" class="nav-icon shield-ic" />
          Seguridad (2FA)
        </router-link>

        <!-- Botón 3D Cerrar sesión (Se aplasta al hacer clic) -->
        <button class="logout-btn-3d-max" @click="onLogout">
          <LogOut :size="20" class="logout-ic" />
          Cerrar sesión
        </button>
      </div>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="content">
      <!-- TOPBAR -->
      <div class="topbar-soft flex jb ic mb4">
        <div class="topbar-left flex ic">
          <span class="welcome-text">
            ¡Hola, <strong>{{ auth.user?.nombre_completo }}</strong>!
          </span>
        </div>
        <div class="topbar-right flex ic">
          <!-- Avatar 3D Físico -->
          <div class="greet-avatar" :title="auth.user?.nombre_completo">{{ iniciales }}</div>
        </div>
      </div>

      <!-- VISTA DINÁMICA -->
      <div class="content-body">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { menuFusionado } from '@/config/menus'
import router from '@/router'
import { ShieldCheck, LogOut, LayoutDashboard, GraduationCap, Users, FileText, Settings } from 'lucide-vue-next'

const route = useRoute()
const auth = useAuthStore()
const menu = computed(() => menuFusionado(auth.roles))

function getItemIcon(routeName) {
  if (routeName?.includes('academico')) return GraduationCap
  if (routeName?.includes('usuarios')) return Users
  if (routeName?.includes('reporte')) return FileText
  if (routeName?.includes('config')) return Settings
  return LayoutDashboard
}

const iniciales = computed(() => {
  const nombre = auth.user?.nombre_completo || ''
  return (
    nombre
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((p) => p[0]?.toUpperCase())
      .join('') || '?'
  )
})

async function onLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
/* Contenedor principal */
.light-3d-wrap {
  display: flex;
  min-height: 100vh;
  background-color: var(--bg-page);
}

/* ── SIDEBAR ARREGLADA (FIJA A LA PANTALLA) ── */
.strong-green-sidebar {
  width: 275px;
  height: 100vh; /* Obliga a la barra a medir exactamente el alto de la pantalla */
  position: sticky; /* Se queda pegada aunque hagas scroll */
  top: 0;
  background: linear-gradient(180deg, #008C3D 0%, #00662C 100%);
  border-right: none;
  display: flex;
  flex-direction: column;
  padding: 24px 18px;
  box-shadow: 6px 0 30px rgba(0, 110, 48, 0.25), inset -8px 0 20px rgba(0, 0, 0, 0.1); /* Efecto de tubo cilíndrico */
  z-index: 10;
}

.sb-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
  padding: 0 8px;
}

/* ── CUBO LOGO 3D EXAGERADO ── */
.logo-cube {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #FFFFFF;
  /* Labio inferior súper grueso (Efecto físico real) */
  box-shadow: 0 6px 0 #A7F3D0, 0 12px 20px rgba(0, 0, 0, 0.2);
  transform: translateY(-4px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.logo-cube:hover {
  transform: translateY(-6px) rotate(-6deg);
  box-shadow: 0 8px 0 #A7F3D0, 0 16px 25px rgba(0, 0, 0, 0.2);
}
.logo-cube:active {
  transform: translateY(2px) rotate(0deg);
  box-shadow: 0 0 0 #A7F3D0, 0 4px 6px rgba(0, 0, 0, 0.2);
}

.brand-text {
  font-family: 'Sora', sans-serif;
  font-weight: 800;
  font-size: 20px;
  color: #FFFFFF;
  margin: 0;
  letter-spacing: -0.5px;
  text-shadow: 0 3px 6px rgba(0, 0, 0, 0.25);
}
.brand-sub {
  font-size: 12px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.85);
  margin: 0;
}

/* ── CONTENEDOR DE SCROLL PARA EL MENÚ ── */
.nav-scroll {
  flex: 1;
  overflow-y: auto;
  scrollbar-width: none; /* Oculta la barra de scroll nativa para mayor limpieza */
  padding-bottom: 20px;
}
.nav-scroll::-webkit-scrollbar {
  display: none;
}

.nav-sec {
  margin-bottom: 24px;
}

.nav-lbl {
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.5);
  letter-spacing: 1.5px;
  margin-bottom: 14px;
  padding: 0 12px;
}

/* ── BOTONES DEL MENÚ (EFECTO FÍSICO 3D) ── */
.pill-nav-3d {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 16px;
  margin-bottom: 12px;
  border-radius: var(--r-pill);
  color: rgba(255, 255, 255, 0.85);
  text-decoration: none;
  font-size: 14.5px;
  font-weight: 700;
  background: rgba(0, 0, 0, 0.1);
  border: 2px solid transparent;
  /* Labio oscuro simulando relieve */
  box-shadow: 0 4px 0 rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.nav-icon {
  color: rgba(255, 255, 255, 0.6);
  transition: color 0.15s ease;
}

.pill-nav-3d:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.2);
  color: #FFFFFF;
  box-shadow: 0 6px 0 rgba(0, 0, 0, 0.2), 0 12px 20px rgba(0, 0, 0, 0.2);
  transform: translateY(-4px);
}
.pill-nav-3d:hover .nav-icon { color: #FFFFFF; }

/* Efecto Aplastar */
.pill-nav-3d:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 rgba(0, 0, 0, 0.2);
}

/* ── BOTÓN ACTIVO BLANCO 3D ── */
.pill-nav-3d.active {
  background: #FFFFFF;
  color: #008C3D;
  border-color: #FFFFFF;
  /* Labio inferior verde muy oscuro */
  box-shadow: 0 6px 0 #004D21, 0 12px 20px rgba(0, 0, 0, 0.25);
  transform: translateY(-4px);
}
.pill-nav-3d.active .nav-icon { color: #008C3D; }

.pill-nav-3d.active:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 #004D21;
}

/* ── FOOTER ANCLADO ABAJO Y CERRAR SESIÓN PRO ── */
.sidebar-footer {
  margin-top: auto;
  padding-top: 18px;
  border-top: 2px dashed rgba(255, 255, 255, 0.2);
  flex-shrink: 0;
}

.logout-btn-3d-max {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px 16px;
  border-radius: var(--r-pill);
  font-size: 14.5px;
  font-weight: 800;
  cursor: pointer;
  margin-top: 8px;
  
  /* Estilo por defecto (Rojo tenue pero 3D) */
  background: rgba(239, 68, 68, 0.15);
  border: 2px solid rgba(239, 68, 68, 0.3);
  color: #FECACA;
  box-shadow: 0 4px 0 rgba(153, 27, 27, 0.3);
  transform: translateY(-2px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Hover: Se vuelve un botón rojo explosivo */
.logout-btn-3d-max:hover {
  background: #EF4444;
  color: #FFFFFF;
  border-color: #EF4444;
  box-shadow: 0 6px 0 #991B1B, 0 14px 20px rgba(239, 68, 68, 0.4);
  transform: translateY(-4px);
}

/* Aplastar al hacer clic */
.logout-btn-3d-max:active {
  transform: translateY(2px);
  box-shadow: 0 0 0 #991B1B;
}

.logout-ic { color: #FECACA; }
.logout-btn-3d-max:hover .logout-ic { color: #FFFFFF; }
.shield-ic { color: #38BDF8; }

/* ── TOPBAR Y AVATAR ── */
.topbar-soft {
  padding: 20px 32px;
}

.welcome-text {
  font-size: 16px;
  color: var(--text-900);
}
.welcome-text strong {
  font-family: 'Sora', sans-serif;
  font-weight: 800;
  color: var(--uth-verde);
  font-size: 18px;
}

/* Avatar con labios 3D pronunciados */
.greet-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--uth-verde);
  color: #FFFFFF;
  border: 2px solid white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14.5px;
  font-weight: 800;
  box-shadow: 0 6px 0 #007734, 0 10px 15px rgba(0, 0, 0, 0.1);
  transform: translateY(-3px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  cursor: pointer;
}
.greet-avatar:hover {
  transform: translateY(-6px) rotate(5deg);
  box-shadow: 0 8px 0 #007734, 0 15px 25px rgba(0, 0, 0, 0.15);
}
.greet-avatar:active {
  transform: translateY(2px) rotate(0deg);
  box-shadow: 0 0 0 #007734;
}

.content-body {
  padding: 0 32px 32px 32px;
}
</style>