document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    if (!passwordInput) return;

    const strengthMeter = document.createElement('div');
    strengthMeter.className = 'password-strength mt-2';
    passwordInput.parentNode.appendChild(strengthMeter);

    passwordInput.addEventListener('input', function () {
        const password = this.value;
        let strength = 0;

        // Length check
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;

        // Character type checks
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        // Strength text and colors
        const strengthText = ['Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong'][strength] || '';
        const strengthColors = ['danger', 'warning', 'info', 'success', 'success'];

        strengthMeter.innerHTML = `
            <div class="progress" style="height: 5px;">
                <div class="progress-bar bg-${strengthColors[strength]}" 
                     role="progressbar" 
                     style="width: ${(strength + 1) * 20}%" 
                     aria-valuenow="${(strength + 1) * 20}" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
            <small class="text-${strengthColors[strength]}">${strengthText}</small>
        `;
    });

    // Confirm password validation
    const confirmPasswordInput = document.getElementById('confirm_password');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function () {
            const password = document.getElementById('password').value;
            if (this.value && this.value !== password) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
});