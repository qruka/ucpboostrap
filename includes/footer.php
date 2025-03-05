</div> <!-- Fin du conteneur principal -->
            
            <footer class="mt-auto py-4 bg-white border-t border-gray-200">
                <div class="container px-6">
                    <p class="text-center text-gray-600 text-sm">&copy; <?= date('Y') ?> - <?= APP_NAME ?></p>
                </div>
            </footer>
        </div> <!-- Fin du main-content -->
    </div> <!-- Fin du dashboard-layout -->

    <!-- jQuery (nécessaire pour DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    
    <!-- JavaScript personnalisé -->
    <script>
        // Initialisation des comportements après chargement du DOM
        document.addEventListener('DOMContentLoaded', function() {
            // Gestionnaire pour le toggle du sidebar
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const body = document.body;
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    
                    // Ajouter/supprimer overlay
                    let overlay = document.querySelector('.sidebar-overlay');
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.className = 'sidebar-overlay';
                        body.appendChild(overlay);
                    }
                    
                    overlay.classList.toggle('active');
                    
                    // Gestionnaire pour fermer le sidebar en cliquant sur l'overlay
                    overlay.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    });
                });
            }
            
            // Gestion des alertes avec auto-fermeture
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.classList.add('opacity-0');
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'opacity 300ms, transform 300ms';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
            
            // Bouton de fermeture des alertes
            const closeButtons = document.querySelectorAll('.close-alert');
            closeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const alert = button.closest('.alert');
                    alert.classList.add('opacity-0');
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'opacity 300ms, transform 300ms';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                });
            });
            
            // Initialisation des DataTables
            if (typeof $.fn.DataTable !== 'undefined') {
                $('.data-table').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
                    },
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tous"]]
                });
            }
            
            // Confirmation pour actions sensibles
            const confirmActions = document.querySelectorAll('[data-confirm]');
            confirmActions.forEach(function(element) {
                element.addEventListener('click', function(event) {
                    const message = this.getAttribute('data-confirm');
                    if (!confirm(message)) {
                        event.preventDefault();
                    }
                });
            });
            
            // Initialiser le contrôle de force du mot de passe
            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this);
                });
            }
            
            /**
             * Vérifier la force du mot de passe
             * @param {HTMLInputElement} input - Le champ de mot de passe
             */
            function checkPasswordStrength(input) {
                const password = input.value;
                let strength = 0;
                
                // Trouver ou créer l'indicateur de force
                let strengthMeter = document.getElementById('password-strength');
                if (!strengthMeter) {
                    strengthMeter = document.createElement('div');
                    strengthMeter.id = 'password-strength';
                    strengthMeter.className = 'mt-1 h-2 rounded';
                    input.parentNode.appendChild(strengthMeter);
                }
                
                // Trouver ou créer le texte de force
                let strengthText = document.getElementById('password-strength-text');
                if (!strengthText) {
                    strengthText = document.createElement('div');
                    strengthText.id = 'password-strength-text';
                    strengthText.className = 'text-xs mt-1';
                    input.parentNode.appendChild(strengthText);
                }
                
                // Réinitialiser
                strengthMeter.className = 'mt-1 h-2 rounded';
                strengthMeter.style.width = '0%';
                strengthText.className = 'text-xs mt-1';
                strengthText.textContent = '';
                
                if (password.length === 0) {
                    return;
                }
                
                // Calculer la force
                if (password.length >= 8) strength += 1;
                if (password.length >= 12) strength += 1;
                if (/[A-Z]/.test(password)) strength += 1;
                if (/[0-9]/.test(password)) strength += 1;
                if (/[^A-Za-z0-9]/.test(password)) strength += 1;
                
                // Afficher la force
                const percentage = Math.min(100, (strength / 5) * 100);
                strengthMeter.style.width = percentage + '%';
                
                if (strength < 2) {
                    strengthMeter.classList.add('bg-red-500');
                    strengthText.classList.add('text-red-600');
                    strengthText.textContent = 'Très faible';
                } else if (strength < 3) {
                    strengthMeter.classList.add('bg-orange-500');
                    strengthText.classList.add('text-orange-600');
                    strengthText.textContent = 'Faible';
                } else if (strength < 4) {
                    strengthMeter.classList.add('bg-yellow-500');
                    strengthText.classList.add('text-yellow-600');
                    strengthText.textContent = 'Moyen';
                } else if (strength < 5) {
                    strengthMeter.classList.add('bg-blue-500');
                    strengthText.classList.add('text-blue-600');
                    strengthText.textContent = 'Fort';
                } else {
                    strengthMeter.classList.add('bg-green-500');
                    strengthText.classList.add('text-green-600');
                    strengthText.textContent = 'Très fort';
                }
            }
            
            // Gestion des menus déroulants
            const dropdownToggles = document.querySelectorAll('[data-dropdown-toggle]');
            dropdownToggles.forEach(toggle => {
                const dropdownId = toggle.getAttribute('data-dropdown-toggle');
                const dropdown = document.getElementById(dropdownId);
                
                if (dropdown) {
                    // Afficher automatiquement si un sous-menu contient la page active
                    const hasActiveChild = dropdown.querySelector('.active');
                    if (hasActiveChild) {
                        dropdown.classList.remove('hidden');
                        // Rotation de l'icône
                        const icon = toggle.querySelector('.fa-chevron-down');
                        if (icon) {
                            icon.classList.remove('fa-chevron-down');
                            icon.classList.add('fa-chevron-up');
                        }
                    }
                    
                    toggle.addEventListener('click', () => {
                        dropdown.classList.toggle('hidden');
                        
                        // Rotation de l'icône
                        const icon = toggle.querySelector('.fas');
                        if (icon.classList.contains('fa-chevron-down')) {
                            icon.classList.remove('fa-chevron-down');
                            icon.classList.add('fa-chevron-up');
                        } else {
                            icon.classList.remove('fa-chevron-up');
                            icon.classList.add('fa-chevron-down');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>