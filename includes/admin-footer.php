</div> <!-- Fin du conteneur principal -->
            
            <footer class="mt-auto py-4 bg-white border-t border-gray-200">
                <div class="container px-6">
                    <p class="text-center text-gray-600 text-sm">&copy; <?= date('Y') ?> - <?= APP_NAME ?> | Administration</p>
                </div>
            </footer>
        </div> <!-- Fin du main-content -->
    </div> <!-- Fin du dashboard-layout -->

    <!-- jQuery (nécessaire pour DataTables) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            
            // Modal de bannissement
            if (typeof showBanModal === 'function') {
                const showBanButtons = document.querySelectorAll('[data-action="show-ban-modal"]');
                showBanButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-user-id');
                        const username = this.getAttribute('data-username');
                        showBanModal(userId, username);
                    });
                });
            }
            
            // Fermeture des modals
            const closeModalButtons = document.querySelectorAll('#closeModal, #cancelModal, #banModalOverlay');
            closeModalButtons.forEach(button => {
                if (button) {
                    button.addEventListener('click', function() {
                        const modal = document.getElementById('banUserModal');
                        if (modal) {
                            modal.classList.add('hidden');
                        }
                    });
                }
            });
        });
        
        /**
         * Affiche la modal de suspension d'un utilisateur
         * @param {number} userId - ID de l'utilisateur
         * @param {string} username - Nom d'utilisateur
         */
        function showBanModal(userId, username) {
            const modal = document.getElementById('banUserModal');
            if (!modal) return;
            
            document.getElementById('userId').value = userId;
            document.getElementById('banModalUsername').textContent = username;
            modal.classList.remove('hidden');
        }
    </script>
</body>
</html>

