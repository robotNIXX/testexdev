<template>
  <div class="min-h-screen bg-gray-100">
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mb-4"></div>
        <p class="text-gray-600">Проверка авторизации...</p>
      </div>
    </div>
    
    <div v-else>
      <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
          <h1 class="text-xl font-semibold text-gray-900">Панель управления</h1>
          <button
            @click="handleLogout"
            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition-colors"
          >
            Выйти
          </button>
        </div>
      </header>

      <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
          <h1 class="text-4xl font-bold text-gray-900 mb-8 text-center">
            NuxtJS + Laravel + PostgreSQL
          </h1>
          
          <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Баланс</h2>
            <div class="flex items-center space-x-4">
              <div class="text-3xl font-bold text-gray-900">
                {{ userBalance !== null ? userBalance.toFixed(2) : '...' }}
              </div>
              <div class="text-sm text-gray-500">
                {{ userBalance !== null ? 'рублей' : 'Загрузка...' }}
              </div>
            </div>
          </div>

          <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-semibold text-gray-800">Последние операции</h2>
              <NuxtLink 
                to="/operations" 
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md transition-colors text-sm"
              >
                Все операции
              </NuxtLink>
            </div>
            <div v-if="operations.length > 0" class="space-y-3">
              <div 
                v-for="operation in operations" 
                :key="operation.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-md"
              >
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <span 
                      :class="operation.action === 'deposit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                      class="px-2 py-1 rounded-full text-xs font-medium"
                    >
                      {{ operation.action === 'deposit' ? 'Пополнение' : 'Снятие' }}
                    </span>
                    <span class="text-gray-900 font-medium">
                      {{ operation.amount }}
                    </span>
                  </div>
                  <div class="text-sm text-gray-600 mt-1">
                    {{ operation.description || 'Без описания' }}
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    {{ new Date(operation.created_at).toLocaleString('ru-RU') }}
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-gray-500 text-center py-8">
              Операций пока нет
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth'
})

const router = useRouter()
const authStore = useAuthStore()
const operationsStore = useOperationsStore()
const loading = ref(true)

const loadData = async () => {
  try {
    if (process.server) {
      return
    }
    
    const isAuthenticated = await authStore.checkAuth()
    
    if (!isAuthenticated) {
      window.location.href = '/login'
      return
    }
    
    await Promise.all([
      operationsStore.fetchRecentOperations(),
      operationsStore.fetchUserBalance()
    ])
  } catch (error) {
    console.error('Failed to load data:', error)
    window.location.href = '/login'
  }
}

const handleLogout = async () => {
  await authStore.logout()
  window.location.href = '/login'
}

onMounted(async () => {
  try {
    if (process.server) {
      loading.value = false
      return
    }
    
    const isAuthenticated = await authStore.checkAuth()
    if (!isAuthenticated) {
      window.location.href = '/login'
      return
    }
    
    await loadData()
    loading.value = false
    
    setInterval(loadData, 5000)
  } catch (error) {
    console.error('Failed to initialize:', error)
    window.location.href = '/login'
  }
})

const operations = computed(() => {
  if (!operationsStore) {
    return []
  }
  return operationsStore.recentOperations || []
})

const userBalance = computed(() => {
  if (!operationsStore) {
    return null
  }
  return operationsStore.userBalance || null
})
</script> 