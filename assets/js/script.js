document.addEventListener('DOMContentLoaded', function () {
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const passwordStrength = document.createElement('div');
    passwordStrength.className = 'password-strength mt-1';
    if (passwordInput) {
        passwordInput.parentNode.appendChild(passwordStrength);

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

            // Update strength meter
            const strengthText = ['Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong'][strength] || '';
            const strengthColors = ['danger', 'warning', 'info', 'success', 'success'];

            passwordStrength.innerHTML = `
                <div class="progress">
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
    }

    // Confirm password validation
    const confirmPasswordInput = document.getElementById('confirm_password');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function () {
            const password = document.getElementById('password').value;
            if (this.value !== password) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
});document.addEventListener('DOMContentLoaded', function () {
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const passwordStrength = document.createElement('div');
    passwordStrength.className = 'password-strength mt-1';
    if (passwordInput) {
        passwordInput.parentNode.appendChild(passwordStrength);

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

            // Update strength meter
            const strengthText = ['Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong'][strength] || '';
            const strengthColors = ['danger', 'warning', 'info', 'success', 'success'];

            passwordStrength.innerHTML = `
                <div class="progress">
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
    }

    // Confirm password validation
    const confirmPasswordInput = document.getElementById('confirm_password');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function () {
            const password = document.getElementById('password').value;
            if (this.value !== password) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
});