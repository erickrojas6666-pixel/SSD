<template>
  <button type="button" class="icon-btn" :class="`icon-btn-${variant}`" :title="title" :aria-label="title"
    :disabled="disabled">
    <slot />
  </button>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  variant: {
    type: String,
    default: 'default', // default | primary | danger
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})
</script>

<style scoped>
.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: var(--r-pill);
  border: 1px solid var(--border);
  background: var(--bg-white);
  color: var(--text-500);
  cursor: pointer;
  transition:
    background var(--tf) var(--ease),
    color var(--tf) var(--ease),
    border-color var(--tf) var(--ease),
    opacity var(--tf) var(--ease);
}

/* Hover */
.icon-btn:hover:not(:disabled) {
  background: var(--bg-soft);
  color: var(--text-700);
}

/* Variantes */
.icon-btn-primary {
  color: var(--uth-verde);
  border-color: var(--uth-verde);
}

.icon-btn-primary:hover:not(:disabled) {
  background: var(--uth-verde);
  color: white;
}

.icon-btn-danger {
  color: var(--danger);
  border-color: var(--danger);
}

.icon-btn-danger:hover:not(:disabled) {
  background: var(--danger);
  color: white;
}

/* Estado deshabilitado */
.icon-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  pointer-events: none;
}
</style>
