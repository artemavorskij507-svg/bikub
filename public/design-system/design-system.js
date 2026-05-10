/**
 * Bikube Design System - JavaScript Components
 * Version: 1.0.0
 */

// ============================================
// 1. FORM VALIDATOR
// ============================================

class FormValidator {
  constructor(form) {
    this.form = form;
    this.fields = {};
    this.generalError = form.querySelector('.form-general-error');
    
    this.init();
  }
  
  init() {
    // Collect all fields
    this.form.querySelectorAll('input, select, textarea').forEach(field => {
      this.fields[field.id] = {
        element: field,
        errorEl: document.getElementById(`${field.id}-error`),
        hintEl: document.getElementById(`${field.id}-hint`)
      };
      
      // Real-time validation on blur
      field.addEventListener('blur', () => this.validateField(field));
      
      // Clear error on input
      field.addEventListener('input', () => this.clearFieldError(field));
    });
    
    // Form submission
    this.form.addEventListener('submit', (e) => this.handleSubmit(e));
  }
  
  validateField(field) {
    const fieldData = this.fields[field.id];
    if (!fieldData) return true;
    
    let isValid = true;
    let errorMessage = '';
    
    // Required validation
    if (field.hasAttribute('required') && !field.value.trim()) {
      isValid = false;
      errorMessage = 'Поле обов\'язкове для заповнення';
    }
    
    // Email validation
    if (field.type === 'email' && field.value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(field.value)) {
        isValid = false;
        errorMessage = 'Будь ласка, введіть коректний email';
      }
    }
    
    // Phone validation
    if (field.type === 'tel' && field.value) {
      const phoneRegex = /^\+380\d{9}$/;
      if (!phoneRegex.test(field.value.replace(/\s/g, ''))) {
        isValid = false;
        errorMessage = 'Будь ласка, введіть коректний номер телефону';
      }
    }
    
    // Min length validation
    if (field.minLength && field.value.length < field.minLength) {
      isValid = false;
      errorMessage = `Мінімальна довжина: ${field.minLength} символів`;
    }
    
    // Max length validation
    if (field.maxLength && field.value.length > field.maxLength) {
      isValid = false;
      errorMessage = `Максимальна довжина: ${field.maxLength} символів`;
    }
    
    // Pattern validation
    if (field.pattern && field.value) {
      const regex = new RegExp(field.pattern);
      if (!regex.test(field.value)) {
        isValid = false;
        errorMessage = field.dataset.patternError || 'Невірний формат';
      }
    }
    
    if (!isValid) {
      this.showFieldError(field, errorMessage);
    } else {
      this.clearFieldError(field);
    }
    
    return isValid;
  }
  
  showFieldError(field, message) {
    const fieldData = this.fields[field.id];
    if (!fieldData) return;
    
    // Update field state
    field.setAttribute('aria-invalid', 'true');
    field.classList.add('form-input-error');
    
    // Update error message
    if (fieldData.errorEl) {
      fieldData.errorEl.querySelector('.form-error-text').textContent = message;
      fieldData.errorEl.style.display = 'flex';
    }
    
    // Hide hint
    if (fieldData.hintEl) {
      fieldData.hintEl.style.display = 'none';
    }
    
    // Update aria-describedby
    field.setAttribute('aria-describedby', `${field.id}-error`);
  }
  
  clearFieldError(field) {
    const fieldData = this.fields[field.id];
    if (!fieldData) return;
    
    // Update field state
    field.setAttribute('aria-invalid', 'false');
    field.classList.remove('form-input-error');
    
    // Hide error message
    if (fieldData.errorEl) {
      fieldData.errorEl.style.display = 'none';
    }
    
    // Show hint
    if (fieldData.hintEl) {
      fieldData.hintEl.style.display = 'block';
    }
    
    // Update aria-describedby
    if (fieldData.hintEl) {
      field.setAttribute('aria-describedby', `${field.id}-hint`);
    }
  }
  
  handleSubmit(e) {
    e.preventDefault();
    
    let isValid = true;
    const errors = [];
    
    // Validate all fields
    Object.keys(this.fields).forEach(fieldId => {
      const field = this.fields[fieldId].element;
      if (!this.validateField(field)) {
        isValid = false;
        errors.push(field);
      }
    });
    
    if (!isValid) {
      // Show general error
      this.showGeneralError(errors.length);
      
      // Focus first error field
      if (errors.length > 0) {
        errors[0].focus();
      }
      
      // Announce to screen readers
      this.announce(`Знайдено ${errors.length} помилок у формі`, 'assertive');
      
      return;
    }
    
    // Hide general error
    this.hideGeneralError();
    
    // Submit form
    this.submitForm();
  }
  
  showGeneralError(errorCount) {
    if (this.generalError) {
      this.generalError.querySelector('.form-general-error-text').textContent = 
        `Будь ласка, виправте ${errorCount} помилок нижче.`;
      this.generalError.style.display = 'flex';
    }
  }
  
  hideGeneralError() {
    if (this.generalError) {
      this.generalError.style.display = 'none';
    }
  }
  
  submitForm() {
    const formData = new FormData(this.form);
    const data = Object.fromEntries(formData.entries());
    
    // Show loading state
    const submitBtn = this.form.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
      <span class="btn-spinner" aria-hidden="true"></span>
      <span>Збереження...</span>
    `;
    
    // API call
    fetch(this.form.action || window.location.href, {
      method: this.form.method || 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Show success message
        showToast('Зміни успішно збережено', 'success');
        
        // Announce to screen readers
        announce('Зміни успішно збережено', 'polite');
        
        // Redirect if needed
        if (data.redirect) {
          window.location.href = data.redirect;
        }
      } else {
        // Handle server-side validation errors
        if (data.errors) {
          Object.keys(data.errors).forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
              this.showFieldError(field, data.errors[fieldId][0]);
            }
          });
          
          // Show general error
          this.showGeneralError(Object.keys(data.errors).length);
          
          // Focus first error field
          const firstErrorField = document.getElementById(Object.keys(data.errors)[0]);
          if (firstErrorField) {
            firstErrorField.focus();
          }
        } else {
          // Generic error
          showToast(data.message || 'Помилка при збереженні', 'error');
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Помилка при збереженні', 'error');
    })
    .finally(() => {
      // Reset button
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    });
  }
  
  announce(message, priority = 'polite') {
    const announcement = document.createElement('div');
    announcement.setAttribute('role', priority === 'assertive' ? 'alert' : 'status');
    announcement.setAttribute('aria-live', priority);
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    
    document.body.appendChild(announcement);
    
    setTimeout(() => announcement.remove(), 1000);
  }
}

// ============================================
// 2. CONFIRM DIALOG
// ============================================

class ConfirmDialog {
  constructor() {
    this.modal = document.querySelector('[role="alertdialog"]');
    if (!this.modal) return;
    
    this.titleEl = this.modal.querySelector('#confirm-title');
    this.descriptionEl = this.modal.querySelector('#confirm-description');
    this.warningEl = this.modal.querySelector('.confirm-warning');
    this.cancelBtn = this.modal.querySelector('.confirm-cancel');
    this.proceedBtn = this.modal.querySelector('.confirm-proceed');
    
    this.pendingAction = null;
    this.triggerElement = null;
    
    this.init();
  }
  
  init() {
    // Find all confirm triggers
    document.querySelectorAll('[data-confirm-action]').forEach(trigger => {
      trigger.addEventListener('click', (e) => this.show(e));
    });
    
    // Cancel button
    this.cancelBtn?.addEventListener('click', () => this.hide());
    
    // Proceed button
    this.proceedBtn?.addEventListener('click', () => this.proceed());
    
    // Close on overlay click
    this.modal.addEventListener('click', (e) => {
      if (e.target === this.modal) {
        this.hide();
      }
    });
    
    // Close on Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.modal.getAttribute('aria-hidden') === 'false') {
        this.hide();
      }
    });
  }
  
  show(e) {
    const trigger = e.currentTarget;
    this.triggerElement = trigger;
    
    // Get data from trigger
    const action = trigger.dataset.confirmAction;
    const title = trigger.dataset.confirmTitle;
    const message = trigger.dataset.confirmMessage;
    const warning = trigger.dataset.confirmWarning;
    const buttonText = trigger.dataset.confirmButton;
    
    // Update modal content
    this.titleEl.textContent = title;
    this.descriptionEl.textContent = message;
    this.proceedBtn.textContent = buttonText;
    
    // Show/hide warning
    if (warning) {
      this.warningEl.querySelector('p').textContent = warning;
      this.warningEl.style.display = 'flex';
    } else {
      this.warningEl.style.display = 'none';
    }
    
    // Store pending action
    this.pendingAction = {
      type: action,
      element: trigger
    };
    
    // Show modal
    this.modal.setAttribute('aria-hidden', 'false');
    
    // Focus cancel button
    this.cancelBtn.focus();
    
    // Trap focus
    this.trapFocus();
  }
  
  hide() {
    this.modal.setAttribute('aria-hidden', 'true');
    this.pendingAction = null;
    
    // Return focus to trigger
    if (this.triggerElement) {
      this.triggerElement.focus();
    }
  }
  
  proceed() {
    if (this.pendingAction) {
      // Execute the action
      this.executeAction(this.pendingAction);
      
      // Hide modal
      this.hide();
    }
  }
  
  executeAction(action) {
    switch (action.type) {
      case 'disable-2fa':
        this.disable2FA();
        break;
        
      case 'delete-order':
        this.deleteOrder(action.element);
        break;
        
      case 'logout-sessions':
        this.logoutSessions();
        break;
        
      default:
        console.warn(`Unknown action: ${action.type}`);
    }
  }
  
  disable2FA() {
    // API call to disable 2FA
    fetch('/api/security/2fa/disable', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update UI
        const statusEl = document.querySelector('.two-factor-status');
        if (statusEl) {
          statusEl.textContent = 'Вимкнено';
          statusEl.classList.remove('status-active');
          statusEl.classList.add('status-inactive');
        }
        
        // Show success message
        showToast('Двофакторну автентифікацію вимкнено', 'success');
        
        // Announce to screen readers
        announce('Двофакторну автентифікацію вимкнено');
      } else {
        showToast('Помилка при вимкненні 2FA', 'error');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Помилка при вимкненні 2FA', 'error');
    });
  }
  
  deleteOrder(button) {
    const orderId = button.closest('[data-order-id]')?.dataset.orderId;
    if (!orderId) return;
    
    fetch(`/api/orders/${orderId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Remove order from DOM
        const orderCard = button.closest('.order-card');
        if (orderCard) {
          orderCard.remove();
        }
        
        // Show success message
        showToast('Замовлення видалено', 'success');
        
        // Announce to screen readers
        announce('Замовлення видалено');
      } else {
        showToast('Помилка при видаленні замовлення', 'error');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Помилка при видаленні замовлення', 'error');
    });
  }
  
  logoutSessions() {
    fetch('/api/security/sessions/logout-all', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update UI
        const sessionsList = document.querySelector('.sessions-list');
        if (sessionsList) {
          sessionsList.innerHTML = '<li class="session-item">Поточна сесія</li>';
        }
        
        // Show success message
        showToast('Вихід з інших пристроїв виконано', 'success');
        
        // Announce to screen readers
        announce('Вихід з інших пристроїв виконано');
      } else {
        showToast('Помилка при виході з сесій', 'error');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Помилка при виході з сесій', 'error');
    });
  }
  
  trapFocus() {
    const focusableElements = this.modal.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    this.modal.addEventListener('keydown', (e) => {
      if (e.key === 'Tab') {
        if (e.shiftKey && document.activeElement === firstElement) {
          e.preventDefault();
          lastElement.focus();
        } else if (!e.shiftKey && document.activeElement === lastElement) {
          e.preventDefault();
          firstElement.focus();
        }
      }
    });
  }
}

// ============================================
// 3. CLIENT SWITCHER
// ============================================

class ClientSwitcher {
  constructor() {
    this.switcher = document.querySelector('.client-context-switch');
    this.dropdown = document.getElementById('client-switcher-dropdown');
    
    if (!this.switcher || !this.dropdown) return;
    
    this.searchInput = document.getElementById('client-search');
    this.items = document.querySelectorAll('.client-switcher-item');
    
    this.pendingClient = null;
    
    this.init();
  }
  
  init() {
    // Toggle dropdown
    this.switcher.addEventListener('click', () => this.toggleDropdown());
    
    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!this.switcher.contains(e.target) && !this.dropdown.contains(e.target)) {
        this.closeDropdown();
      }
    });
    
    // Keyboard navigation
    this.dropdown.addEventListener('keydown', (e) => this.handleKeyboard(e));
    
    // Search
    this.searchInput?.addEventListener('input', (e) => this.filterClients(e.target.value));
    
    // Item selection
    this.items.forEach(item => {
      item.addEventListener('click', () => this.selectClient(item));
    });
  }
  
  toggleDropdown() {
    const isExpanded = this.switcher.getAttribute('aria-expanded') === 'true';
    this.switcher.setAttribute('aria-expanded', !isExpanded);
    this.dropdown.setAttribute('aria-hidden', isExpanded);
    
    if (!isExpanded) {
      this.searchInput?.focus();
    }
  }
  
  closeDropdown() {
    this.switcher.setAttribute('aria-expanded', 'false');
    this.dropdown.setAttribute('aria-hidden', 'true');
  }
  
  selectClient(item) {
    const clientId = item.querySelector('.client-switcher-item-id')?.textContent;
    const clientName = item.querySelector('.client-switcher-item-name')?.textContent;
    
    // Check if there are unsaved changes
    if (this.hasUnsavedChanges()) {
      this.pendingClient = { id: clientId, name: clientName };
      this.showConfirmModal(clientName);
    } else {
      this.switchClient(clientId, clientName);
    }
  }
  
  showConfirmModal(newClientName) {
    const currentClient = this.switcher.querySelector('.client-context-name')?.textContent;
    
    const descriptionEl = document.querySelector('#client-switch-description');
    if (descriptionEl) {
      descriptionEl.innerHTML = `
        Ви переключаєтесь з <strong>${currentClient}</strong> на 
        <strong>${newClientName}</strong>. Усі незбережені зміни буде втрачено.
      `;
    }
    
    const confirmModal = document.querySelector('[role="alertdialog"]');
    if (confirmModal) {
      confirmModal.setAttribute('aria-hidden', 'false');
      confirmModal.querySelector('.modal-cancel')?.focus();
    }
  }
  
  closeConfirmModal() {
    const confirmModal = document.querySelector('[role="alertdialog"]');
    if (confirmModal) {
      confirmModal.setAttribute('aria-hidden', 'true');
    }
    this.pendingClient = null;
    this.switcher.focus();
  }
  
  confirmSwitch() {
    if (this.pendingClient) {
      this.switchClient(this.pendingClient.id, this.pendingClient.name);
      this.closeConfirmModal();
    }
  }
  
  switchClient(clientId, clientName) {
    // Update UI
    const nameEl = this.switcher.querySelector('.client-context-name');
    if (nameEl) {
      nameEl.textContent = clientName;
    }
    
    const idEl = document.querySelector('.client-context-id');
    if (idEl) {
      idEl.textContent = `ID: ${clientId}`;
    }
    
    // Update dropdown
    this.items.forEach(item => {
      const itemId = item.querySelector('.client-switcher-item-id')?.textContent;
      if (itemId === clientId) {
        item.classList.add('client-switcher-item-current');
        item.setAttribute('aria-selected', 'true');
      } else {
        item.classList.remove('client-switcher-item-current');
        item.setAttribute('aria-selected', 'false');
      }
    });
    
    this.closeDropdown();
    
    // Announce to screen readers
    announce(`Переключено на клієнта ${clientName}`);
  }
  
  hasUnsavedChanges() {
    // Check for unsaved form data
    const forms = document.querySelectorAll('form');
    for (const form of forms) {
      if (form.dataset.dirty === 'true') {
        return true;
      }
    }
    return false;
  }
  
  filterClients(query) {
    const lowerQuery = query.toLowerCase();
    
    this.items.forEach(item => {
      const name = item.querySelector('.client-switcher-item-name')?.textContent.toLowerCase() || '';
      const id = item.querySelector('.client-switcher-item-id')?.textContent.toLowerCase() || '';
      
      if (name.includes(lowerQuery) || id.includes(lowerQuery)) {
        item.style.display = 'flex';
      } else {
        item.style.display = 'none';
      }
    });
  }
  
  handleKeyboard(e) {
    const visibleItems = Array.from(this.items).filter(item => item.style.display !== 'none');
    const currentIndex = visibleItems.findIndex(item => item === document.activeElement);
    
    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        if (currentIndex < visibleItems.length - 1) {
          visibleItems[currentIndex + 1].focus();
        }
        break;
        
      case 'ArrowUp':
        e.preventDefault();
        if (currentIndex > 0) {
          visibleItems[currentIndex - 1].focus();
        }
        break;
        
      case 'Enter':
      case ' ':
        e.preventDefault();
        if (document.activeElement.classList.contains('client-switcher-item')) {
          this.selectClient(document.activeElement);
        }
        break;
        
      case 'Escape':
        this.closeDropdown();
        this.switcher.focus();
        break;
    }
  }
}

// ============================================
// 4. TOAST MANAGER
// ============================================

class ToastManager {
  constructor() {
    this.container = document.querySelector('.toast-container');
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.className = 'toast-container';
      this.container.setAttribute('aria-live', 'polite');
      this.container.setAttribute('aria-label', 'Сповіщення');
      document.body.appendChild(this.container);
    }
  }
  
  show(message, type = 'info', duration = 5000) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'alert');
    
    const icon = type === 'success' ? 'check-circle' : 'error';
    
    toast.innerHTML = `
      <svg class="toast-icon" aria-hidden="true">
        <use href="#icon-${icon}"></use>
      </svg>
      <div class="toast-content">
        <p class="toast-text">${message}</p>
      </div>
      <button type="button" class="toast-dismiss" aria-label="Закрити">
        <svg aria-hidden="true">
          <use href="#icon-close"></use>
        </svg>
      </button>
    `;
    
    this.container.appendChild(toast);
    
    // Dismiss button
    const dismissBtn = toast.querySelector('.toast-dismiss');
    dismissBtn.addEventListener('click', () => this.dismiss(toast));
    
    // Auto dismiss
    if (duration > 0) {
      setTimeout(() => this.dismiss(toast), duration);
    }
    
    return toast;
  }
  
  dismiss(toast) {
    toast.classList.add('closing');
    setTimeout(() => toast.remove(), 300);
  }
}

// ============================================
// 5. UTILITY FUNCTIONS
// ============================================

function showToast(message, type = 'info', duration = 5000) {
  const manager = new ToastManager();
  return manager.show(message, type, duration);
}

function announce(message, priority = 'polite') {
  const announcement = document.createElement('div');
  announcement.setAttribute('role', priority === 'assertive' ? 'alert' : 'status');
  announcement.setAttribute('aria-live', priority);
  announcement.setAttribute('aria-atomic', 'true');
  announcement.className = 'sr-only';
  announcement.textContent = message;
  
  document.body.appendChild(announcement);
  
  setTimeout(() => announcement.remove(), 1000);
}

// ============================================
// 6. INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
  // Initialize all forms
  document.querySelectorAll('form[novalidate]').forEach(form => {
    new FormValidator(form);
  });
  
  // Initialize confirmation dialogs
  new ConfirmDialog();
  
  // Initialize client switcher
  new ClientSwitcher();
  
  // Initialize toast manager
  window.toastManager = new ToastManager();
  
  // Mark forms as dirty on input
  document.querySelectorAll('form').forEach(form => {
    form.querySelectorAll('input, select, textarea').forEach(field => {
      field.addEventListener('input', () => {
        form.dataset.dirty = 'true';
      });
    });
  });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    FormValidator,
    ConfirmDialog,
    ClientSwitcher,
    ToastManager,
    showToast,
    announce
  };
}
