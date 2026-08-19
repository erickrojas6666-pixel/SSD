<template>
  <div class="editor-focus-layout">
    <!-- ═══ ESTADO DE CARGA ═══ -->
    <div v-if="cargando" class="editor-loading">
      <Loader2 :size="48" class="spin-slow mx-auto mb4" color="#00B64F" />
      <h2 class="ht-sm">Cargando secuencia...</h2>
      <p class="text-dim">Preparando el entorno de trabajo</p>
    </div>

    <div v-else-if="secuencia" class="dashboard-layout">

      <!-- ═══ Indicador flotante de autoguardado ═══ -->
      <transition name="fade-guardado">
        <div v-if="estadoGuardado" class="badge-autoguardado" :class="`ag-${estadoGuardado}`">
          <Loader2 v-if="estadoGuardado === 'guardando'" :size="14" class="spin" />
          <CheckCircle2 v-else-if="estadoGuardado === 'guardado'" :size="14" />
          <XCircle v-else :size="14" />
          <span v-if="estadoGuardado === 'guardando'">Guardando en la nube…</span>
          <span v-else-if="estadoGuardado === 'guardado'">Guardado local</span>
          <span v-else>Error de conexión</span>
        </div>
      </transition>

      <!-- Grid principal con estado colapsable -->
      <div class="dash-grid editor-grid" :class="{ 'sidebar-collapsed': menuContraido }">

        <!-- =========================================
             PANEL IZQUIERDO: ÍNDICE DE LA SECUENCIA
        ========================================== -->
        <aside class="editor-outline widget-contorno" :class="{ 'collapsed': menuContraido }">

          <div class="sidebar-header">
            <div v-if="!menuContraido" class="outline-brand">
              <div class="icon-wrap-3d-small">
                <Layers :size="20" color="#00B64F" stroke-width="2" />
              </div>
              <div style="min-width:0;">
                <h3 class="ht-sm">Planeación</h3>
                <p class="sz-xs text-dim truncate" :title="secuencia.asignatura?.nombre">{{ secuencia.asignatura?.nombre
                  }}</p>
              </div>
            </div>

            <button class="btn-toggle-menu" @click="menuContraido = !menuContraido"
              :title="menuContraido ? 'Expandir menú' : 'Contraer menú'">
              <Menu :size="20" color="var(--text-600)" />
            </button>
          </div>

          <div class="div-soft" :class="{ 'my-2': menuContraido }"></div>

          <div class="nav-sec">
            <button class="nav-btn-3d btn-volver" @click="router.back()" title="Volver al menú">
              <ArrowLeft :size="18" /> <span v-if="!menuContraido">Volver al menú</span>
            </button>
          </div>

          <div class="nav-sec">
            <div v-if="!menuContraido" class="nav-lbl">Documento Principal</div>
            <button class="nav-btn-3d" :class="{ active: seccion === 'caratula' }" @click="seccion = 'caratula'"
              title="Carátula">
              <FileText :size="16" /> <span v-if="!menuContraido">Carátula</span>
            </button>
          </div>

          <div v-if="secuencia.unidades.length" class="nav-sec">
            <div v-if="!menuContraido" class="nav-lbl">Unidades de Aprendizaje</div>
            <div v-for="(u, i) in secuencia.unidades" :key="u.id" class="unidad-group">

              <button class="nav-btn-3d btn-unidad" @click="toggleGrupo(u.id)" :title="u.nombre || `Unidad ${i + 1}`">
                <span class="num-badge-3d">{{ i + 1 }}</span>
                <span v-if="!menuContraido" class="truncate" style="flex:1">{{ u.nombre || `Unidad ${i + 1}` }}</span>
                <ChevronRight v-if="!menuContraido" :size="16" class="nav-chevron"
                  :class="{ open: gruposAbiertos[u.id] }" />
              </button>

              <div v-show="gruposAbiertos[u.id]" class="unidad-hijos">
                <button class="nav-btn-3d child-btn" :class="{ active: seccion === `unidad-${u.id}` }"
                  @click="seccion = `unidad-${u.id}`" title="B. Info unidad">
                  <Info :size="14" /> <span v-if="!menuContraido">B. Info unidad</span>
                </button>
                <button class="nav-btn-3d child-btn" :class="{ active: seccion === `unidad-${u.id}-evaluacion` }"
                  @click="seccion = `unidad-${u.id}-evaluacion`" title="C. Evaluación">
                  <ClipboardList :size="14" /> <span v-if="!menuContraido">C. Evaluación</span>
                </button>
                <button class="nav-btn-3d child-btn" :class="{ active: seccion === `unidad-${u.id}-secuencia` }"
                  @click="seccion = `unidad-${u.id}-secuencia`" title="D. Secuencia">
                  <Layers :size="14" /> <span v-if="!menuContraido">D. Secuencia</span>
                </button>
              </div>
            </div>
          </div>

          <div class="nav-sec">
            <div v-if="!menuContraido" class="nav-lbl">Cierre y Envío</div>
            <button class="nav-btn-3d" :class="{ active: seccion === 'bibliografia' }" @click="seccion = 'bibliografia'"
              title="Bibliografía">
              <Library :size="16" /> <span v-if="!menuContraido">Bibliografía</span>
            </button>
            <button class="nav-btn-3d" :class="{ active: seccion === 'finalizar' }" @click="seccion = 'finalizar'"
              title="Finalizar y Enviar">
              <CheckCheck :size="16" /> <span v-if="!menuContraido">Finalizar y Enviar</span>
            </button>
          </div>
        </aside>

        <!-- =========================================
             PANEL DERECHO: ÁREA DEL DOCUMENTO
        ========================================== -->
        <main class="editor-main widget-contorno">

          <!-- ═══ A. CARÁTULA ═══ -->
          <div v-show="seccion === 'caratula'" class="doc-wrap fade-in">
            <DocHeader :subtitulo="secuencia.asignatura?.nombre || ''" />
            <div class="doc-section-title">
              <span>A.— Carátula</span>
              <div class="flex ic g2u">
                <span :class="['estado-badge', badgeEstadoDoc(secuencia.estado)]">{{ etiquetaEstado(secuencia.estado)
                  }}</span>
                <button v-if="esAutor" class="btn btn-outline btn-page-3d btn-sm" @click="modalGruposAbierto = true">
                  <Pencil :size="13" style="margin-right:4px" /> Editar grupos
                </button>
              </div>
            </div>
            <p class="hint-autoguardado">Los cambios se guardan automáticamente al salir del campo (efecto Soft Save).
            </p>

            <table class="doc-table" style="margin: 1rem 1.2rem; width: calc(100% - 2.4rem);">
              <tr>
                <td class="lbl">Programa educativo
                  <InfoTooltip :texto="INSTRUCCIONES.programaEducativo" />
                </td>
                <td class="val"><input class="eval-input input-3d-lit" v-model="caratula.programa_educativo"
                    :disabled="!editable" @blur="guardarCaratula('programa_educativo')" /></td>
                <td class="lbl">Docente(s)
                  <InfoTooltip :texto="INSTRUCCIONES.docentes" />
                </td>
                <td class="val font-bold">{{secuencia.autores.map(a => a.nombre_completo).join(', ') || '—'}}</td>
              </tr>
              <tr>
                <td class="lbl">Cuatrimestre</td>
                <td class="val font-bold">{{ secuencia.asignatura?.cuatrimestre?.numero ?? '—' }}</td>
                <td class="lbl">Periodo escolar</td>
                <td class="val font-bold">{{ secuencia.periodo }}</td>
              </tr>
              <tr>
                <td class="lbl">Nombre de la asignatura</td>
                <td class="val font-bold">{{ secuencia.asignatura?.nombre }}</td>
                <td class="lbl">Grupo(s)
                  <InfoTooltip :texto="INSTRUCCIONES.grupos" />
                </td>
                <td class="val font-bold">{{secuencia.grupos.map(g => g.grupo).join(', ') || '—'}}</td>
              </tr>
            </table>

            <table class="doc-table" style="margin: 0 1.2rem 1rem; width: calc(100% - 2.4rem);">
              <tr>
                <td class="lbl" style="width:28%">Propósito de la asignatura
                  <InfoTooltip :texto="INSTRUCCIONES.propositoAsignatura" />
                </td>
                <td class="val tall"><textarea class="eval-input input-3d-lit" rows="3"
                    v-model="caratula.proposito_aprendizaje" :disabled="!editable"
                    @blur="guardarCaratula('proposito_aprendizaje')"></textarea></td>
              </tr>
              <tr>
                <td class="lbl" style="width:28%">Competencia a la que contribuye
                  <InfoTooltip :texto="INSTRUCCIONES.competencia" />
                </td>
                <td class="val tall"><textarea class="eval-input input-3d-lit" rows="3" v-model="caratula.competencia"
                    :disabled="!editable" @blur="guardarCaratula('competencia')"></textarea></td>
              </tr>
            </table>

            <table class="doc-table" style="margin: 0 1.2rem 1rem; width: calc(100% - 2.4rem);">
              <tr>
                <td class="lbl">Tipo de competencia
                  <InfoTooltip :texto="INSTRUCCIONES.tipoCompetencia" />
                </td>
                <td class="val"><input class="eval-input input-3d-lit" v-model="caratula.tipo_competencia"
                    :disabled="!editable" @blur="guardarCaratula('tipo_competencia')" /></td>
                <td class="lbl">Créditos
                  <InfoTooltip :texto="INSTRUCCIONES.creditos" />
                </td>
                <td class="val"><input class="eval-input input-3d-lit text-center" type="number"
                    v-model.number="caratula.creditos" :disabled="!editable" @blur="guardarCaratula('creditos')" /></td>
                <td class="lbl">Modalidad
                  <InfoTooltip :texto="INSTRUCCIONES.modalidad" />
                </td>
                <td class="val"><input class="eval-input input-3d-lit" v-model="caratula.modalidad"
                    :disabled="!editable" @blur="guardarCaratula('modalidad')" /></td>
              </tr>
            </table>

            <table class="doc-table" style="margin: 0 1.2rem 1rem; width: calc(100% - 2.4rem); text-align:center">
              <tr>
                <td class="lbl lbl-hrs">Horas del saber</td>
                <td class="lbl lbl-hrs">Horas del saber hacer</td>
                <td class="lbl lbl-hrs">Horas totales</td>
                <td class="lbl lbl-hrs">Horas por semana
                  <InfoTooltip :texto="INSTRUCCIONES.horas" />
                </td>
              </tr>
              <tr>
                <td class="val val-hrs"><input class="eval-input input-3d-lit text-center font-bold" type="number"
                    v-model.number="caratula.horas_saber" :disabled="!editable"
                    @blur="guardarCaratula('horas_saber')" /></td>
                <td class="val val-hrs"><input class="eval-input input-3d-lit text-center font-bold" type="number"
                    v-model.number="caratula.horas_saber_hacer" :disabled="!editable"
                    @blur="guardarCaratula('horas_saber_hacer')" /></td>
                <td class="val val-hrs"><input class="eval-input input-3d-lit text-center font-bold" type="number"
                    v-model.number="caratula.horas_totales" :disabled="!editable"
                    @blur="guardarCaratula('horas_totales')" /></td>
                <td class="val val-hrs"><input class="eval-input input-3d-lit text-center font-bold" type="number"
                    v-model.number="caratula.horas_semana" :disabled="!editable"
                    @blur="guardarCaratula('horas_semana')" /></td>
              </tr>
            </table>
            <DocFooter :pagina="paginaDe('caratula')" :total-paginas="totalPaginasDoc" />
          </div>

          <!-- ═══ POR UNIDAD ═══ -->
          <template v-for="(unidad, i) in secuencia.unidades" :key="unidad.id">

            <!-- B. Info -->
            <div v-show="seccion === `unidad-${unidad.id}`" class="doc-wrap fade-in">
              <DocHeader :subtitulo="`B.— Información de la Unidad ${i + 1}`" />
              <div class="doc-section-title"><span>B.— Información de la Unidad de Aprendizaje {{ i + 1 }}</span></div>

              <ValidacionElemento v-if="unidad.revision || puedeValidarElementos" variante="barra" tipo="unidad"
                :elemento-id="unidad.id" :revision="unidad.revision" :puede-validar="puedeValidarElementos"
                @actualizado="(r) => unidad.revision = r" />

              <table class="doc-table" style="margin: 1rem 1.2rem 0; width: calc(100% - 2.4rem);">
                <tr>
                  <td class="lbl" style="width:28%">Nombre de la unidad
                    <InfoTooltip :texto="INSTRUCCIONES.nombreUnidad" />
                  </td>
                  <td class="val"><input class="eval-input input-3d-lit" v-model="unidad.nombre"
                      :disabled="!puedeEditarUnidad(unidad)" @blur="guardarUnidad(unidad, 'nombre')" /></td>
                </tr>
                <tr>
                  <td class="lbl">Propósito esperado
                    <InfoTooltip :texto="INSTRUCCIONES.propositoEsperado" />
                  </td>
                  <td class="val tall"><textarea class="eval-input input-3d-lit" rows="3"
                      v-model="unidad.proposito_esperado" :disabled="!puedeEditarUnidad(unidad)"
                      @blur="guardarUnidad(unidad, 'proposito_esperado')"></textarea></td>
                </tr>
              </table>

              <table class="doc-table" style="margin: 0 1.2rem 1rem; width: calc(100% - 2.4rem); text-align:center">
                <tr>
                  <td class="lbl">Horas saber</td>
                  <td class="lbl">Horas saber hacer</td>
                  <td class="lbl">Horas totales</td>
                  <td class="lbl">% de la unidad
                    <InfoTooltip :texto="INSTRUCCIONES.porcentajeUnidad" />
                  </td>
                </tr>
                <tr>
                  <td class="val"><input class="eval-input input-3d-lit text-center font-bold" type="number"
                      v-model.number="unidad.horas_saber" :disabled="!puedeEditarUnidad(unidad)"
                      @blur="guardarUnidad(unidad, 'horas_saber')" /></td>
                  <td class="val"><input class="eval-input input-3d-lit text-center font-bold" type="number"
                      v-model.number="unidad.horas_saber_hacer" :disabled="!puedeEditarUnidad(unidad)"
                      @blur="guardarUnidad(unidad, 'horas_saber_hacer')" /></td>
                  <td class="val"><input class="eval-input input-3d-lit text-center font-bold" type="number"
                      v-model.number="unidad.horas_totales" :disabled="!puedeEditarUnidad(unidad)"
                      @blur="guardarUnidad(unidad, 'horas_totales')" /></td>
                  <td class="val"><input class="eval-input input-3d-lit text-center font-bold" type="number"
                      v-model.number="unidad.porcentaje_unidad" :disabled="!puedeEditarUnidad(unidad)"
                      @blur="guardarUnidad(unidad, 'porcentaje_unidad')" /></td>
                </tr>
              </table>

              <div class="doc-section-title"
                style="font-size:11.5px; border-top: 1px solid var(--border-soft); padding-top:16px;">
                <span>Temas de la Unidad
                  <InfoTooltip :texto="INSTRUCCIONES.tema" />
                </span>
                <button v-if="puedeEditarUnidad(unidad)" class="btn btn-add-3d btn-sm"
                  :disabled="!!agregandoTema[unidad.id]" @click="agregarTema(unidad)">
                  <Loader2 v-if="agregandoTema[unidad.id]" :size="13" class="spin" style="margin-right:4px" />
                  <Plus v-else :size="13" style="margin-right:4px" />
                  {{ agregandoTema[unidad.id] ? 'Agregando…' : 'Añadir tema' }}
                </button>
              </div>

              <div style="overflow-x:auto; margin: 0 1.2rem 1.5rem">
                <table class="doc-table table-contorno" style="width:100%; margin-bottom:0">
                  <thead>
                    <tr>
                      <td class="lbl text-center">Temas</td>
                      <td class="lbl text-center">Saber
                        <InfoTooltip :texto="INSTRUCCIONES.saberConceptual" />
                      </td>
                      <td class="lbl text-center">Saber Hacer
                        <InfoTooltip :texto="INSTRUCCIONES.saberHacer" />
                      </td>
                      <td class="lbl text-center">Saber Ser-convivir
                        <InfoTooltip :texto="INSTRUCCIONES.saberSerConvivir" />
                      </td>
                      <td class="lbl text-center" style="width:110px;">Estado</td>
                      <td class="lbl" style="width:36px"></td>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="unidad.temas.length === 0">
                      <td colspan="6" class="val text-center text-dim" style="font-style:italic; padding: 24px;">Sin
                        temas registrados.</td>
                    </tr>
                    <tr v-for="t in unidad.temas" :key="t.id">
                      <td class="val"><textarea class="tema-cell input-3d-lit" v-model="t.tema"
                          :disabled="!puedeEditarUnidad(unidad)" @blur="guardarTema(t, 'tema')"></textarea></td>
                      <td class="val"><textarea class="tema-cell input-3d-lit" v-model="t.saber"
                          :disabled="!puedeEditarUnidad(unidad)" @blur="guardarTema(t, 'saber')"></textarea></td>
                      <td class="val"><textarea class="tema-cell input-3d-lit" v-model="t.saber_hacer"
                          :disabled="!puedeEditarUnidad(unidad)" @blur="guardarTema(t, 'saber_hacer')"></textarea></td>
                      <td class="val"><textarea class="tema-cell input-3d-lit" v-model="t.ser_convivir"
                          :disabled="!puedeEditarUnidad(unidad)" @blur="guardarTema(t, 'ser_convivir')"></textarea></td>
                      <ValidacionElemento variante="fila" tipo="tema" :elemento-id="t.id" :revision="t.revision"
                        :puede-validar="puedeValidarElementos" @actualizado="(r) => t.revision = r" />
                      <td class="text-center align-middle bg-soft">
                        <IconButton v-if="puedeEditarUnidad(unidad)" title="Eliminar" variant="danger"
                          class="icon-btn-3d" @click="eliminarTema(unidad, t)">
                          <Trash2 :size="14" />
                        </IconButton>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <DocFooter :pagina="paginaDe('info', i)" :total-paginas="totalPaginasDoc" />
            </div>

            <!-- C. Evaluación -->
            <div v-show="seccion === `unidad-${unidad.id}-evaluacion`" class="doc-wrap fade-in">
              <DocHeader :subtitulo="`C.— Sistema de Evaluación — Unidad ${i + 1}`" />
              <div class="doc-section-title">
                <span>C.— Sistema de Evaluación</span>
                <span :class="['pond-badge', sumaPonderacion(unidad) === 100 ? 'pond-ok' : 'pond-warn']">Σ {{
                  sumaPonderacion(unidad) }}%</span>
              </div>

              <ValidacionElemento v-if="unidad.evaluacion.revision || puedeValidarElementos" variante="barra"
                tipo="evaluacion" :elemento-id="unidad.evaluacion.id" :revision="unidad.evaluacion.revision"
                :puede-validar="puedeValidarElementos" @actualizado="(r) => unidad.evaluacion.revision = r" />

              <table class="doc-table" style="margin: 1rem 1.2rem 0; width: calc(100% - 2.4rem);">
                <tr>
                  <td class="lbl" style="width:28%">Periodo en semanas
                    <InfoTooltip :texto="INSTRUCCIONES.periodoSemanas" />
                  </td>
                  <td class="val"><input class="eval-input input-3d-lit font-bold text-center" style="width:80px"
                      type="number" min="1" max="15" v-model.number="unidad.evaluacion.periodo_semanas"
                      :disabled="!puedeEditarUnidad(unidad)" @blur="guardarEvaluacion(unidad)" /></td>
                </tr>
                <tr>
                  <td class="lbl">Resultado de aprendizaje
                    <InfoTooltip :texto="INSTRUCCIONES.resultadoAprendizaje" />
                  </td>
                  <td class="val tall"><textarea class="eval-input input-3d-lit" rows="3"
                      v-model="unidad.evaluacion.resultado_aprendizaje" :disabled="!puedeEditarUnidad(unidad)"
                      @blur="guardarEvaluacion(unidad)"></textarea></td>
                </tr>
              </table>

              <div class="doc-section-title"
                style="font-size:11.5px; border-top: 1px solid var(--border-soft); padding-top:16px;">
                <span>Evidencias de aprendizaje</span>
                <button v-if="puedeEditarUnidad(unidad)" class="btn btn-add-3d btn-sm"
                  :disabled="!!agregandoEvidencia[unidad.id]" @click="agregarEvidencia(unidad)">
                  <Loader2 v-if="agregandoEvidencia[unidad.id]" :size="13" class="spin" style="margin-right:4px" />
                  <Plus v-else :size="13" style="margin-right:4px" />
                  {{ agregandoEvidencia[unidad.id] ? 'Agregando…' : 'Añadir evidencia' }}
                </button>
              </div>

              <div style="overflow-x:auto; margin: 0 1.2rem 1.5rem">
                <table class="doc-table table-contorno" style="width:100%; margin-bottom:0">
                  <thead>
                    <tr>
                      <td class="lbl text-center">Evidencia
                        <InfoTooltip :texto="INSTRUCCIONES.evidenciaAprendizaje" />
                      </td>
                      <td class="lbl text-center">Tipo evaluación
                        <InfoTooltip :texto="INSTRUCCIONES.tipoEvaluacion" />
                      </td>
                      <td class="lbl text-center" style="width:100px;">Ponderación %
                        <InfoTooltip :texto="INSTRUCCIONES.ponderacion" />
                      </td>
                      <td class="lbl text-center">Instrumento
                        <InfoTooltip :texto="INSTRUCCIONES.instrumentoEvaluacion" />
                      </td>
                      <td class="lbl text-center" style="width:110px;">Estado</td>
                      <td class="lbl" style="width:36px"></td>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="unidad.evidencias.length === 0">
                      <td colspan="6" class="val text-center text-dim" style="font-style:italic; padding: 24px;">Sin
                        evidencias registradas.</td>
                    </tr>
                    <tr v-for="ev in unidad.evidencias" :key="ev.id">
                      <td class="val"><textarea class="ev-cell input-3d-lit" v-model="ev.evidencia_aprendizaje"
                          :disabled="!puedeEditarUnidad(unidad)"
                          @blur="guardarEvidencia(ev, 'evidencia_aprendizaje')"></textarea></td>
                      <td class="val">
                        <select class="eval-select input-3d-lit" v-model="ev.tipo_evaluacion"
                          :disabled="!puedeEditarUnidad(unidad)" @change="guardarEvidencia(ev, 'tipo_evaluacion')">
                          <option :value="null">— Seleccionar —</option>
                          <option v-for="t in TIPOS_EVALUACION" :key="t" :value="t">{{ t }}</option>
                        </select>
                      </td>
                      <td class="val"><input class="eval-input input-3d-lit text-center font-bold" type="number" min="0"
                          max="100" v-model.number="ev.ponderacion" :disabled="!puedeEditarUnidad(unidad)"
                          @blur="guardarEvidencia(ev, 'ponderacion')" /></td>
                      <td class="val">
                        <select class="eval-select input-3d-lit" v-model="ev.instrumento_evaluacion"
                          :disabled="!puedeEditarUnidad(unidad)"
                          @change="guardarEvidencia(ev, 'instrumento_evaluacion')">
                          <option :value="null">— Seleccionar —</option>
                          <option v-for="ins in INSTRUMENTOS" :key="ins" :value="ins">{{ ins }}</option>
                        </select>
                      </td>
                      <ValidacionElemento variante="fila" tipo="evidencia" :elemento-id="ev.id" :revision="ev.revision"
                        :puede-validar="puedeValidarElementos" @actualizado="(r) => ev.revision = r" />
                      <td class="text-center align-middle bg-soft">
                        <IconButton v-if="puedeEditarUnidad(unidad)" title="Eliminar" variant="danger"
                          class="icon-btn-3d" @click="eliminarEvidencia(unidad, ev)">
                          <Trash2 :size="14" />
                        </IconButton>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div style="padding:.5rem 1.2rem 1rem;font-size:11px;color:var(--warning);font-weight:600;">
                <Info :size="12" style="display:inline; margin-right:4px" /> Cada unidad debe tener al menos dos tipos
                distintos de evaluación y la suma de ponderaciones debe ser exactamente 100%.
              </div>
              <DocFooter :pagina="paginaDe('evaluacion', i)" :total-paginas="totalPaginasDoc" />
            </div>

            <!-- D. Secuencia (fases) -->
            <div v-show="seccion === `unidad-${unidad.id}-secuencia`" class="doc-wrap fade-in">
              <DocHeader :subtitulo="`D.— Secuencia Didáctica — Unidad ${i + 1}`" />
              <div class="doc-section-title"><span>D.— Secuencia Didáctica por Unidad</span></div>

              <div v-for="tipoFase in ['apertura', 'desarrollo', 'cierre']" :key="tipoFase"
                style="margin-bottom: 24px;">
                <div class="fase-header">
                  <span class="fase-header-title">{{ tipoFase }}
                    <InfoTooltip :texto="INSTRUCCIONES['fase' + capitalizar(tipoFase)]" />
                  </span>
                  <button v-if="puedeEditarUnidad(unidad)" class="btn btn-add-3d btn-sm"
                    :disabled="!!agregandoActividad[claveFase(unidad.id, tipoFase)]"
                    @click="agregarActividad(unidad, tipoFase)">
                    <Loader2 v-if="agregandoActividad[claveFase(unidad.id, tipoFase)]" :size="13" class="spin"
                      style="margin-right:4px" />
                    <Plus v-else :size="13" style="margin-right:4px" />
                    {{ agregandoActividad[claveFase(unidad.id, tipoFase)] ? 'Agregando…' : 'Añadir estrategia' }}
                  </button>
                </div>

                <div style="overflow-x:auto; margin: 0 1.2rem 1.5rem">
                  <table class="doc-table table-contorno" style="width:100%; margin-bottom:0">
                    <thead>
                      <tr>
                        <td class="lbl text-center" style="font-size:10.5px">Métodos y técnicas
                          <InfoTooltip :texto="INSTRUCCIONES.metodosTecnicas" />
                        </td>
                        <td class="lbl text-center" style="font-size:10.5px">Actividades docente
                          <InfoTooltip :texto="INSTRUCCIONES.actividadesDocente" />
                        </td>
                        <td class="lbl text-center" style="font-size:10.5px">Actividades estudiante
                          <InfoTooltip :texto="INSTRUCCIONES.actividadesEstudiante" />
                        </td>
                        <td class="lbl text-center" style="font-size:10.5px">Evidencia
                          <InfoTooltip :texto="INSTRUCCIONES.evidenciaFase" />
                        </td>
                        <td class="lbl text-center" style="font-size:10.5px">Medios y materiales
                          <InfoTooltip :texto="INSTRUCCIONES.mediosMateriales" />
                        </td>
                        <td class="lbl text-center" style="width:110px;font-size:10.5px">Estado</td>
                        <td class="lbl" style="width:36px"></td>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="actividadesDeFase(unidad, tipoFase).length === 0">
                        <td colspan="7" class="val text-center text-dim" style="font-style:italic; padding: 24px;">Sin
                          estrategias registradas.</td>
                      </tr>
                      <tr v-for="act in actividadesDeFase(unidad, tipoFase)" :key="act.id">
                        <td class="val" style="padding:5px">
                          <select class="eval-select input-3d-lit" v-model="act.metodos_tecnicas"
                            :disabled="!puedeEditarUnidad(unidad)" @change="guardarActividad(act, 'metodos_tecnicas')">
                            <option :value="null">— Seleccionar —</option>
                            <option v-for="(estrategia, idx) in ESTRATEGIAS_POR_FASE[tipoFase]" :key="estrategia"
                              :value="estrategia">{{ idx + 1 }}) {{ estrategia }}</option>
                          </select>
                        </td>
                        <td class="val"><textarea class="fase-cell input-3d-lit" v-model="act.actividades_docente"
                            :disabled="!puedeEditarUnidad(unidad)"
                            @blur="guardarActividad(act, 'actividades_docente')"></textarea></td>
                        <td class="val"><textarea class="fase-cell input-3d-lit" v-model="act.actividades_estudiante"
                            :disabled="!puedeEditarUnidad(unidad)"
                            @blur="guardarActividad(act, 'actividades_estudiante')"></textarea></td>
                        <td class="val"><textarea class="fase-cell input-3d-lit" v-model="act.evidencia_aprendizaje"
                            :disabled="!puedeEditarUnidad(unidad)"
                            @blur="guardarActividad(act, 'evidencia_aprendizaje')"></textarea></td>
                        <td class="val"><textarea class="fase-cell input-3d-lit" v-model="act.medios_materiales"
                            :disabled="!puedeEditarUnidad(unidad)"
                            @blur="guardarActividad(act, 'medios_materiales')"></textarea></td>
                        <ValidacionElemento variante="fila" tipo="fase" :elemento-id="act.id" :revision="act.revision"
                          :puede-validar="puedeValidarElementos" @actualizado="(r) => act.revision = r" />
                        <td class="text-center align-middle bg-soft">
                          <IconButton v-if="puedeEditarUnidad(unidad)" title="Eliminar" variant="danger"
                            class="icon-btn-3d" @click="eliminarActividad(unidad, tipoFase, act)">
                            <Trash2 :size="14" />
                          </IconButton>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <DocFooter :pagina="paginaDe('secuencia', i)" :total-paginas="totalPaginasDoc" />
            </div>
          </template>

          <!-- ═══ BIBLIOGRAFÍA ═══ -->
          <div v-show="seccion === 'bibliografia'" class="doc-wrap fade-in">
            <DocHeader subtitulo="Referencias Bibliográficas y Digitales" />
            <div class="doc-section-title">
              <span>Referencias
                <InfoTooltip :texto="INSTRUCCIONES.referencias" />
              </span>
              <button v-if="editable" class="btn btn-add-3d btn-sm" :disabled="agregandoReferencia"
                @click="agregarReferencia">
                <Loader2 v-if="agregandoReferencia" :size="13" class="spin" style="margin-right:4px" />
                <Plus v-else :size="13" style="margin-right:4px" />
                {{ agregandoReferencia ? 'Agregando…' : 'Añadir referencia' }}
              </button>
            </div>

            <div style="overflow-x:auto; margin: 1rem 1.2rem 1.5rem">
              <table class="doc-table table-contorno" style="width:100%; margin-bottom:0">
                <thead>
                  <tr>
                    <td class="lbl text-center" style="width:40px;">#</td>
                    <td class="lbl text-center">Autor</td>
                    <td class="lbl text-center">Título</td>
                    <td class="lbl text-center">Referencia / vínculo</td>
                    <td class="lbl text-center" style="width:110px;">Estado</td>
                    <td class="lbl" style="width:36px"></td>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="secuencia.referencias.length === 0">
                    <td colspan="6" class="val text-center text-dim" style="font-style:italic; padding: 24px;">Sin
                      referencias
                      registradas.</td>
                  </tr>
                  <tr v-for="(r, idx) in secuencia.referencias" :key="r.id">
                    <td class="val text-center font-bold">{{ idx + 1 }}</td>
                    <td class="val"><input class="eval-input input-3d-lit" v-model="r.autor" :disabled="!editable"
                        @blur="guardarReferencia(r, 'autor')" /></td>
                    <td class="val"><input class="eval-input input-3d-lit" v-model="r.titulo" :disabled="!editable"
                        @blur="guardarReferencia(r, 'titulo')" /></td>
                    <td class="val"><textarea class="ev-cell input-3d-lit" v-model="r.referencia" :disabled="!editable"
                        @blur="guardarReferencia(r, 'referencia')"></textarea></td>
                    <ValidacionElemento variante="fila" tipo="referencia" :elemento-id="r.id" :revision="r.revision"
                      :puede-validar="puedeValidarElementos" @actualizado="(rv) => r.revision = rv" />
                    <td class="text-center align-middle bg-soft">
                      <IconButton v-if="editable" title="Eliminar" variant="danger" class="icon-btn-3d"
                        @click="eliminarReferencia(r)">
                        <Trash2 :size="14" />
                      </IconButton>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <DocFooter :pagina="paginaDe('bibliografia')" :total-paginas="totalPaginasDoc" />
          </div>

          <!-- ═══ FINALIZAR ═══ -->
          <div v-show="seccion === 'finalizar'" class="doc-wrap fade-in">
            <DocHeader subtitulo="Estado y Validación de la Secuencia" />

            <div class="doc-section-title">
              <span>Estado de la Secuencia</span>
              <span :class="['estado-badge', badgeEstadoDoc(secuencia.estado)]">{{ etiquetaEstado(secuencia.estado)
                }}</span>
            </div>

            <div style="padding: 2rem 1.2rem">
              <div v-if="mensajeAccion"
                :class="['alert', mensajeAccion.tipo === 'error' ? 'a-danger' : 'a-success', 'mb4']">
                {{ mensajeAccion.texto }}
              </div>

              <!-- Vista Autor: Borrador -->
              <template v-if="secuencia.estado === 'borrador' && esAutor">
                <h3 class="ht-sm mb2">Lista de verificación pre-envío ({{ itemsOk }}/{{ completitud.length }})</h3>

                <div class="progreso-barra-3d mb4">
                  <div class="progreso-relleno-3d" :style="{ width: porcentajeOk + '%' }"></div>
                </div>

                <div class="checklist-3d mb4">
                  <div v-for="(item, idx) in completitud" :key="idx" class="check-item-3d"
                    :class="item.ok ? 'ok' : 'falta'" @click="!item.ok && (seccion = item.seccion)">
                    <div class="check-icon-wrap">
                      <CheckCircle2 v-if="item.ok" :size="16" color="#10B981" />
                      <XCircle v-else :size="16" color="#EF4444" />
                    </div>
                    <span>{{ item.label }}</span>
                  </div>
                </div>

                <div class="flex g2u mt4">
                  <button class="btn btn-primary btn-add-3d flex-1" :disabled="itemsOk < completitud.length || enviando"
                    @click="enviarRevision">
                    <Send :size="16" style="margin-right:6px" /> Enviar a revisión técnica
                  </button>
                  <button class="btn btn-danger btn-danger-3d" :disabled="eliminando" @click="eliminarSecuencia">
                    <Trash2 :size="16" style="margin-right:6px" /> Eliminar secuencia
                  </button>
                </div>
              </template>

              <!-- Vista Autor/Revisor: En Revisión -->
              <template v-else-if="secuencia.estado === 'en_revision'">
                <div class="alert a-info mb4 alert-bounce">
                  <strong>En revisión:</strong> Esta secuencia ha sido enviada y se encuentra bloqueada para edición
                  mientras el revisor la evalúa.
                </div>
                <div class="flex g2u fw">
                  <button v-if="esAutor" class="btn btn-outline btn-page-3d" @click="cancelarEnvio">
                    <Undo2 :size="16" style="margin-right:6px" /> Cancelar envío y editar
                  </button>
                  <button v-if="puedeValidarElementos" class="btn btn-add-3d flex-1" @click="enviarValidacion">
                    <ShieldCheck :size="16" style="margin-right:6px" /> Aprobar y enviar al Director
                  </button>
                  <button v-if="puedeValidarElementos" class="btn btn-danger-3d flex-1" @click="rechazarComoRevisor">
                    <XCircle :size="16" style="margin-right:6px" /> Rechazar y devolver a borrador
                  </button>
                </div>
              </template>

              <!-- Vista En Validación -->
              <template v-else-if="secuencia.estado === 'en_proceso_validacion'">
                <div class="alert a-warning alert-bounce">
                  <strong>En proceso de validación:</strong> Esta secuencia ya pasó la revisión técnica y está en la
                  bandeja del Director Académico para su firma final.
                </div>
              </template>

              <!-- Vista Validada -->
              <template v-else-if="secuencia.estado === 'validada'">
                <div class="alert a-success alert-bounce">
                  <strong>¡Aprobada!</strong> Esta secuencia fue validada exitosamente y está lista para su
                  implementación en clase.
                </div>
                <a v-if="secuencia.documento_validacion_url" class="btn btn-outline btn-page-3d mt4"
                  :href="secuencia.documento_validacion_url" target="_blank" rel="noopener">
                  <FileText :size="16" style="margin-right:6px" /> Ver documento oficial firmado
                </a>
              </template>

              <!-- Vista Rechazada -->
              <template v-else-if="secuencia.estado === 'rechazada'">
                <div class="alert a-danger alert-bounce">
                  <strong>Rechazada:</strong> Esta secuencia fue declinada por el director académico. Consulta las notas
                  de revisión para realizar las correcciones pertinentes antes de reenviar.
                </div>
              </template>

            </div>
            <DocFooter :pagina="paginaDe('finalizar')" :total-paginas="totalPaginasDoc" />
          </div>

        </main>
      </div>
    </div>

    <EditarGruposAutoresModal v-if="modalGruposAbierto" :secuencia="secuencia" @close="modalGruposAbierto = false"
      @actualizado="onGruposActualizados" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  ArrowLeft, FileText, ChevronRight, Info, ClipboardList, Layers, Library, CheckCheck,
  Pencil, Plus, Trash2, CheckCircle2, XCircle, Send, Undo2, ShieldCheck, Loader2, Menu
} from 'lucide-vue-next'
// SE ELIMINÓ LA IMPORTACIÓN DEL APPSHELL PORQUE AHORA USAMOS MODO ENFOQUE
import '@/assets/secuencia-documento.css'
import { INSTRUCCIONES } from '@/config/instrucciones'
import { ESTRATEGIAS_POR_FASE } from '@/config/estrategias'
import InfoTooltip from '@/components/InfoTooltip.vue'
import DocHeader from '@/components/DocHeader.vue'
import DocFooter from '@/components/DocFooter.vue'
import ValidacionElemento from '@/components/ValidacionElemento.vue'
import IconButton from '@/components/IconButton.vue'
import EditarGruposAutoresModal from './EditarGruposAutoresModal.vue'
import api from '@/services/api'
import router from '@/router'

const route = useRoute()
const secuenciaId = route.params.id

const cargando = ref(true)
const secuencia = ref(null)
const esAutor = ref(false)
const puedeValidarElementos = ref(false)
const editable = ref(false)
const completitud = ref([])
const seccion = ref('caratula')
const gruposAbiertos = reactive({})
const modalGruposAbierto = ref(false)
const enviando = ref(false)
const eliminando = ref(false)
const mensajeAccion = ref(null)

// NUEVO: Estado para contraer el menú lateral
const menuContraido = ref(false)

const agregandoTema = reactive({})
const agregandoEvidencia = reactive({})
const agregandoActividad = reactive({})
const agregandoReferencia = ref(false)
function claveFase(unidadId, fase) { return `${unidadId}-${fase}` }

const estadoGuardado = ref(null)
let timeoutOcultarGuardado = null
function marcarGuardando() {
  clearTimeout(timeoutOcultarGuardado)
  estadoGuardado.value = 'guardando'
}
function marcarGuardado() {
  estadoGuardado.value = 'guardado'
  clearTimeout(timeoutOcultarGuardado)
  timeoutOcultarGuardado = setTimeout(() => { estadoGuardado.value = null }, 1800)
}
function marcarErrorGuardado() {
  estadoGuardado.value = 'error'
  clearTimeout(timeoutOcultarGuardado)
  timeoutOcultarGuardado = setTimeout(() => { estadoGuardado.value = null }, 2500)
}

const caratula = computed(() => secuencia.value.caratula)

const totalPaginasDoc = computed(() => 1 + (secuencia.value.unidades.length * 3) + 2)
function paginaDe(tipo, indiceUnidad = 0) {
  if (tipo === 'caratula') return 1
  if (tipo === 'info') return 2 + indiceUnidad * 3
  if (tipo === 'evaluacion') return 3 + indiceUnidad * 3
  if (tipo === 'secuencia') return 4 + indiceUnidad * 3
  if (tipo === 'bibliografia') return 2 + secuencia.value.unidades.length * 3
  if (tipo === 'finalizar') return 3 + secuencia.value.unidades.length * 3
  return 1
}

const TIPOS_EVALUACION = [
  'Autoevaluación', 'Coevaluación', 'Heteroevaluación',
  'Autoevaluación + Coevaluación', 'Autoevaluación + Heteroevaluación',
  'Coevaluación + Heteroevaluación', 'Autoevaluación + Coevaluación + Heteroevaluación',
]
const INSTRUMENTOS = [
  'Cuestionario de preguntas abiertas', 'Prueba objetiva', 'Prueba por competencias',
  'Lista de cotejo', 'Guía de observación', 'Escala estimativa', 'Rúbrica',
]

const itemsOk = computed(() => completitud.value.filter((i) => i.ok).length)
const porcentajeOk = computed(() => completitud.value.length ? Math.round((itemsOk.value / completitud.value.length) * 100) : 0)

onMounted(cargar)

async function refrescarCompletitud() {
  if (!esAutor.value) return
  try {
    const { data } = await api.get(`/secuencias/${secuencia.value.id}/completitud`)
    completitud.value = data
  } catch (e) {
    console.error(e)
  }
}

watch(seccion, (nueva) => {
  if (nueva === 'finalizar') refrescarCompletitud()
})

async function cargar() {
  cargando.value = true
  try {
    const { data } = await api.get(`/secuencias/${secuenciaId}`)
    secuencia.value = data.secuencia
    esAutor.value = data.es_autor
    puedeValidarElementos.value = data.puede_validar_elementos
    editable.value = data.editable
    completitud.value = data.completitud
    secuencia.value.unidades.forEach((u) => { gruposAbiertos[u.id] = true })
  } catch (e) {
    alert(e.response?.data?.message || 'No se pudo cargar la secuencia.')
    router.back()
  } finally {
    cargando.value = false
  }
}

function toggleGrupo(id) { gruposAbiertos[id] = !gruposAbiertos[id] }
function puedeEditarUnidad(unidad) { return editable.value }
function actividadesDeFase(unidad, tipoFase) { return unidad.fases.find((f) => f.fase === tipoFase)?.actividades ?? [] }
function sumaPonderacion(unidad) {
  return Math.round((unidad.evidencias?.reduce((s, e) => s + (Number(e.ponderacion) || 0), 0) ?? 0) * 100) / 100
}
function capitalizar(s) { return s.charAt(0).toUpperCase() + s.slice(1) }

async function guardarCaratula(campo) {
  marcarGuardando()
  try {
    await api.patch(`/docente/secuencias/${secuencia.value.id}/caratula`, { [campo]: caratula.value[campo] })
    marcarGuardado()
    if (campo === 'proposito_aprendizaje' || campo === 'competencia') refrescarCompletitud()
  } catch (e) { console.error(e); marcarErrorGuardado() }
}

async function guardarUnidad(unidad, campo) {
  marcarGuardando()
  try {
    await api.patch(`/docente/unidades/${unidad.id}`, { [campo]: unidad[campo] })
    marcarGuardado()
  } catch (e) { console.error(e); marcarErrorGuardado() }
}

async function agregarTema(unidad) {
  if (agregandoTema[unidad.id]) return
  agregandoTema[unidad.id] = true
  try {
    const { data } = await api.post(`/docente/unidades/${unidad.id}/temas`, {})
    unidad.temas.push(data)
    refrescarCompletitud()
  } finally {
    agregandoTema[unidad.id] = false
  }
}
async function guardarTema(tema, campo) {
  marcarGuardando()
  try {
    await api.patch(`/docente/temas/${tema.id}`, { [campo]: tema[campo] })
    marcarGuardado()
  } catch (e) { console.error(e); marcarErrorGuardado() }
}
async function eliminarTema(unidad, tema) {
  if (!confirm('¿Eliminar este tema?')) return
  await api.delete(`/docente/temas/${tema.id}`)
  unidad.temas = unidad.temas.filter((t) => t.id !== tema.id)
  refrescarCompletitud()
}

async function guardarEvaluacion(unidad) {
  marcarGuardando()
  try {
    await api.patch(`/docente/unidades/${unidad.id}/evaluacion`, {
      periodo_semanas: unidad.evaluacion.periodo_semanas,
      resultado_aprendizaje: unidad.evaluacion.resultado_aprendizaje,
    })
    marcarGuardado()
    refrescarCompletitud()
  } catch (e) { console.error(e); marcarErrorGuardado() }
}

async function agregarEvidencia(unidad) {
  if (agregandoEvidencia[unidad.id]) return
  agregandoEvidencia[unidad.id] = true
  try {
    const { data } = await api.post(`/docente/unidades/${unidad.id}/evidencias`, {})
    unidad.evidencias.push(data)
    refrescarCompletitud()
  } finally {
    agregandoEvidencia[unidad.id] = false
  }
}
async function guardarEvidencia(evidencia, campo) {
  marcarGuardando()
  try {
    await api.patch(`/docente/evidencias/${evidencia.id}`, { [campo]: evidencia[campo] })
    marcarGuardado()
    if (campo === 'ponderacion' || campo === 'tipo_evaluacion') refrescarCompletitud()
  } catch (e) { console.error(e); marcarErrorGuardado() }
}
async function eliminarEvidencia(unidad, evidencia) {
  if (!confirm('¿Eliminar esta evidencia?')) return
  await api.delete(`/docente/evidencias/${evidencia.id}`)
  unidad.evidencias = unidad.evidencias.filter((e) => e.id !== evidencia.id)
  refrescarCompletitud()
}

async function agregarActividad(unidad, tipoFase) {
  const clave = claveFase(unidad.id, tipoFase)
  if (agregandoActividad[clave]) return
  agregandoActividad[clave] = true
  try {
    const { data } = await api.post(`/docente/unidades/${unidad.id}/fases/${tipoFase}/actividades`, {})
    let fase = unidad.fases.find((f) => f.fase === tipoFase)
    if (!fase) { fase = { fase: tipoFase, actividades: [] }; unidad.fases.push(fase) }
    fase.actividades.push(data)
    refrescarCompletitud()
  } finally {
    agregandoActividad[clave] = false
  }
}
async function guardarActividad(actividad, campo) {
  marcarGuardando()
  try {
    await api.patch(`/docente/fase-actividades/${actividad.id}`, { [campo]: actividad[campo] })
    marcarGuardado()
  } catch (e) { console.error(e); marcarErrorGuardado() }
}
async function eliminarActividad(unidad, tipoFase, actividad) {
  if (!confirm('¿Eliminar esta estrategia?')) return
  await api.delete(`/docente/fase-actividades/${actividad.id}`)
  const fase = unidad.fases.find((f) => f.fase === tipoFase)
  fase.actividades = fase.actividades.filter((a) => a.id !== actividad.id)
  refrescarCompletitud()
}

async function agregarReferencia() {
  if (agregandoReferencia.value) return
  agregandoReferencia.value = true
  try {
    const { data } = await api.post(`/docente/secuencias/${secuencia.value.id}/referencias`, {})
    secuencia.value.referencias.push(data)
    refrescarCompletitud()
  } finally {
    agregandoReferencia.value = false
  }
}
async function guardarReferencia(referencia, campo) {
  marcarGuardando()
  try {
    await api.patch(`/docente/referencias/${referencia.id}`, { [campo]: referencia[campo] })
    marcarGuardado()
  } catch (e) { console.error(e); marcarErrorGuardado() }
}
async function eliminarReferencia(referencia) {
  if (!confirm('¿Eliminar esta referencia?')) return
  await api.delete(`/docente/referencias/${referencia.id}`)
  secuencia.value.referencias = secuencia.value.referencias.filter((r) => r.id !== referencia.id)
  refrescarCompletitud()
}

function onGruposActualizados(data) {
  secuencia.value.autores = data.autores
  secuencia.value.grupos = data.grupos
  modalGruposAbierto.value = false
  refrescarCompletitud()
}

async function enviarRevision() {
  enviando.value = true
  mensajeAccion.value = null
  try {
    const { data } = await api.post(`/docente/secuencias/${secuencia.value.id}/enviar-revision`)
    secuencia.value.estado = data.estado
    mensajeAccion.value = { tipo: 'ok', texto: 'Secuencia enviada a revisión técnica correctamente.' }
  } catch (e) {
    const errores = e.response?.data?.errors?.completitud
    mensajeAccion.value = { tipo: 'error', texto: errores ? errores.join(' ') : (e.response?.data?.message || 'No se pudo enviar.') }
  } finally {
    enviando.value = false
  }
}
async function eliminarSecuencia() {
  if (!confirm('¿Eliminar esta secuencia permanentemente? Esta acción no se puede deshacer.')) return
  eliminando.value = true
  try {
    await api.delete(`/docente/secuencias/${secuencia.value.id}`)
    router.push({ name: 'secuencias-docente' })
  } catch (e) {
    mensajeAccion.value = { tipo: 'error', texto: e.response?.data?.message || 'No se pudo eliminar la secuencia.' }
  } finally {
    eliminando.value = false
  }
}

async function cancelarEnvio() {
  if (!confirm('¿Cancelar el envío y devolver el documento a estado Borrador?')) return
  const { data } = await api.post(`/docente/secuencias/${secuencia.value.id}/cancelar-envio`)
  secuencia.value.estado = data.estado
  await cargar()
}
async function enviarValidacion() {
  const { data } = await api.post(`/revisor/secuencias/${secuencia.value.id}/enviar-validacion`)
  secuencia.value.estado = data.estado
}
async function rechazarComoRevisor() {
  if (!confirm('¿Rechazar y devolver esta secuencia al autor para correcciones?')) return
  const { data } = await api.post(`/revisor/secuencias/${secuencia.value.id}/rechazar`)
  secuencia.value.estado = data.estado
}

function badgeEstadoDoc(estado) {
  return { borrador: 'estado-En_desarrollo', en_revision: 'estado-En_revision', en_proceso_validacion: 'estado-En_proceso_validacion', validada: 'estado-Validada', rechazada: 'estado-Rechazada' }[estado] ?? 'estado-En_desarrollo'
}
function etiquetaEstado(estado) {
  return { borrador: 'Borrador', en_revision: 'En revisión técnica', en_proceso_validacion: 'En validación final', validada: 'Validada', rechazada: 'Rechazada' }[estado] ?? estado
}
</script>

<style scoped>
/* ==================================================
   LAYOUT MODO ENFOQUE (FOCUS MODE)
================================================== */
.editor-focus-layout {
  min-height: 100vh;
  background: var(--bg-page);
  padding: 16px 24px;
  /* Ocupa casi toda la pantalla */
}

.editor-loading {
  height: 80vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
}

.dashboard-layout {
  position: relative;
}

/* ==================================================
   GRID DINÁMICO Y COLAPSABLE
================================================== */
.editor-grid {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 20px;
  align-items: start;
  transition: grid-template-columns 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.editor-grid.sidebar-collapsed {
  grid-template-columns: 72px 1fr;
  /* Modo colapsado súper compacto */
}

/* UTILIDADES Y TEXTO */
.text-dim {
  color: var(--text-500);
}

.text-center {
  text-align: center;
}

.align-middle {
  vertical-align: middle;
}

.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: block;
}

.bg-soft {
  background: var(--bg-soft);
}

.font-bold {
  font-weight: 800;
}

.flex-1 {
  flex: 1;
}

.mb2 {
  margin-bottom: 8px;
}

.mb3 {
  margin-bottom: 12px;
}

.mb4 {
  margin-bottom: 16px;
}

.mt3 {
  margin-top: 12px;
}

.mt4 {
  margin-top: 16px;
}

.my-2 {
  margin: 8px 0;
}

/* ── SPINNER ── */
.spin {
  animation: girar 0.8s linear infinite;
}

.spin-slow {
  animation: girar 2.5s linear infinite;
}

@keyframes girar {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

/* ==================================================
   CONTORNOS SOFT UI 3D
================================================== */
.widget-contorno {
  background: #FFFFFF;
  border: 3px solid rgba(0, 182, 79, 0.15);
  border-radius: var(--r-xl);
  box-shadow: 0 10px 30px -10px rgba(0, 182, 79, 0.15);
  transition: transform 0.3s var(--ease-spring), box-shadow 0.3s ease;
  overflow: hidden;
}

.widget-contorno:hover {
  box-shadow: 0 15px 35px -10px rgba(0, 182, 79, 0.25);
}

.table-contorno {
  border: 2px solid rgba(0, 182, 79, 0.15);
  border-radius: var(--r-md);
  overflow: hidden;
}

/* ==================================================
   PANEL LATERAL (OUTLINE)
================================================== */
.editor-outline {
  padding: 24px 16px;
  position: sticky;
  top: 16px;
  max-height: calc(100vh - 32px);
  overflow-y: auto;
  transition: all 0.3s ease;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.collapsed .sidebar-header {
  justify-content: center;
  /* Centra el botón de hamburguesa cuando se oculta el logo */
}

.outline-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.icon-wrap-3d-small {
  width: 44px;
  height: 44px;
  background: rgba(0, 182, 79, 0.1);
  border: 2px solid rgba(0, 182, 79, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(0, 182, 79, 0.15);
  flex-shrink: 0;
  transform: rotate(-3deg);
}

.btn-toggle-menu {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: var(--r-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-toggle-menu:hover {
  background: var(--bg-soft);
}

.div-soft {
  height: 2px;
  background: var(--border-soft);
  margin: 20px 0;
  border-radius: var(--r-pill);
}

.nav-lbl {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--text-400);
  font-weight: 800;
  margin: 16px 0 8px 12px;
}

/* Botones de navegación del Índice */
.nav-btn-3d {
  width: 100%;
  text-align: left;
  padding: 10px 14px;
  border-radius: var(--r-md);
  background: transparent;
  border: 2px solid transparent;
  color: var(--text-600);
  font-weight: 700;
  font-size: 13.5px;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.2s ease;
  cursor: pointer;
  margin-bottom: 4px;
}

.nav-btn-3d:hover {
  background: var(--bg-soft);
  transform: translateX(4px);
  color: var(--text-900);
}

.nav-btn-3d.active {
  background: rgba(0, 182, 79, 0.1);
  border-color: rgba(0, 182, 79, 0.2);
  color: var(--uth-verde);
  box-shadow: 0 4px 10px rgba(0, 182, 79, 0.1);
}

.btn-volver {
  color: var(--text-500);
  font-weight: 800;
}

.btn-volver:hover {
  background: #FEF2F2;
  color: #EF4444;
}

/* Estilos para unidades (acordeón) */
.num-badge-3d {
  background: var(--text-800);
  color: white;
  width: 20px;
  height: 20px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  flex-shrink: 0;
  box-shadow: 0 2px 0 var(--text-900);
}

.nav-chevron {
  transition: transform 0.2s ease;
  color: var(--text-400);
}

.nav-chevron.open {
  transform: rotate(90deg);
}

.unidad-hijos {
  margin-left: 16px;
  padding-left: 12px;
  border-left: 2px solid var(--border-soft);
  margin-bottom: 12px;
}

.child-btn {
  padding: 8px 12px;
  font-size: 12.5px;
  font-weight: 600;
}

/* ==================================================
   ESTILOS PARA MODO COLAPSADO
================================================== */
.collapsed .nav-btn-3d {
  justify-content: center;
  padding: 12px 0;
}

.collapsed .nav-btn-3d:hover {
  transform: scale(1.1);
  /* Cambia efecto slide por efecto scale */
}

.collapsed .nav-btn-3d svg {
  margin: 0;
}

.collapsed .unidad-hijos {
  margin-left: 0;
  padding-left: 0;
  border-left: none;
}

.collapsed .child-btn {
  justify-content: center;
  padding: 12px 0;
}

/* ==================================================
   ÁREA DEL DOCUMENTO (DERECHA)
================================================== */
.editor-main {
  background: var(--bg-soft);
  padding: 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  overflow-x: auto;
  width: 100%;
  min-width: 0;
  /* Previene desbordamiento en grid */
}

.doc-wrap {
  background: #FFFFFF;
  width: 100%;
  max-width: 1200px;
  /* Ampliado para aprovechar el Focus Mode */
  border-radius: var(--r-md);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
  padding: 0;
  border: 1px solid var(--border-soft);
  margin: 0 auto;
}

/* Botones 3D Generales en el doc */
.btn-add-3d {
  background: var(--uth-verde);
  color: white;
  border: none;
  border-radius: var(--r-pill);
  padding: 10px 16px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 0 #007734, 0 8px 15px rgba(0, 182, 79, 0.3);
  transform: translateY(-2px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  cursor: pointer;
}

.btn-add-3d:hover:not(:disabled) {
  transform: translateY(-4px);
  box-shadow: 0 6px 0 #007734, 0 12px 20px rgba(0, 182, 79, 0.4);
}

.btn-add-3d:active:not(:disabled) {
  transform: translateY(2px);
  box-shadow: 0 0 0 #007734;
}

.btn-add-3d:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: translateY(0);
  box-shadow: none;
}

.btn-danger-3d {
  background: #EF4444;
  color: white;
  border: none;
  border-radius: var(--r-pill);
  padding: 10px 16px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 0 #B91C1C, 0 8px 15px rgba(239, 68, 68, 0.3);
  transform: translateY(-2px);
  transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
  cursor: pointer;
}

.btn-danger-3d:hover:not(:disabled) {
  background: #DC2626;
  transform: translateY(-4px);
  box-shadow: 0 6px 0 #991B1B, 0 12px 20px rgba(239, 68, 68, 0.4);
}

.btn-danger-3d:active:not(:disabled) {
  transform: translateY(2px);
  box-shadow: 0 0 0 #991B1B;
}

.btn-danger-3d:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: translateY(0);
  box-shadow: none;
}

.btn-page-3d {
  border-radius: var(--r-pill);
  border: 2px solid rgba(0, 182, 79, 0.3);
  color: var(--uth-verde);
  background: transparent;
  font-weight: 700;
  box-shadow: 0 4px 0 rgba(0, 182, 79, 0.15);
  transform: translateY(-2px);
  display: inline-flex;
  align-items: center;
}

.btn-page-3d:hover {
  background: var(--uth-verde);
  color: #FFFFFF;
  border-color: var(--uth-verde);
  box-shadow: 0 6px 0 #007734;
  transform: translateY(-4px);
}

/* Inputs Iluminados (Reemplazando los naked de la tabla) */
.input-3d-lit {
  width: 100%;
  padding: 8px 10px;
  border-radius: var(--r-sm);
  border: 2px solid transparent !important;
  background: transparent !important;
  font-family: var(--font) !important;
  color: var(--text-900);
  transition: all 0.2s ease;
  font-size: 13.5px;
}

.input-3d-lit:hover:not(:disabled),
.input-3d-lit:focus:not(:disabled) {
  border-color: var(--uth-verde) !important;
  background: #FFFFFF !important;
  box-shadow: 0 0 0 4px var(--uth-verde-ring), inset 0 2px 4px rgba(0, 0, 0, 0.02) !important;
  outline: none;
}

.input-3d-lit:disabled {
  color: var(--text-700);
  background: transparent !important;
}

/* ── ZONA DE FINALIZAR (CHECKLIST) ── */
.progreso-barra-3d {
  background: var(--border-soft);
  border-radius: 99px;
  height: 12px;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.progreso-relleno-3d {
  background: var(--uth-verde);
  height: 100%;
  border-radius: 99px;
  transition: width 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
  box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.15);
}

.checklist-3d {
  border: 2px solid var(--border-soft);
  border-radius: var(--r-lg);
  overflow: hidden;
  background: #FFFFFF;
}

.check-item-3d {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-soft);
  font-weight: 600;
  transition: background 0.2s;
}

.check-item-3d:last-child {
  border-bottom: none;
}

.check-item-3d.ok {
  background: #ECFDF5;
  color: #065F46;
}

.check-item-3d.falta {
  background: #FEF2F2;
  color: #991B1B;
  cursor: pointer;
}

.check-item-3d.falta:hover {
  background: #FEE2E2;
}

.check-icon-wrap {
  display: flex;
  align-items: center;
}

/* ── ANIMACIONES Y ALERTAS ── */
.alert-bounce {
  animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

/* ── AVISO FLOTANTE DE GUARDADO ── */
.hint-autoguardado {
  margin: 0 1.2rem .8rem;
  font-size: 12px;
  color: var(--text-400);
  font-weight: 600;
}

.badge-autoguardado {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 1000;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: var(--r-pill);
  font-size: 13px;
  font-weight: 800;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border: 2px solid transparent;
}

.badge-autoguardado.ag-guardando {
  background: #EFF6FF;
  color: #1D4ED8;
  border-color: #BFDBFE;
}

.badge-autoguardado.ag-guardado {
  background: #ECFDF5;
  color: #047857;
  border-color: #A7F3D0;
}

.badge-autoguardado.ag-error {
  background: #FEF2F2;
  color: #B91C1C;
  border-color: #FECACA;
}

.fade-guardado-enter-active,
.fade-guardado-leave-active {
  transition: all .3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.fade-guardado-enter-from,
.fade-guardado-leave-to {
  opacity: 0;
  transform: translateY(-20px) scale(0.9);
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }

  to {
    opacity: 1;
    transform: scale(1);
  }
}

@media (max-width: 1024px) {
  .editor-grid {
    grid-template-columns: 1fr;
  }

  .editor-outline {
    position: static;
    max-height: none;
  }
}
</style>