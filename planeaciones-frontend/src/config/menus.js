// Menú por rol. Si un usuario tiene varios roles, se fusionan todas las
// secciones correspondientes (ver menuFusionado más abajo).
export const MENU_POR_ROL = {
  Administrador: {
    items: [
      { label: 'Panel de administración', routeName: 'home-administrador' },
      { label: 'Carreras y especialidades', routeName: 'admin-academico' },
      { label: 'Usuarios', routeName: 'admin-usuarios' },
    ],
  },
  Director: {
    items: [
      { label: 'Panel', routeName: 'home-director' },
      { label: 'Validación de secuencias', routeName: 'secuencias-director' },
    ],
  },
  Docente: {
    items: [
      { label: 'Panel', routeName: 'home-docente' },
      { label: 'Mis secuencias', routeName: 'secuencias-docente' },
    ],
  },
  Revisor: {
    items: [
      { label: 'Panel', routeName: 'home-revisor' },
      { label: 'Cola de revisión', routeName: 'secuencias-revisor' },
    ],
  },
  Secretario: {
    items: [{ label: 'Seguimiento administrativo', routeName: 'home-secretario' }],
  },
}

// Orden de prioridad solo para decidir a dónde aterriza el usuario justo
// después de iniciar sesión (el menú, en cambio, siempre muestra TODO).
const PRIORIDAD_ROLES = ['Administrador', 'Director', 'Revisor', 'Secretario', 'Docente']

export function roleHomeName(roles = []) {
  const encontrado = PRIORIDAD_ROLES.find((r) => roles.includes(r))
  return encontrado ? `home-${encontrado.toLowerCase()}` : 'login'
}

// Devuelve un arreglo con una sección por cada rol que tenga el usuario,
// en el mismo orden de prioridad, con sus items de menú ya resueltos.
export function menuFusionado(roles = []) {
  return PRIORIDAD_ROLES
    .filter((rol) => roles.includes(rol))
    .map((rol) => ({ rol, items: MENU_POR_ROL[rol].items }))
}
