document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des DataTables
    if ($.fn.DataTable) {
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
    
    // Gestion des modals de bannissement
    const banModal = document.getElementById('banUserModal');
    
    if (banModal) {
        banModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const username = button.getAttribute('data-username');
            
            const modalTitle = banModal.querySelector('.modal-title');
            const userIdInput = banModal.querySelector('#userId');
            
            modalTitle.textContent = `Suspendre l'utilisateur ${username}`;
            userIdInput.value = userId;
        });
    }
    
    // Preview des avatars lors du téléchargement
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
    
    // Recherche en temps réel
    const searchInput = document.getElementById('searchUsers');
    
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.querySelector('.users-table tbody');
            const rows = table.querySelectorAll('tr');
            
            rows.forEach(function(row) {
                const username = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                
                if (username.includes(searchValue) || email.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});