/**
 * Script principal pour le site
 */
document.addEventListener('DOMContentLoaded', () => {
    // Initialisation des composants
    initAlerts();
    initForms();
    initSidebar();
    initDropdowns();
    initPasswordStrength();
    initConfirmActions();
  });
  
  /**
   * Gestion des alertes avec auto-fermeture
   */
  function initAlerts() {
    // Sélectionner toutes les alertes
    const alerts = document.querySelectorAll('.alert');
    
    // Ajouter un timer pour les fermer automatiquement après 5 secondes
    alerts.forEach(alert => {
      // Ajouter le bouton de fermeture s'il n'existe pas
      if (!alert.querySelector('.close-alert')) {
        const closeButton = document.createElement('button');
        closeButton.className = 'close-alert ml-auto pl-3 text-gray-400 hover:text-gray-900 focus:outline-none';
        closeButton.innerHTML = '<span class="sr-only">Fermer</span><i class="fas fa-times"></i>';
        alert.appendChild(closeButton);
        
        // Ajouter l'événement de fermeture
        closeButton.addEventListener('click', () => closeAlert(alert));
      }
      
      // Auto-fermeture après délai
      setTimeout(() => closeAlert(alert), 5000);
    });
  }
  
  /**
   * Fermer une alerte avec animation
   * @param {HTMLElement} alert - L'élément d'alerte à fermer
   */
  function closeAlert(alert) {
    // Animation de fermeture
    alert.style.transition = 'opacity 300ms, transform 300ms';
    alert.style.opacity = 0;
    alert.style.transform = 'translateY(-10px)';
    
    // Supprimer l'alerte après l'animation
    setTimeout(() => {
      if (alert.parentNode) {
        alert.parentNode.removeChild(alert);
      }
    }, 300);
  }
  
  /**
   * Initialisation et validation des formulaires
   */
  function initForms() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
      // Validation à la soumission
      form.addEventListener('submit', event => {
        let valid = validateForm(form);
        
        if (!valid) {
          event.preventDefault();
        }
      });
      
      // Validation en temps réel des champs
      const inputs = form.querySelectorAll('input, select, textarea');
      inputs.forEach(input => {
        input.addEventListener('blur', () => {
          validateInput(input);
        });
        
        // Pour les champs de type password, vérifier la force à chaque saisie
        if (input.type === 'password' && input.id === 'password') {
          input.addEventListener('input', () => {
            checkPasswordStrength(input);
          });
        }
      });
    });
  }
  
  /**
   * Valider un formulaire complet
   * @param {HTMLFormElement} form - Le formulaire à valider
   * @returns {boolean} - True si valide, False sinon
   */
  function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let valid = true;
    
    requiredFields.forEach(field => {
      if (!validateInput(field)) {
        valid = false;
      }
    });
    
    return valid;
  }
  
  /**
   * Valider un champ de formulaire
   * @param {HTMLInputElement} input - Le champ à valider
   * @returns {boolean} - True si valide, False sinon
   */
  function validateInput(input) {
    let valid = true;
    const value = input.value.trim();
    
    // Supprimer le message d'erreur existant
    const existingError = input.parentNode.querySelector('.error-message');
    if (existingError) {
      existingError.remove();
    }
    
    // Vérifier si le champ est vide
    if (input.hasAttribute('required') && value === '') {
      valid = false;
      showInputError(input, 'Ce champ est requis');
    } 
    // Validation spécifique selon le type
    else if (value !== '') {
      if (input.type === 'email' && !isValidEmail(value)) {
        valid = false;
        showInputError(input, 'Adresse email invalide');
      } else if (input.id === 'password' && input.value.length < 8) {
        valid = false;
        showInputError(input, 'Le mot de passe doit contenir au moins 8 caractères');
      } else if (input.id === 'confirm_password') {
        const password = document.getElementById('password');
        if (password && input.value !== password.value) {
          valid = false;
          showInputError(input, 'Les mots de passe ne correspondent pas');
        }
      }
    }
    
    // Ajouter/supprimer les classes selon la validité
    if (valid) {
      input.classList.remove('border-red-500');
      input.classList.remove('bg-red-50');
      if (value !== '') {
        input.classList.add('border-green-500');
        input.classList.add('bg-green-50');
      }
    } else {
      input.classList.remove('border-green-500');
      input.classList.remove('bg-green-50');
      input.classList.add('border-red-500');
      input.classList.add('bg-red-50');
    }
    
    return valid;
  }
  
  /**
   * Afficher un message d'erreur sous un champ
   * @param {HTMLInputElement} input - Le champ concerné
   * @param {string} message - Le message d'erreur
   */
  function showInputError(input, message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message text-sm text-red-600 mt-1';
    errorDiv.innerText = message;
    
    // Ajouter le message après le champ
    input.parentNode.appendChild(errorDiv);
  }
  
  /**
   * Vérifier si un email est valide
   * @param {string} email - L'email à vérifier
   * @returns {boolean} - True si valide, False sinon
   */
  function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }
  
  /**
   * Gestion du menu latéral responsive
   */
  function initSidebar() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const body = document.body;
    
    if (sidebarToggle && sidebar) {
      sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        
        // Gestion de l'overlay
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
          overlay = document.createElement('div');
          overlay.className = 'sidebar-overlay';
          body.appendChild(overlay);
        }
        
        overlay.classList.toggle('active');
        
        // Fermer le sidebar quand on clique sur l'overlay
        overlay.addEventListener('click', () => {
          sidebar.classList.remove('active');
          overlay.classList.remove('active');
        });
      });
    }
  }
  
  /**
   * Initialisation des menus déroulants
   */
  function initDropdowns() {
    const dropdownButtons = document.querySelectorAll('[data-dropdown]');
    
    dropdownButtons.forEach(button => {
      const target = document.getElementById(button.dataset.dropdown);
      if (target) {
        // Ouvrir/fermer au clic
        button.addEventListener('click', event => {
          event.stopPropagation();
          
          // Fermer les autres dropdowns
          document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            if (menu !== target) {
              menu.classList.remove('active');
              menu.classList.add('hidden');
            }
          });
          
          // Toggle ce dropdown
          target.classList.toggle('hidden');
          setTimeout(() => {
            target.classList.toggle('active');
          }, 10);
        });
      }
    });
    
    // Fermer les dropdowns au clic ailleurs
    document.addEventListener('click', () => {
      document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
        menu.classList.remove('active');
        menu.classList.add('hidden');
      });
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
    strengthText.className = 'text-xs mt-1';
    
    if (password.length === 0) {
      strengthMeter.style.width = '0%';
      strengthText.textContent = '';
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
  
  /**
   * Initialisation des actions avec confirmation
   */
  function initConfirmActions() {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    
    confirmButtons.forEach(button => {
      button.addEventListener('click', event => {
        const message = button.getAttribute('data-confirm');
        if (!confirm(message)) {
          event.preventDefault();
        }
      });
    });
  }