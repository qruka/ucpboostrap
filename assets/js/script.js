// Gestion des alertes avec auto-fermeture
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner toutes les alertes
    const alerts = document.querySelectorAll('.alert');
    
    // Ajouter un timer pour les fermer automatiquement après 5 secondes
    alerts.forEach(function(alert) {
        setTimeout(function() {
            // Créer un nouvel événement pour fermer l'alerte avec le Bootstrap JS
            const closeEvent = new Event('close.bs.alert');
            alert.dispatchEvent(closeEvent);
            
            // Ajouter la classe fade-out pour une animation douce
            alert.classList.add('fade-out');
            
            // Supprimer l'alerte après l'animation
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        }, 5000);
    });
    
    // Amélioration des formulaires
    const forms = document.querySelectorAll('form');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            // Ajouter une validation côté client si nécessaire
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    valid = false;
                    // Ajouter une classe d'erreur
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Empêcher la soumission si des champs requis sont vides
            if (!valid) {
                event.preventDefault();
            }
        });
    });
});
