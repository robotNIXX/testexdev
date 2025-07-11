import { defineStore } from 'pinia'

interface AuthState {
  token: string | null
  user: any | null
  isAuthenticated: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    token: null,
    user: null,
    isAuthenticated: false
  }),

  getters: {
    getToken: (state) => state.token,
    getUser: (state) => state.user,
    isLoggedIn: (state) => state.isAuthenticated
  },

  actions: {
    async login(email: string, password: string) {
      try {
        // Получить CSRF cookie
        await $fetch('/sanctum/csrf-cookie', { credentials: 'include' })

        // Получить XSRF-TOKEN из cookie
        function getCsrfToken() {
          const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
          return match ? decodeURIComponent(match[1]) : null
        }
        const csrfToken = getCsrfToken()

        // Выполнить логин
        const response = await $fetch('/api/login', {
          method: 'POST',
          body: {
            email,
            password
          },
          credentials: 'include',
          headers: {
            'X-XSRF-TOKEN': csrfToken
          }
        })

        this.token = response.token
        this.user = response.user
        this.isAuthenticated = true

        localStorage.setItem('auth_token', response.token)
        localStorage.setItem('user', JSON.stringify(response.user))

        return response
      } catch (error) {
        console.error('Login error:', error)
        throw error
      }
    },

    async logout() {
      try {
        if (this.token) {
          await $fetch('/api/logout', {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${this.token}`
            },
            credentials: 'include'
          })
        }
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.token = null
        this.user = null
        this.isAuthenticated = false

        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
      }
    },

    async checkAuth() {
      try {
        if (process.server) {
          return false
        }
        
        const token = localStorage.getItem('auth_token')
        const user = localStorage.getItem('user')

        if (!token || !user) {
          this.token = null
          this.user = null
          this.isAuthenticated = false
          return false
        }

        this.token = token
        this.user = JSON.parse(user)
        this.isAuthenticated = true
        return true
      } catch (error) {
        console.error('Auth check error:', error)
        this.token = null
        this.user = null
        this.isAuthenticated = false
        return false
      }
    }
  }
}) 