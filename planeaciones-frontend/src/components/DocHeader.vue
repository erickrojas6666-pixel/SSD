<template>
  <div class="doc-header">
    <div class="doc-header-logo-placeholder">Escudo UTH</div>
    <div class="doc-header-text">
      <h1>Universidad Tecnológica de Huejotzingo</h1>
      <h2 v-if="programa">{{ programa }}</h2>
      <h3>{{ subtitulo }}</h3>
    </div>
    <div class="doc-header-logo-placeholder">Escudo UT/UP</div>
  </div>
</template>

<script setup>
defineProps({
  subtitulo: { type: String, required: true },
  programa: { type: String, default: '' },
})
</script>
