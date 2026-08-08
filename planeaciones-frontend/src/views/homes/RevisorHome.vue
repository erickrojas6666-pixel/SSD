<template>
  <AppShell>
    <div class="hero">
      <div class="hero-tag"><SearchCheck :size="14" /> Revisor</div>
      <h2>Panel del Revisor</h2>
      <p>Este panel queda disponible para lo que definas más adelante.</p>
    </div>

    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num">1</div>
        <div>
          <h2>Accesos rápidos</h2>
          <p>Ve directo a tu cola de revisión.</p>
        </div>
      </div>

      <router-link :to="{ name: 'secuencias-revisor' }" class="card admin-shortcut">
        <div class="cp flex ic g4u">
          <div class="admin-shortcut-icon"><ClipboardList :size="28" /></div>
          <div>
            <h3 class="ht-sm mb4">Cola de Revisión</h3>
            <p class="sz-sm" style="color: var(--text-300)">
              Secuencias enviadas por los docentes, pendientes de revisión.
            </p>
          </div>
        </div>
      </router-link>
    </div>
  </AppShell>
</template>

<script setup>
import { SearchCheck, ClipboardList } from 'lucide-vue-next'
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
