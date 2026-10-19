/**
 * Manejador de Modales - ReservaFácil
 * Previene aperturas automáticas no deseadas y maneja errores
 */

class ModalManager {
    constructor() {
        this.activeModals = new Set();
        this.setupGlobalListeners();
    }

    setupGlobalListeners() {
        // ESC key para cerrar modales
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });

        // Prevenir apertura automática en errores
        window.addEventListener('error', (e) => {
            console.warn('Error detectado, verificando modales:', e.message);
            // Solo cerrar si no es una acción intencional del usuario
            if (!e.message.toLowerCase().includes('usuario') && 
                !e.message.toLowerCase().includes('click')) {
                this.closeAllModals();
            }
        });

        // Log cuando el DOM está listo
        document.addEventListener('DOMContentLoaded', () => {
            console.log('ModalManager inicializado');
            this.closeAllModals(); // Asegurar que todos los modales estén cerrados
        });
    }

    openModal(modalId, config = {}) {
        console.log('Abriendo modal:', modalId);
        
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error('Modal no encontrado:', modalId);
            return false;
        }

        // Verificar si ya está abierto
        if (this.activeModals.has(modalId)) {
            console.log('Modal ya está abierto:', modalId);
            return false;
        }

        // Cerrar otros modales si es necesario
        if (config.closeOthers !== false) {
            this.closeAllModals();
        }

        // Abrir modal
        modal.style.display = 'block';
        this.activeModals.add(modalId);

        // Configurar evento de clic fuera del modal
        setTimeout(() => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modalId);
                }
            });
        }, 100);

        // Scroll al top
        if (config.scrollTop !== false) {
            setTimeout(() => {
                modal.scrollTop = 0;
                const content = modal.querySelector('.modal-content');
                if (content) content.scrollTop = 0;
            }, 50);
        }

        return true;
    }

    closeModal(modalId) {
        console.log('Cerrando modal:', modalId);
        
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error('Modal no encontrado:', modalId);
            return false;
        }

        modal.style.display = 'none';
        this.activeModals.delete(modalId);

        // Limpiar formularios si existen
        const forms = modal.querySelectorAll('form');
        forms.forEach(form => {
            if (form.reset) form.reset();
        });

        return true;
    }

    closeAllModals() {
        console.log('Cerrando todos los modales');
        this.activeModals.forEach(modalId => {
            this.closeModal(modalId);
        });
    }

    isModalOpen(modalId) {
        return this.activeModals.has(modalId);
    }

    hasOpenModals() {
        return this.activeModals.size > 0;
    }
}

// Instancia global
window.modalManager = new ModalManager();

// Funciones globales para compatibilidad con código existente
window.abrirModal = function(modalId, config) {
    return window.modalManager.openModal(modalId, config);
};

window.cerrarModal = function(modalId) {
    // Si no se especifica ID, cerrar todos
    if (!modalId) {
        return window.modalManager.closeAllModals();
    }
    return window.modalManager.closeModal(modalId);
};

console.log('Modal Manager cargado exitosamente'); 