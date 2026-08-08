import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null,
    roles: JSON.parse(localStorage.getItem('roles') || '[]'),

    // Estado temporal mientras se resuelve el segundo paso de 2FA
    // (no se persiste: si se recarga la página, se debe iniciar sesión de nuevo)
    twoFactorChallenge: null,
    twoFactorMethod: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    tieneRol: (state) => (rol) => state.roles.includes(rol),
  },

  actions: {
    async login(email, password) {
      const { data } = await api.post('/login', { email, password })

      if (data.requires_2fa) {
        this.twoFactorChallenge = data.challenge_token
        this.twoFactorMethod = data.method
        return data
      }

      this._guardarSesion(data)
      return data
    },

    async verifyTwoFactor(code) {
      const { data } = await api.post('/2fa/verify', {
        challenge_token: this.twoFactorChallenge,
        code,
      })
      this.twoFactorChallenge = null
      this.twoFactorMethod = null
      this._guardarSesion(data)
      return data
    },

    async resendTwoFactorCode() {
      return api.post('/2fa/resend', { challenge_token: this.twoFactorChallenge })
    },

    async logout() {
      try {
        await api.post('/logout')
      } catch (e) {
        // limpiamos la sesión local aunque falle en el servidor
      }
      this._limpiarSesion()
    },

    async forgotPassword(email) {
      const { data } = await api.post('/forgot-password', { email })
      return data
    },

    async resetPassword({ token, email, password, password_confirmation }) {
      const { data } = await api.post('/reset-password', {
        token,
        email,
        password,
        password_confirmation,
      })
      return data
    },

    _guardarSesion(data) {
      this.token = data.token
      this.user = data.user
      this.roles = data.roles
      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
      localStorage.setItem('roles', JSON.stringify(data.roles))
    },

    _limpiarSesion() {
      this.token = null
      this.user = null
      this.roles = []
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('roles')
    },
  },
})
