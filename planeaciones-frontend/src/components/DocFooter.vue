<template>
  <div class="doc-footer">
    <div class="doc-footer-codigo">
      <strong>CÓDIGO:</strong> {{ codigo }}<br>
      <strong>REVISIÓN:</strong> {{ revision }}
    </div>
    <div class="doc-footer-pagina">
      Página <strong>{{ pagina }}</strong> de <strong>{{ totalPaginas }}</strong>
    </div>
  </div>
</template>

<script setup>
// Réplica del pie de página del formato oficial (UTH-ACA-DC-F-PSDA/02):
// código de la ficha a la izquierda, número de página a la derecha.
defineProps({
  codigo: { type: String, default: 'UTH-ACA-DC-F-PSDA/02' },
  revision: { type: [String, Number], default: 3 },
  pagina: { type: [String, Number], required: true },
  totalPaginas: { type: [String, Number], required: true },
})
</script>
