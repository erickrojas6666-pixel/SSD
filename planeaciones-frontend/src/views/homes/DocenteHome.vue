<template>
  <AppShell>
    <div class="hero">
      <div class="hero-tag"><PenLine :size="14" /> Docente</div>
      <h2>Panel del Docente</h2>
      <p>Este panel queda disponible para lo que definas más adelante.</p>
    </div>

    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num">1</div>
        <div>
          <h2>Accesos rápidos</h2>
          <p>Ve directo a tus secuencias didácticas.</p>
        </div>
      </div>

      <router-link :to="{ name: 'secuencias-docente' }" class="card admin-shortcut">
        <div class="cp flex ic g4u">
          <div class="admin-shortcut-icon"><FileEdit :size="28" /></div>
          <div>
            <h3 class="ht-sm mb4">Mis Secuencias Didácticas</h3>
            <p class="sz-sm" style="color: var(--text-300)">
              Borradores en desarrollo e historial de secuencias validadas.
            </p>
          </div>
        </div>
      </router-link>
    </div>
  </AppShell>
</template>

<script setup>
import { PenLine, FileEdit } from 'lucide-vue-next'
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
