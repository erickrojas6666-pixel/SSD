<template>
  <div class="doc-header">
    <!-- Logo Izquierda: Universidades del Subsistema Tecnológico -->
    <div class="doc-header-logo-placeholder">
      <img src="/img/ut.png" alt="Logo Universidades Tecnológicas" class="logo-img">
    </div>

    <!-- Texto Central -->
    <div class="doc-header-text">
      <h1>Universidades del Subsistema Tecnológico</h1>
      <h2>Universidad Tecnológica Huejotzingo</h2>
      <h3 v-if="programa">{{ programa }}</h3>
      <h4>{{ subtitulo }}</h4>
    </div>

    <!-- Logo Derecha: UTH -->
    <div class="doc-header-logo-placeholder">
      <img src="/img/uth.webp" alt="Logo UTH" class="logo-img">
    </div>
  </div>
</template>

<script setup>
defineProps({
  subtitulo: { type: String, required: true },
  programa: { type: String, default: '' },
})
</script>
