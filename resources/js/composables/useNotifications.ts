import { ref, computed } from 'vue'

interface Notification {
    id: string;
    message: string;
    type: string;
    url?: string;
    read_at: string | null;
    created_at: string;
}

let storeInstance: ReturnType<typeof createNotificationStore> | null = null;
let initialServerCountSet = false;

function createNotificationStore() {
    const realTimeNotifications = ref<Notification[]>([])
    const serverUnreadCount = ref(0)
    
    const totalUnreadCount = computed(() => {
        const realTimeUnread = realTimeNotifications.value.filter(n => !n.read_at).length
        return serverUnreadCount.value + realTimeUnread
    })
    
    function addRealTimeNotification(notification: Notification) {
        realTimeNotifications.value.unshift(notification)
        if (realTimeNotifications.value.length > 50) {
            realTimeNotifications.value = realTimeNotifications.value.slice(0, 50)
        }
    }
    
    function markRealTimeAsRead(id: string) {
        const notification = realTimeNotifications.value.find(n => n.id === id)
        if (notification) {
            notification.read_at = new Date().toISOString()
        }
    }
    
    function setServerCount(count: number) {
        serverUnreadCount.value = count
    }
    
    function setInitialServerCount(count: number) {
        if (!initialServerCountSet) {
            serverUnreadCount.value = count
            initialServerCountSet = true
        }
    }
    
    function resetInitialCountFlag() {
        initialServerCountSet = false
    }
    
    return {
        realTimeNotifications,
        serverUnreadCount,
        totalUnreadCount,
        addRealTimeNotification,
        markRealTimeAsRead,
        setServerCount,
        setInitialServerCount,
        resetInitialCountFlag
    }
}

export function useNotifications() {
    if (!storeInstance) {
        storeInstance = createNotificationStore();
    }
    return storeInstance;
}