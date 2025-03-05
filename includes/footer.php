</div> <!-- Fin du conteneur principal -->
            
            <footer class="mt-auto py-4 bg-white border-t border-gray-200">
                <div class="container px-6">
                    <p class="text-center text-gray-600 text-sm">&copy; <?= date('Y') ?> - <?= APP_NAME ?></p>
                </div>
            </footer>
        </div> <!-- Fin du main-content -->
    </div> <!-- Fin du dashboard-layout -->

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
        });
    </script>
</body>
</html>