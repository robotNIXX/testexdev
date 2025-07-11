import { defineStore } from 'pinia'

interface Operation {
  id: number
  amount: number
  action: 'deposit' | 'withdraw'
  description: string | null
  created_at: string
}

interface OperationsState {
  recentOperations: Operation[]
  allOperations: Operation[]
  userBalance: number | null
  loading: boolean
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
  } | null
  filters: {
    search: string
    sort: 'asc' | 'desc'
    perPage: number
  }
}

export const useOperationsStore = defineStore('operations', {
  state: (): OperationsState => ({
    recentOperations: [],
    allOperations: [],
    userBalance: null,
    loading: false,
    pagination: null,
    filters: {
      search: '',
      sort: 'desc',
      perPage: 10
    }
  }),

  getters: {
    getRecentOperations: (state) => state.recentOperations,
    getAllOperations: (state) => state.allOperations,
    getUserBalance: (state) => state.userBalance,
    isLoading: (state) => state.loading,
    getPagination: (state) => state.pagination,
    getFilters: (state) => state.filters
  },

  actions: {
    async fetchRecentOperations() {
      try {
        if (process.server) {
          this.recentOperations = []
          return
        }
        
        const authStore = useAuthStore()
        let token = authStore.getToken
        if (!token) {
          token = localStorage.getItem('auth_token')
        }

        if (!token) {
          this.recentOperations = []
          return
        }

        const response = await $fetch('/api/recent-operations', {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })

        if (Array.isArray(response)) {
          this.recentOperations = response
        } else if (response && Array.isArray(response.data)) {
          this.recentOperations = response.data
        } else {
          this.recentOperations = []
        }
      } catch (error) {
        console.error('Failed to fetch recent operations:', error)
        this.recentOperations = []
      }
    },

    async fetchUserBalance() {
      try {
        if (process.server) {
          this.userBalance = null
          return
        }
        
        const authStore = useAuthStore()
        let token = authStore.getToken
        if (!token) {
          token = localStorage.getItem('auth_token')
        }

        if (!token) {
          this.userBalance = null
          return
        }

        const response = await $fetch('/api/user', {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })
        
        if (typeof response === 'number') {
          this.userBalance = response
        } else if (response && typeof response.balance === 'number') {
          this.userBalance = response.balance
        } else if (response && typeof response.balance === 'string') {
          this.userBalance = parseFloat(response.balance)
        } else {
          this.userBalance = null
        }
      } catch (error) {
        console.error('Failed to fetch user balance:', error)
        this.userBalance = null
      }
    },

    async fetchOperations(page: number = 1) {
      this.loading = true
      try {
        const authStore = useAuthStore()
        let token = authStore.getToken
        if (!token) {
          token = localStorage.getItem('auth_token')
        }

        if (!token) return

        const params = new URLSearchParams({
          page: page.toString(),
          per_page: this.filters.perPage.toString(),
          sort: this.filters.sort,
          search: this.filters.search
        })

        const response = await $fetch(`/api/operations?${params}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })

        this.allOperations = response.data
        this.pagination = response.meta
      } catch (error) {
        console.error('Failed to fetch operations:', error)
      } finally {
        this.loading = false
      }
    },

    updateFilters(filters: Partial<OperationsState['filters']>) {
      this.filters = { ...this.filters, ...filters }
    },

    async searchOperations(search: string) {
      this.filters.search = search
      await this.fetchOperations(1)
    },

    async changeSort(sort: 'asc' | 'desc') {
      this.filters.sort = sort
      await this.fetchOperations(1)
    },

    async changePerPage(perPage: number) {
      this.filters.perPage = perPage
      await this.fetchOperations(1)
    },

    async goToPage(page: number) {
      await this.fetchOperations(page)
    },

    getVisiblePages() {
      if (!this.pagination) return []
      
      const current = this.pagination.current_page
      const last = this.pagination.last_page
      const pages = []
      
      if (last <= 7) {
        for (let i = 1; i <= last; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(last)
        } else if (current >= last - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = last - 4; i <= last; i++) {
            pages.push(i)
          }
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(last)
        }
      }
      
      return pages
    }
  }
}) 