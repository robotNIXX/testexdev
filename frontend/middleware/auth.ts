export default defineNuxtRouteMiddleware(async (to, from) => {
  if (process.server) {
    return
  }
  
  if (to.path === '/login') {
    return
  }
  
  try {
    const authStore = useAuthStore()
    const isAuthenticated = await authStore.checkAuth()
    
    if (!isAuthenticated) {
      window.location.href = '/login'
      return
    }
  } catch (error) {
    window.location.href = '/login'
    return
  }
}) 