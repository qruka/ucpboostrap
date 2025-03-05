/**
 * Script pour la section d'administration
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des composants
    initDataTables();
    initConfirmActions();
    initBanModals();
    initUserSearch();
    initAvatarPreview();
    initCharts();
});

/**
 * Initialisation des DataTables
 */
function initDataTables() {
    // Vérifier si DataTables est chargé
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.data-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Tous"]],
            dom: '<"flex flex-col md:flex-row md:items-center md:justify-between mb-4"<"flex-grow"f><"flex-shrink-0"l>>t<"flex flex-col md:flex-row md:items-center md:justify-between mt-4"<"flex-grow"i><"flex-shrink-0"p>>',
            initComplete: function() {
                // Personnalisation des éléments de DataTables
                $('.dataTables_filter input').addClass('px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm');
                $('.dataTables_length select').addClass('px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm');
                $('.dataTables_paginate .paginate_button').addClass('px-3 py-1 border border-gray-300 rounded-md mx-1 hover:bg-gray-50');
                $('.dataTables_paginate .paginate_button.current').addClass('bg-blue-50 text-blue-600 border-blue-500');
            }
        });
    }
}

/**
 * Confirmation pour actions sensibles
 */
function initConfirmActions() {
    const confirmActions = document.querySelectorAll('[data-confirm]');
    
    confirmActions.forEach(function(element) {
        element.addEventListener('click', function(event) {
            const message = this.getAttribute('data-confirm');
            
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });
}

/**
 * Gestion des modals de bannissement
 */
function initBanModals() {
    // Modal de ban
    const banUserModal = document.getElementById('banUserModal');
    
    if (banUserModal) {
        // Ouverture de la modal
        const showBanModalButtons = document.querySelectorAll('[data-action="show-ban-modal"]');
        
        showBanModalButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const username = this.getAttribute('data-username');
                
                document.getElementById('userId').value = userId;
                document.getElementById('banModalUsername').textContent = username;
                
                banUserModal.classList.remove('hidden');
            });
        });
        
        // Fermeture de la modal
        const closeModalButtons = document.querySelectorAll('#closeModal, #cancelModal, #banModalOverlay');
        
        closeModalButtons.forEach(button => {
            button.addEventListener('click', function() {
                banUserModal.classList.add('hidden');
            });
        });
    }
}

/**
 * Recherche en temps réel pour la liste d'utilisateurs
 */
function initUserSearch() {
    const searchInput = document.getElementById('searchUsers');
    
    if (searchInput && !document.querySelector('.data-table')) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.querySelector('.table tbody');
            
            if (table) {
                const rows = table.querySelectorAll('tr');
                
                rows.forEach(function(row) {
                    const username = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                    const email = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                    
                    if (username.includes(searchValue) || email.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    }
}

/**
 * Preview des avatars lors du téléchargement
 */
function initAvatarPreview() {
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    avatarPreview.src = reader.result;
                    avatarPreview.style.display = 'block';
                });
                
                reader.readAsDataURL(file);
            }
        });
    }
}

/**
 * Initialisation des graphiques pour le tableau de bord
 */
function initCharts() {
    // Vérifier si Chart.js est disponible
    if (typeof Chart !== 'undefined') {
        // Graphique des inscriptions sur les 7 derniers jours
        const registrationsCtx = document.getElementById('registrationsChart');
        
        if (registrationsCtx) {
            // Données simulées
            const dates = [];
            const counts = [];
            
            // Générer des dates pour les 7 derniers jours
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                dates.push(date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }));
                
                // Nombre aléatoire entre 1 et 10
                counts.push(Math.floor(Math.random() * 10) + 1);
            }
            
            new Chart(registrationsCtx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Nouvelles inscriptions',
                        data: counts,
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointBackgroundColor: 'rgba(59, 130, 246, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        // Graphique de répartition des utilisateurs par niveau
        const userLevelsCtx = document.getElementById('userLevelsChart');
        
        if (userLevelsCtx) {
            // Récupérer les données depuis les éléments HTML
            const regularUsers = parseInt(document.getElementById('regularUsersCount')?.textContent || 0);
            const moderators = parseInt(document.getElementById('moderatorsCount')?.textContent || 0);
            const admins = parseInt(document.getElementById('adminsCount')?.textContent || 0);
            const superAdmins = parseInt(document.getElementById('superAdminsCount')?.textContent || 0);
            
            new Chart(userLevelsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Utilisateurs', 'Modérateurs', 'Administrateurs', 'Super Admins'],
                    datasets: [{
                        data: [regularUsers, moderators, admins, superAdmins],
                        backgroundColor: [
                            'rgba(107, 114, 128, 0.7)',
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(139, 92, 246, 0.7)'
                        ],
                        borderColor: [
                            'rgba(107, 114, 128, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(139, 92, 246, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        }
    }
}

/**
 * Afficher la modal de suspension d'un utilisateur
 * @param {number} userId - ID de l'utilisateur
 * @param {string} username - Nom d'utilisateur
 */
function showBanModal(userId, username) {
    document.getElementById('userId').value = userId;
    document.getElementById('banModalUsername').textContent = username;
    document.getElementById('banUserModal').classList.remove('hidden');
}