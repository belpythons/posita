import { ref, computed } from 'vue'

const currentTheme = ref(localStorage.getItem('theme') || 'light')

export function useTheme() {
    const initTheme = () => {
        const html = document.documentElement

        // Explicitly default to 'light' if nothing is in localStorage
        const savedTheme = localStorage.getItem('theme') || 'light'

        currentTheme.value = savedTheme

        // Only add 'dark' class if theme is exactly 'dark'
        // This ensures 'light' allows the default light styles to apply
        if (savedTheme === 'dark') {
            html.classList.add('dark')
        } else {
            html.classList.remove('dark')
        }

        // Always ensure localStorage has a value
        if (!localStorage.getItem('theme')) {
            localStorage.setItem('theme', 'light')
        }
    }

    const toggleTheme = () => {
        const html = document.documentElement
        if (currentTheme.value === 'light') {
            currentTheme.value = 'dark'
            html.classList.add('dark')
            localStorage.setItem('theme', 'dark')
        } else {
            currentTheme.value = 'light'
            html.classList.remove('dark')
            localStorage.setItem('theme', 'light')
        }
    }

    return {
        theme: currentTheme,
        initTheme,
        toggleTheme,
        isDark: computed(() => currentTheme.value === 'dark'),
    }
}
