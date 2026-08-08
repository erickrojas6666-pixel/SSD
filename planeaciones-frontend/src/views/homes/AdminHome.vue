<template>
  <AppShell>
    <div class="hero">
      <div class="hero-tag">
        <Settings :size="14" /> Administrador
      </div>
      <h2>Panel de Administración</h2>
      <p>Gestión de catálogos y usuarios del sistema.</p>
    </div>

    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num">1</div>
        <div>
          <h2>Catálogos</h2>
          <p>Acceso rápido al catálogo académico que administras.</p>
        </div>
      </div>

      <div class="g2">
        <router-link :to="{ name: 'admin-academico' }" class="card admin-shortcut">
          <div class="cp flex ic g4u">
            <div class="admin-shortcut-icon">
              <GraduationCap :size="28" />
            </div>
            <div>
              <h3 class="ht-sm mb4">Carreras y Especialidades</h3>
              <p class="sz-sm" style="color: var(--text-300)">
                Ver, crear, editar, activar y desactivar carreras y especialidades.
              </p>
            </div>
          </div>
        </router-link>

        <router-link :to="{ name: 'admin-usuarios' }" class="card admin-shortcut">
          <div class="cp flex ic g4u">
            <div class="admin-shortcut-icon">
              <Users :size="28" />
            </div>
            <div>
              <h3 class="ht-sm mb4">Usuarios</h3>
              <p class="sz-sm" style="color: var(--text-300)">
                Crear cuentas, asignar roles, materias de docentes y carreras de directores.
              </p>
            </div>
          </div>
        </router-link>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { Settings, GraduationCap, Users } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'
</script>

<style scoped>
.admin-shortcut {
  display: block;
  text-decoration: none;
  color: inherit;
  transition: box-shadow var(--tf), transform var(--tf);
}

.admin-shortcut:hover {
  box-shadow: var(--sh-md);
  transform: translateY(-2px);
}

.admin-shortcut-icon {
  color: var(--uth-verde);
  flex-shrink: 0;
}
</style>
