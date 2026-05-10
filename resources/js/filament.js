// Add registration link to Filament login page
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the login page
    const loginForm = document.querySelector('form[wire\\:submit\\.prevent="authenticate"]');
    
    if (loginForm) {
        // Check if link already exists
        const existingLink = document.querySelector('#register-link-custom');
        if (!existingLink) {
            // Create registration link
            const linkDiv = document.createElement('div');
            linkDiv.id = 'register-link-custom';
            linkDiv.className = 'mt-4 text-center';
            linkDiv.innerHTML = `
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Немає облікового запису?
                    <a href="/register" class="font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                        Зареєструватися
                    </a>
                </p>
            `;
            
            // Insert after the form
            loginForm.appendChild(linkDiv);
        }
    }
});

