<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow-sm">
      <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-900">Операции</h1>
        <div class="flex space-x-4">
          <NuxtLink 
            to="/" 
            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition-colors"
          >
            Назад
          </NuxtLink>
          <button
            @click="handleLogout"
            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition-colors"
          >
            Выйти
          </button>
        </div>
      </div>
    </header>

    <div class="container mx-auto px-4 py-8">
      <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Все операции</h2>
            <div class="flex items-center space-x-4">
              <input
                v-model="searchQuery"
                @input="handleSearch"
                type="text"
                placeholder="Поиск по описанию..."
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <select
                v-model="sortOrder"
                @change="handleSortChange"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="desc">Сначала новые</option>
                <option value="asc">Сначала старые</option>
              </select>
              <select
                v-model="perPage"
                @change="handlePerPageChange"
                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="10">10 на странице</option>
                <option value="25">25 на странице</option>
                <option value="50">50 на странице</option>
              </select>
            </div>
          </div>

          <div v-if="operationsStore.isLoading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <p class="mt-2 text-gray-600">Загрузка операций...</p>
          </div>

          <div v-else-if="operationsStore.allOperations.length > 0" class="space-y-3">
            <div 
              v-for="operation in operationsStore.allOperations" 
              :key="operation.id"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-md hover:bg-gray-100 transition-colors"
            >
              <div class="flex-1">
                <div class="flex items-center space-x-3">
                  <span 
                    :class="operation.action === 'deposit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    class="px-3 py-1 rounded-full text-sm font-medium"
                  >
                    {{ operation.action === 'deposit' ? 'Пополнение' : 'Снятие' }}
                  </span>
                  <span class="text-lg font-semibold text-gray-900">
                    {{ operation.amount }} ₽
                  </span>
                </div>
                <div class="text-sm text-gray-600 mt-2">
                  {{ operation.description || 'Без описания' }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  {{ new Date(operation.created_at).toLocaleString('ru-RU') }}
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-8">
            <p class="text-gray-500">Операций не найдено</p>
          </div>

          <div v-if="operationsStore.pagination" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Показано {{ operationsStore.pagination?.from || 0 }} - {{ operationsStore.pagination?.to || 0 }} из {{ operationsStore.pagination?.total || 0 }} операций
            </div>
            
            <div class="flex items-center space-x-2">
              <button
                @click="goToPage(operationsStore.pagination?.current_page - 1)"
                :disabled="!operationsStore.pagination || operationsStore.pagination.current_page <= 1"
                class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Назад
              </button>
              
              <div class="flex items-center space-x-1">
                <button
                  v-for="page in visiblePages"
                  :key="page"
                  @click="typeof page === 'number' ? goToPage(page) : null"
                  :disabled="typeof page !== 'number'"
                  :class="[
                    'px-3 py-2 border rounded-md text-sm font-medium',
                    typeof page === 'number' && page === operationsStore.pagination?.current_page
                      ? 'bg-blue-500 text-white border-blue-500'
                      : typeof page === 'number'
                      ? 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'
                      : 'border-gray-300 text-gray-400 bg-white cursor-default'
                  ]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button
                @click="goToPage(operationsStore.pagination?.current_page + 1)"
                :disabled="!operationsStore.pagination || operationsStore.pagination.current_page >= operationsStore.pagination.last_page"
                class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Вперед
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Простая реализация debounce
function debounce(fn, delay) {
  let timeout
  return (...args) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => fn(...args), delay)
  }
}

definePageMeta({
  middleware: 'auth'
})

const router = useRouter()
const authStore = useAuthStore()
const operationsStore = useOperationsStore()

const searchQuery = ref('')
const sortOrder = ref('desc')
const perPage = ref(10)

const handleSearch = debounce(async () => {
  await operationsStore.searchOperations(searchQuery.value)
}, 300)

const handleSortChange = async () => {
  operationsStore.updateFilters({ sort: sortOrder.value })
  await operationsStore.fetchOperations(1)
}

const handlePerPageChange = async () => {
  operationsStore.updateFilters({ perPage: parseInt(perPage.value) })
  await operationsStore.fetchOperations(1)
}

const goToPage = async (page) => {
  if (operationsStore.pagination && page >= 1 && page <= operationsStore.pagination.last_page) {
    await operationsStore.goToPage(page)
  }
}

const handleLogout = async () => {
  await authStore.logout()
  window.location.href = '/login'
}

const visiblePages = computed(() => {
  return operationsStore.pagination ? operationsStore.getVisiblePages() : []
})

onMounted(async () => {
  await operationsStore.fetchOperations(1)
})
</script> 