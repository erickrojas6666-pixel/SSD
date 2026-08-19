<template>
  <AppShell>
    <!-- ============ HERO SECTION CON EFECTO 3D ============ -->
    <section class="hero-tilt" @mousemove="handleHeroMouseMove" @mouseleave="handleHeroMouseLeave" ref="heroRef">
      <div class="hero-content">
        <div class="hero-tag">
          <PenLine :size="14" class="spin-slow" /> Docente
        </div>
        <h1>Panel del <span class="accent">Docente</span></h1>
        <p>Crea, planifica y gestiona tus secuencias didácticas de manera ágil y centralizada en este espacio de trabajo.</p>
      </div>
      <!-- Círculos decorativos de fondo estilo "Soft UI" -->
      <div class="hero-blob blob-1"></div>
      <div class="hero-blob blob-2"></div>
    </section>

    <!-- ============ ACCESOS RÁPIDOS ============ -->
    <div class="sec">
      <div class="sec-hdr">
        <div class="sec-num">1</div>
        <div>
          <h2>Accesos rápidos</h2>
          <p>Ve directo a tu área de planificación y redacción.</p>
        </div>
      </div>

      <div class="g2">
        <!-- Tarjeta: Mis Secuencias Didácticas -->
        <router-link :to="{ name: 'secuencias-docente' }" class="card-tilt">
          <div class="card-inner">
            <div class="card-icon-wrap c-violet">
              <FileEdit :size="28" />
            </div>
            <div class="card-info">
              <h3>Mis Secuencias Didácticas</h3>
              <p>Accede a tus borradores en desarrollo o consulta el historial de tus planeaciones ya validadas.</p>
            </div>
            <div class="card-arrow-wrap">
              <ChevronRight :size="18" />
            </div>
          </div>
        </router-link>
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { ref } from 'vue'
import { PenLine, FileEdit, ChevronRight } from 'lucide-vue-next'
import AppShell from '@/components/AppShell.vue'

// Lógica de efecto Parallax / Inclinación 3D del Hero
const heroRef = ref(null)

function handleHeroMouseMove(e) {
  const card = heroRef.value
  if (!card) return
  const rect = card.getBoundingClientRect()
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  const centerX = rect.width / 2
  const centerY = rect.height / 2
  const rotateX = ((y - centerY) / centerY) * -4
  const rotateY = ((x - centerX) / centerX) * 4

  card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-2px)`
}

function handleHeroMouseLeave() {
  const card = heroRef.value
  if (!card) return
  card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateY(0)'
}
</script>

<style scoped>
/* ---- HERO CON EFECTO PROFUNDO 3D ---- */
.hero-tilt {
  position: relative;
  overflow: hidden;
  border-radius: var(--r-xl);
  background: var(--bg-white);
  border: 1px solid var(--border);
  padding: 42px 48px;
  margin-bottom: 38px;
  box-shadow: var(--sh-sm);
  transition: transform 0.1s ease-out, box-shadow 0.3s ease, border-color 0.3s ease;
  will-change: transform;
}

.hero-tilt:hover {
  box-shadow: var(--sh-lg);
  border-color: rgba(139, 92, 246, 0.4); /* Violeta claro */
}

.hero-content {
  position: relative;
  z-index: 2;
}

.hero-tag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-page);
  padding: 8px 16px;
  border-radius: var(--r-pill);
  font-size: 12.5px;
  font-weight: 700;
  color: #8B5CF6; /* Tinte Violeta para el Docente */
  margin-bottom: 18px;
  border: 1px solid var(--border);
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}

.hero-tilt h1 {
  font-family: 'Sora', sans-serif;
  font-weight: 800;
  font-size: 38px;
  line-height: 1.15;
  color: var(--text-900);
  margin: 0 0 12px 0;
  letter-spacing: -1px;
}

.hero-tilt h1 .accent {
  color: var(--uth-verde);
}

.hero-tilt p {
  font-size: 15px;
  color: var(--text-500);
  margin: 0;
  max-width: 500px;
  line-height: 1.6;
}

/* Manchas decorativas tipo Soft UI en el fondo del Hero */
.hero-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(40px);
  z-index: 1;
}
.blob-1 {
  width: 300px;
  height: 300px;
  background: rgba(139, 92, 246, 0.08); /* Violeta suave */
  top: -50px;
  right: -50px;
}
.blob-2 {
  width: 200px;
  height: 200px;
  background: rgba(0, 182, 79, 0.06); /* Verde suave */
  bottom: -50px;
  right: 150px;
}

/* ---- TARJETAS CON EFECTO HOVER INTERACTIVO 3D ---- */
.card-tilt {
  position: relative;
  background: var(--bg-white);
  border: 1px solid var(--border);
  border-radius: var(--r-xl);
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  box-shadow: var(--sh-xs);
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  display: block;
}

.card-inner {
  position: relative;
  z-index: 2;
  padding: 26px;
  display: flex;
  align-items: center;
  gap: 18px;
}

.card-tilt:hover {
  transform: translateY(-6px) scale(1.01);
  box-shadow: var(--sh-md);
  border-color: #8B5CF6; /* Borde violeta al pasar el cursor */
}

/* Iconos estilizados caricatura */
.card-icon-wrap {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.3s var(--ease-spring), box-shadow 0.3s ease;
}

.c-violet {
  background: rgba(139, 92, 246, 0.1);
  color: #8B5CF6;
  border: 1px solid rgba(139, 92, 246, 0.2);
}

.card-tilt:hover .card-icon-wrap {
  transform: scale(1.1) rotate(-8deg);
  box-shadow: 0 8px 16px rgba(139, 92, 246, 0.15);
}

.card-info {
  flex: 1;
}

.card-info h3 {
  font-family: 'Sora', sans-serif;
  font-weight: 700;
  font-size: 17px;
  margin: 0 0 6px 0;
  color: var(--text-900);
  transition: color 0.2s;
}

.card-tilt:hover .card-info h3 {
  color: #8B5CF6;
}

.card-info p {
  font-size: 13.5px;
  color: var(--text-500);
  line-height: 1.5;
  margin: 0;
}

/* Flechas de navegación animadas */
.card-arrow-wrap {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 1px solid var(--border);
  background: var(--bg-soft);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-500);
  flex-shrink: 0;
  transition: all 0.25s var(--ease-spring);
}

.card-tilt:hover .card-arrow-wrap {
  background: #8B5CF6;
  border-color: #8B5CF6;
  color: white;
  transform: translateX(4px) scale(1.1);
  box-shadow: 0 4px 10px rgba(139, 92, 246, 0.3);
}

/* ---- ANIMACIÓN DE ROTACIÓN LENTA ---- */
.spin-slow {
  animation: spin 8s linear infinite;
}
</style>