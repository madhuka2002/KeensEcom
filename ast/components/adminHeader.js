// Wait for document to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns
    var dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(function(dropdown) {
        new bootstrap.Dropdown(dropdown);
    });

    // Quick Actions (3-dot menu) handling
    const quickActionsBtn = document.querySelector('.btn-light.rounded-pill');
    const quickActionsMenu = document.querySelector('.dropdown-menu');

    if (quickActionsBtn) {
        // Add hover effect
        quickActionsBtn.addEventListener('mouseover', function() {
            this.style.backgroundColor = 'rgba(67, 97, 238, 0.1)';
        });

        quickActionsBtn.addEventListener('mouseout', function() {
            this.style.backgroundColor = '';
        });

        // Add ripple effect on click
        quickActionsBtn.addEventListener('click', function(e) {
            let ripple = document.createElement('div');
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            let rect = this.getBoundingClientRect();
            let x = e.clientX - rect.left;
            let y = e.clientY - rect.top;
            
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            setTimeout(() => {
                ripple.remove();
            }, 1000);
        });
    }

    // Handle dropdown items hover effect
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('mouseover', function() {
            this.style.backgroundColor = 'rgba(67, 97, 238, 0.1)';
        });

        item.addEventListener('mouseout', function() {
            this.style.backgroundColor = '';
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });

    // Handle mobile menu
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarContent = document.querySelector('#navbarContent');

    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            navbarContent.classList.toggle('show');
        });
    }

    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert-message');
    alerts.forEach(alert => {
        setTimeout(() => {
            // Create fade out effect
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            
            // Remove alert after fade
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 3000);
    });

    // Add smooth scroll for navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const targetElement = document.querySelector(href);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
});

// Add required CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    .btn-light.rounded-pill {
        position: relative;
        overflow: hidden;
    }

    .ripple {
        position: absolute;
        background: rgba(67, 97, 238, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }

    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .dropdown-menu {
        transform-origin: top right;
        animation: dropdown-animation 0.2s ease-out;
    }

    @keyframes dropdown-animation {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .navbar-toggler {
        transition: transform 0.2s ease;
    }

    .navbar-toggler:active {
        transform: scale(0.95);
    }

    .nav-link {
        transition: transform 0.2s ease;
    }

    .nav-link:active {
        transform: scale(0.95);
    }
`;
document.head.appendChild(style);