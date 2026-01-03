import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const usePosStore = defineStore('pos', () => {
    // State
    const cart = ref([]);
    const session = ref(null);
    const searchQuery = ref('');

    // Getters
    const subtotal = computed(() => {
        return cart.value.reduce((total, item) => total + (item.price * item.quantity), 0);
    });

    const tax = computed(() => {
        // Example: 10% tax. This logic can be adjusted based on settings.
        return 0; // Keeping it simple for now as per previous logic (gross sales) or implement tax logic later
    });

    const total = computed(() => {
        return subtotal.value + tax.value;
    });

    const itemCount = computed(() => {
        return cart.value.reduce((count, item) => count + item.quantity, 0);
    });

    // Actions
    function setSession(sessionData) {
        session.value = sessionData;
    }

    function addItem(product) {
        const existingItem = cart.value.find(item => item.id === product.id);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.value.push({
                ...product,
                quantity: 1,
            });
        }
    }

    function removeItem(productId) {
        const index = cart.value.findIndex(item => item.id === productId);
        if (index !== -1) {
            cart.value.splice(index, 1);
        }
    }

    function updateQuantity(productId, quantity) {
        const item = cart.value.find(item => item.id === productId);
        if (item) {
            item.quantity = quantity;
            if (item.quantity <= 0) {
                removeItem(productId);
            }
        }
    }

    function clearCart() {
        cart.value = [];
    }

    return {
        cart,
        session,
        searchQuery,
        subtotal,
        tax,
        total,
        itemCount,
        setSession,
        addItem,
        removeItem,
        updateQuantity,
        clearCart,
    };
});
