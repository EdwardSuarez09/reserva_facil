// footer-dinamico.js - Manejo dinámico del footer por restaurante

function actualizarFooterConDatos(restauranteInfo) {
    if (!restauranteInfo) {
        return;
    }

    const setLabeledText = (id, label, value) => {
        const element = document.getElementById(id);
        if (!element) return;

        const strong = document.createElement('strong');
        strong.textContent = `${label}: `;
        element.replaceChildren(strong, document.createTextNode(value || 'No disponible'));
    };

    setLabeledText('footer-direccion', 'Dirección', restauranteInfo.direccion);
    setLabeledText(
        'footer-horario',
        'Horario',
        restauranteInfo.horario || 'Consultar horarios'
    );

    const contacto = [restauranteInfo.telefono, restauranteInfo.correo]
        .filter(Boolean)
        .join(' | ');
    setLabeledText('footer-contacto', 'Contacto', contacto);

    const footerWeb = document.getElementById('footer-web');
    if (footerWeb) {
        const strong = document.createElement('strong');
        strong.textContent = 'Web: ';
        footerWeb.replaceChildren(strong);

        try {
            const website = String(restauranteInfo.web || '').trim();
            const url = new URL(/^https?:\/\//i.test(website) ? website : `https://${website}`);
            if (['http:', 'https:'].includes(url.protocol)) {
                const link = document.createElement('a');
                link.href = url.href;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = url.hostname.replace(/^www\./i, '');
                link.title = url.href;
                link.setAttribute('aria-label', `Visitar sitio web de ${url.hostname}`);
                footerWeb.appendChild(link);
            } else {
                footerWeb.appendChild(document.createTextNode('No disponible'));
            }
        } catch {
            footerWeb.appendChild(document.createTextNode('No disponible'));
        }
    }

    const footerSocialWeb = document.getElementById('footer-social-web');
    if (footerSocialWeb) {
        try {
            const website = String(restauranteInfo.web || '').trim();
            const url = new URL(/^https?:\/\//i.test(website) ? website : `https://${website}`);
            footerSocialWeb.href = ['http:', 'https:'].includes(url.protocol) ? url.href : '#';
        } catch {
            footerSocialWeb.href = '#';
        }
    }

    const footerRedes = document.getElementById('footer-redes-value');
    if (footerRedes) {
        footerRedes.textContent = restauranteInfo.redes || 'No disponible';
    }
}

// Función que se ejecuta cuando los datos del restaurante están disponibles
function onRestauranteInfoLoaded() {
    if (window.restauranteInfo) {
        actualizarFooterConDatos(window.restauranteInfo);
    } else {
        // Intentar nuevamente después de un breve delay
        setTimeout(() => {
            if (window.restauranteInfo) {
                actualizarFooterConDatos(window.restauranteInfo);
            }
        }, 1000);
    }
}

// Hacer las funciones disponibles globalmente
window.actualizarFooterConDatos = actualizarFooterConDatos;
window.onRestauranteInfoLoaded = onRestauranteInfoLoaded;

// Observer para detectar cuando se crean elementos script (cuando se carga la info del restaurante)
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'SCRIPT') {
                // Si se agregó un script, verificar si contiene restauranteInfo
                setTimeout(() => {
                    if (window.restauranteInfo) {
                        onRestauranteInfoLoaded();
                    }
                }, 100);
            }
        });
    });
});

// Iniciar el observer cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // También intentar actualizar inmediatamente por si los datos ya están disponibles
    setTimeout(onRestauranteInfoLoaded, 500);
    setTimeout(onRestauranteInfoLoaded, 1500);
    setTimeout(onRestauranteInfoLoaded, 3000);
}); 