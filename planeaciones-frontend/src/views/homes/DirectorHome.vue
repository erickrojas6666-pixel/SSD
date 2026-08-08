<template>
  <AppShell>
    <div class="hero">
      <div class="hero-tag"><Building2 :size="14" /> Director</div>
      <h2>Panel del Director</h2>
      <p>Este panel queda disponible para lo que definas más adelante.</p>
    </div>

    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num">1</div>
        <div>
          <h2>Accesos rápidos</h2>
          <p>Ve directo a la validación de secuencias de tu carrera.</p>
        </div>
      </div>

      <router-link :to="{ name: 'secuencias-director' }" class="card admin-shortcut">
        <div class="cp flex ic g4u">
          <div class="admin-shortcut-icon"><ShieldCheck :size="28" /></div>
          <div>
            <h3 class="ht-sm mb4">Validación de Secuencias</h3>
            <p class="sz-sm" style="color: var(--text-300)">
              Secuencias de tu carrera pendientes de tu decisión final.
            </p>
          </div>
        </div>
      </router-link>
    </div>
  </AppShell>
</template>

<script setup>
import { Building2, ShieldCheck } from 'lucide-vue-next'
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
