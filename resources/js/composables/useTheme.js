import { ref, computed } from 'vue'

const currentTheme = ref(localStorage.getItem('theme') || 'light')

export function useTheme() {
    const initTheme = () => {
        const html = document.documentElement
        const savedTheme = localStorage.getItem('theme') || 'light'

        currentTheme.value = savedTheme

        if (savedTheme === 'dark') {
            html.classList.add('dark')
        } else {
            html.classList.remove('dark')
        }

        // Remove legacy overrides
        html.classList.remove('theme-neutral')
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
