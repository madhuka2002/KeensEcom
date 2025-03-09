<!-- Customer Registration Card -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-user-plus me-2"></i>Add New Customer
    </div>
    <div class="card-body">
        <form id="customerRegistrationForm" method="post" action="" class="needs-validation" novalidate>
            <div class="row g-3">
                <!-- Personal Information -->
                <div class="col-md-6">
                    <label class="form-label">First Name</label>
                    <input 
                        type="text" 
                        name="first_name" 
                        class="form-control" 
                        required 
                        pattern="[A-Za-z\s]+" 
                        minlength="2" 
                        maxlength="50"
                    >
                    <div class="invalid-feedback">
                        Please enter a valid first name (2-50 letters)
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Last Name</label>
                    <input 
                        type="text" 
                        name="last_name" 
                        class="form-control" 
                        required 
                        pattern="[A-Za-z\s]+" 
                        minlength="2" 
                        maxlength="50"
                    >
                    <div class="invalid-feedback">
                        Please enter a valid last name (2-50 letters)
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        required 
                        maxlength="100"
                    >
                    <div class="invalid-feedback">
                        Please enter a valid email address
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone Number (Optional)</label>
                    <input 
                        type="tel" 
                        name="phone" 
                        class="form-control" 
                        pattern="[0-9\-\(\)\s]{10,20}"
                        maxlength="20"
                    >
                    <div class="invalid-feedback">
                        Please enter a valid phone number
                    </div>
                </div>

                <!-- Address -->
                <div class="col-12">
                    <label class="form-label">Address (Optional)</label>
                    <textarea 
                        name="address" 
                        class="form-control" 
                        maxlength="255" 
                        rows="3"
                    ></textarea>
                </div>

                <!-- Password -->
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        class="form-control" 
                        required 
                        minlength="8" 
                        maxlength="50"
                    >
                    <div class="invalid-feedback">
                        Password must be at least 8 characters
                    </div>
                    <div class="password-strength-meter mt-2">
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="password-strength-text text-muted"></small>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        class="form-control" 
                        required 
                        minlength="8" 
                        maxlength="50"
                    >
                    <div class="invalid-feedback">
                        Passwords must match
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="col-12 mt-4">
                    <button 
                        type="submit" 
                        name="add_customer" 
                        class="btn btn-primary btn-lg w-100"
                    >
                        <i class="fas fa-user-plus me-2"></i>Create Customer Profile
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Optional: JavaScript for Form Validation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.querySelector('input[name="password"]');
    const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
    const passwordStrengthMeter = document.querySelector('.password-strength-meter .progress-bar');
    const passwordStrengthText = document.querySelector('.password-strength-text');

    // Password Strength Meter
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;

        // Check password strength
        if (password.length >= 8) strength += 25;
        if (password.match(/[a-z]+/)) strength += 25;
        if (password.match(/[A-Z]+/)) strength += 25;
        if (password.match(/[0-9]+/)) strength += 25;

        // Update progress bar
        passwordStrengthMeter.style.width = `${strength}%`;
        passwordStrengthMeter.classList.remove('bg-danger', 'bg-warning', 'bg-success');

        // Set color and text based on strength
        if (strength < 50) {
            passwordStrengthMeter.classList.add('bg-danger');
            passwordStrengthText.textContent = 'Weak Password';
        } else if (strength < 75) {
            passwordStrengthMeter.classList.add('bg-warning');
            passwordStrengthText.textContent = 'Medium Password';
        } else {
            passwordStrengthMeter.classList.add('bg-success');
            passwordStrengthText.textContent = 'Strong Password';
        }
    });

    // Password Match Validation
    confirmPasswordInput.addEventListener('input', function() {
        if (passwordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.setCustomValidity('Passwords do not match');
        } else {
            confirmPasswordInput.setCustomValidity('');
        }
    });

    // Bootstrap Form Validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
});
</script>