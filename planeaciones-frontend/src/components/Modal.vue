<template>
  <div class="mo modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="mh">
        <span class="mt2">{{ titulo }}</span>
        <button class="mc" type="button" @click="$emit('close')">✕</button>
      </div>
      <div class="mb2">
        <slot />
      </div>
      <div class="mf">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({ titulo: { type: String, required: true } })
defineEmits(['close'])
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 50;
}
</style>
