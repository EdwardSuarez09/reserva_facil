// footer-dinamico.js - Manejo dinámico del footer por restaurante

function actualizarFooterConDatos(restauranteInfo) {
    console.log('Actualizando footer con datos:', restauranteInfo);
    
    if (!restauranteInfo) {
        console.log('No hay datos del restaurante para actualizar el footer');
        return;
    }
    
    // Actualizar dirección
    if (restauranteInfo.direccion) {
        const footerDireccion = document.getElementById("footer-direccion");
        if (footerDireccion) {
            footerDireccion.innerHTML = `<strong>Dirección:</strong> ${restauranteInfo.direccion}`;
        }
    }
    
    // Actualizar horario
    if (restauranteInfo.horario) {
        const footerHorario = document.getElementById("footer-horario");
        if (footerHorario) {
            footerHorario.innerHTML = `<strong>Horario:</strong> ${restauranteInfo.horario}`;
        }
    } else {
        const footerHorario = document.getElementById("footer-horario");
        if (footerHorario) {
            footerHorario.innerHTML = `<strong>Horario:</strong> Consultar horarios`;
        }
    }
    
    // Actualizar contacto
    let contactoText = '<strong>Contacto:</strong> ';
    if (restauranteInfo.telefono && restauranteInfo.correo) {
        contactoText += `${restauranteInfo.telefono} | ${restauranteInfo.correo}`;
    } else if (restauranteInfo.telefono) {
        contactoText += restauranteInfo.telefono;
    } else if (restauranteInfo.correo) {
        contactoText += restauranteInfo.correo;
    } else {
        contactoText += 'No disponible';
    }
    
    const footerContacto = document.getElementById("footer-contacto");
    if (footerContacto) {
        footerContacto.innerHTML = contactoText;
    }
    
    // Actualizar web
    if (restauranteInfo.web) {
        const footerWeb = document.getElementById("footer-web");
        const footerSocialWeb = document.getElementById("footer-social-web");
        
        if (footerWeb) {
            footerWeb.innerHTML = `<strong>Web:</strong> <a href="${restauranteInfo.web}" target="_blank">${restauranteInfo.web}</a>`;
        }
        if (footerSocialWeb) {
            footerSocialWeb.href = restauranteInfo.web;
        }
    } else {
        const footerWeb = document.getElementById("footer-web");
        if (footerWeb) {
            footerWeb.innerHTML = `<strong>Web:</strong> No disponible`;
        }
    }
    
    // Actualizar redes sociales
    if (restauranteInfo.redes) {
        const footerRedesValue = document.getElementById("footer-redes-value");
        if (footerRedesValue) {
            footerRedesValue.textContent = restauranteInfo.redes;
        }
    } else {
        const footerRedesValue = document.getElementById("footer-redes-value");
        if (footerRedesValue) {
            footerRedesValue.textContent = 'No disponible';
        }
    }
    
    console.log('✅ Footer actualizado correctamente con datos dinámicos');
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