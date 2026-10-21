<?php
// Obtener el nombre del restaurante desde la URL
$nombreRestaurante = $_GET['restaurante'] ?? '';
if (!$nombreRestaurante) {
    die("Error: Nombre de restaurante no especificado.");
}

// Conectar a la base de datos y obtener datos del restaurante
include __DIR__ . '/../../config/conexion.php';
$sql = "SELECT nombre, direccion, horario, telefono, correo, web, redes_sociales FROM restaurantes WHERE nombre = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nombreRestaurante);
$stmt->execute();
$resultado = $stmt->get_result();

$restauranteData = null;
if ($resultado->num_rows > 0) {
    $restauranteData = $resultado->fetch_assoc();
}
$stmt->close();
$conn->close();

// Función para obtener datos del footer
function getFooterData($data, $campo, $default = 'No disponible') {
    return (!empty($data[$campo])) ? htmlspecialchars($data[$campo]) : $default;
}

function getFooterWebsite($website): string {
    $website = trim((string) $website);
    if ($website === '') {
        return 'No disponible';
    }

    if (!preg_match('/^https?:\/\//i', $website)) {
        $website = 'https://' . $website;
    }

    $parts = parse_url($website);
    if (
        $parts === false
        || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        || empty($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
    ) {
        return 'No disponible';
    }

    $label = preg_replace('/^www\./i', '', $parts['host']);
    return '<a href="' . htmlspecialchars($website, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '" target="_blank" rel="noopener noreferrer" title="'
        . htmlspecialchars($website, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '" aria-label="Visitar sitio web de '
        . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '">' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a>';
}

// Preparar datos del footer
if ($restauranteData) {
    $footerDireccion = getFooterData($restauranteData, 'direccion');
    $footerHorario = getFooterData($restauranteData, 'horario');
    
    $footerContacto = '';
    if (!empty($restauranteData['telefono']) && !empty($restauranteData['correo'])) {
        $footerContacto = htmlspecialchars($restauranteData['telefono']) . ' | ' . htmlspecialchars($restauranteData['correo']);
    } elseif (!empty($restauranteData['telefono'])) {
        $footerContacto = htmlspecialchars($restauranteData['telefono']);
    } elseif (!empty($restauranteData['correo'])) {
        $footerContacto = htmlspecialchars($restauranteData['correo']);
    } else {
        $footerContacto = 'No disponible';
    }
    
    $footerWeb = getFooterWebsite($restauranteData['web'] ?? '');
    
    $footerRedes = getFooterData($restauranteData, 'redes_sociales');
} else {
    $footerDireccion = $footerHorario = $footerContacto = $footerRedes = 'No disponible';
    $footerWeb = 'No disponible';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detalles del Restaurante</title>
  <link rel="stylesheet" href="../../../public/assets/css/estilos_vista_restaurante.css?v=20261021-restaurant-2">
  <style>
  </style>
</head>
<body class="restaurant-detail-page">
  <div class="main-container">
    <nav class="sidebar">
      <div id="logo-restaurante" class="logo-nav"></div>
      <a href="#inicio" class="nav-link active">Inicio</a>
      <a href="#menu" class="nav-link">Especialidades</a>
      <a href="#" id="menu-completo-link" class="nav-link">Menú Completo</a>
      <a href="#reservas" class="nav-link">Reservas</a>
      <a href="../../../public/index_cliente.html" class="nav-link">Volver al catálogo</a>
    </nav>

    <div class="contenido-principal">
      <section id="inicio">
        <div id="info-restaurante">Cargando información...</div>
      </section>

      <section id="menu">
        <h2>Menú del Restaurante</h2>
        <div id="menu-restaurante">Cargando menú...</div>
      </section>

      <section id="reservas">
        <h2>Reservas</h2>
        
        <!-- Botón para hacer reserva -->
        <div class="reservation-cta">
          <h3>¿Quieres hacer una reserva?</h3>
          <p>Completa el formulario para reservar tu mesa de forma fácil y rápida.</p>
          <button class="reservation-cta-button" type="button" onclick="irAHacerReserva()">
            Hacer reserva ahora
          </button>
        </div>
        
        <div id="mesas-galeria">Cargando mesas disponibles...</div>
        <div id="platos-reservas">Cargando platos especiales...</div>
      </section>
    </div>
  </div>

  <!-- Footer directo en HTML -->
  <footer class="footer-restaurante" id="main-footer">
    <div class="footer-contenido">
      <div class="footer-main">
        <h1 class="footer-nombre" id="footer-nombre-dinamico"><?php echo htmlspecialchars($nombreRestaurante); ?></h1>
        <div class="footer-descripcion">El mejor ambiente gastronómico</div>
        <div class="footer-info-detalle">
          <div class="footer-direccion" id="footer-direccion"><strong>Dirección:</strong> <?php echo $footerDireccion; ?></div>
          <div class="footer-horario" id="footer-horario"><strong>Horario:</strong> <?php echo $footerHorario; ?></div>
          <div class="footer-telefonos" id="footer-contacto"><strong>Contacto:</strong> <?php echo $footerContacto; ?></div>
          <div class="footer-web" id="footer-web"><strong>Web:</strong> <?php echo $footerWeb; ?></div>
        </div>
      </div>
      
      <nav class="footer-nav">
        <a href="#inicio" class="footer-nav-link">INICIO</a>
        <a href="#menu" class="footer-nav-link">MENÚ</a>
        <a href="#reservas" class="footer-nav-link">RESERVAS</a>
        <a href="../../../public/index_cliente.html" class="footer-nav-link">CATÁLOGO</a>
      </nav>
      
      <div class="footer-bottom">
        <div class="footer-copyright">
          <span>&copy; 2025 <span id="footer-copyright-nombre"><?php echo htmlspecialchars($nombreRestaurante); ?></span> - Todos los derechos reservados</span>
          <span class="footer-credits">Diseño web por <strong>ReservaFácil</strong></span>
        </div>
        
        <div class="footer-redes" id="footer-redes">
          <span><strong>Redes sociales:</strong> <span id="footer-redes-value"><?php echo $footerRedes; ?></span></span>
        </div>
      </div>
      
    </div>
  </footer>

  <script>
    // Función global para redirección al menú completo
    function irAMenuCompleto() {
      const params = new URLSearchParams(window.location.search);
      const nombre = params.get("restaurante");
      
      if (nombre) {
        const menuUrl = `../../../public/html/menu_restaurante.html?restaurante=${encodeURIComponent(nombre)}`;
        console.log('Redirigiendo a menú completo:', menuUrl);
        window.location.href = menuUrl;
      } else {
        alert('Error: No se puede acceder al menú completo.');
      }
    }

    // Función global para redirección a hacer reserva
    function irAHacerReserva() {
      const params = new URLSearchParams(window.location.search);
      const nombre = params.get("restaurante");
      
      if (nombre) {
        const reservaUrl = `../../../public/html/hacer_reserva.html?restaurante=${encodeURIComponent(nombre)}`;
        console.log('Redirigiendo a hacer reserva:', reservaUrl);
        window.location.href = reservaUrl;
      } else {
        alert('Error: No se puede acceder al sistema de reservas.');
      }
    }

    function crearContenidoWeb(website) {
      const contenido = document.createDocumentFragment();
      const label = document.createElement('strong');
      label.textContent = 'Web:';
      contenido.appendChild(label);

      try {
        const valor = String(website || '').trim();
        const url = new URL(/^https?:\/\//i.test(valor) ? valor : `https://${valor}`);
        if (!['http:', 'https:'].includes(url.protocol) || !url.hostname) {
          throw new TypeError('URL no válida');
        }

        const link = document.createElement('a');
        link.href = url.href;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = url.hostname.replace(/^www\./i, '');
        link.title = url.href;
        link.setAttribute('aria-label', `Visitar sitio web de ${url.hostname}`);
        contenido.appendChild(link);
      } catch {
        contenido.appendChild(document.createTextNode('No disponible'));
      }

      return contenido;
    }

    // Función global para actualizar el footer desde los datos del restaurante
    window.actualizarFooterDesdeRestaurante = function() {
      if (!window.restauranteInfo) {
        console.log('❌ window.restauranteInfo no disponible');
        return false;
      }
      
      const info = window.restauranteInfo;
      console.log('Actualizando footer con datos globales:', info);
      
      // Verificar que los elementos existan
      const footerDireccion = document.getElementById('footer-direccion');
      const footerHorario = document.getElementById('footer-horario');
      const footerContacto = document.getElementById('footer-contacto');
      const footerWeb = document.getElementById('footer-web');
      const footerRedes = document.getElementById('footer-redes-value');
      
      if (!footerDireccion) {
        console.log('❌ Elementos del footer no encontrados');
        return false;
      }
      
      console.log('✅ Elementos del footer encontrados, actualizando...');
      
      // Actualizar dirección
      if (info.direccion) {
        footerDireccion.innerHTML = '<strong>Dirección:</strong> ' + info.direccion;
        console.log('✅ Dirección actualizada:', info.direccion);
      }
      
      // Actualizar horario
      if (info.horario) {
        footerHorario.innerHTML = '<strong>Horario:</strong> ' + info.horario;
        console.log('✅ Horario actualizado:', info.horario);
      }
      
      // Actualizar contacto
      let contacto = '<strong>Contacto:</strong> ';
      if (info.telefono && info.correo) {
        contacto += info.telefono + ' | ' + info.correo;
      } else if (info.telefono) {
        contacto += info.telefono;
      } else if (info.correo) {
        contacto += info.correo;
      } else {
        contacto += 'No disponible';
      }
      footerContacto.innerHTML = contacto;
      console.log('✅ Contacto actualizado:', contacto);
      
      // Actualizar web
      if (footerWeb) {
        footerWeb.replaceChildren(crearContenidoWeb(info.web));
      }
      
      // Actualizar redes sociales
      if (footerRedes) {
        if (info.redes && info.redes.trim() !== '') {
          footerRedes.textContent = info.redes;
          console.log('✅ Redes actualizadas:', info.redes);
        } else {
          footerRedes.textContent = 'No disponible';
          console.log('⚠️ Redes no disponibles');
        }
      }
      
      console.log('Footer actualizado desde la función global.');
      return true;
    };

    // Función para actualizar el footer con datos dinámicos
    function actualizarFooterDinamico() {
      console.log('Iniciando actualización del footer...');
      console.log('Verificando window.restauranteInfo:', window.restauranteInfo);
      
      if (window.restauranteInfo) {
        return window.actualizarFooterDesdeRestaurante();
      } else {
        console.log('❌ window.restauranteInfo no está disponible aún');
        return false;
      }
    }
    
    // Hacer la función globalmente disponible
    window.actualizarFooterDinamico = actualizarFooterDinamico;

    document.addEventListener("DOMContentLoaded", () => {
      const params = new URLSearchParams(window.location.search);
      const nombre = params.get("restaurante");
  
      if (!nombre) {
        document.getElementById("info-restaurante").innerHTML = "<p style='color:red;'>Restaurante no especificado.</p>";
        return;
      }

      // Actualizar el nombre del restaurante en el footer (nombre básico inicial)
      document.getElementById("footer-nombre-dinamico").textContent = nombre;
      document.getElementById("footer-copyright-nombre").textContent = nombre;

      // Configurar enlace al menú completo
      const menuCompletoLink = document.getElementById("menu-completo-link");
      if (menuCompletoLink) {
        const menuUrl = `menu_restaurante.html?restaurante=${encodeURIComponent(nombre)}`;
        menuCompletoLink.href = menuUrl;
        console.log('Enlace menú completo configurado:', menuUrl);
      }

      // Proteger el footer de ser removido
      const footer = document.querySelector('.footer-restaurante');
      if (footer) {
        footer.style.cssText += 'display: block !important; visibility: visible !important; opacity: 1 !important;';
        
        // Observer para detectar si el footer es removido
        const observer = new MutationObserver((mutations) => {
          mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
              const footerExists = document.querySelector('.footer-restaurante');
              if (!footerExists) {
                console.warn('Footer fue removido, restaurando...');
                document.body.appendChild(footer);
              }
            }
          });
        });
        
        observer.observe(document.body, {
          childList: true,
          subtree: true
        });
      }
  
      // Cargar la información principal del restaurante
      fetch(`modulos.php?restaurante=${encodeURIComponent(nombre)}`)
        .then(res => res.text())
        .then(html => {
          document.getElementById("info-restaurante").innerHTML = html;
          const datosRestaurante = document.getElementById('restaurante-info-data');
          if (datosRestaurante) {
            window.restauranteInfo = JSON.parse(datosRestaurante.textContent);
            window.actualizarFooterConDatos?.(window.restauranteInfo);
          }
  
          const logo = document.querySelector("#info-restaurante img");
          if (logo) {
            const logoClon = logo.cloneNode(true);
            document.getElementById("logo-restaurante").appendChild(logoClon);
          }
          
          // Observer para detectar cuando se agregue el script con los datos del restaurante
          const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
              mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'SCRIPT') {
                  // Si se agregó un script, verificar si contiene restauranteInfo
                  setTimeout(() => {
                    if (window.restauranteInfo) {
                      console.log('Script detectado. Actualizando footer con:', window.restauranteInfo);
                      actualizarFooterConDatos(window.restauranteInfo);
                      observer.disconnect(); // Desconectar el observer una vez que encontramos los datos
                    }
                  }, 100);
                }
              });
            });
          });
          
          // Observar cambios en info-restaurante
          observer.observe(document.getElementById("info-restaurante"), {
            childList: true,
            subtree: true
          });
          
          // También intentar con timeout como respaldo
          setTimeout(() => {
            if (window.restauranteInfo) {
              console.log('Timeout: actualizando footer con:', window.restauranteInfo);
              actualizarFooterConDatos(window.restauranteInfo);
            } else {
              console.log('❌ Timeout: Datos aún no disponibles');
            }
          }, 2000);
        })
        .catch(error => {
          console.error("Error al cargar información del restaurante:", error);
        });
  
      // Función para actualizar footer con los datos del restaurante
      function actualizarFooterConDatos(info) {
        console.log('Actualizando footer con:', info);
        
        // Actualizar dirección
        if (info.direccion) {
          const footerDireccion = document.getElementById("footer-direccion");
          if (footerDireccion) {
            footerDireccion.innerHTML = `<strong>Dirección:</strong> ${info.direccion}`;
            console.log('✅ Dirección:', info.direccion);
          }
        }
        
        // Actualizar horario
        if (info.horario) {
          const footerHorario = document.getElementById("footer-horario");
          if (footerHorario) {
            footerHorario.innerHTML = `<strong>Horario:</strong> ${info.horario}`;
            console.log('✅ Horario:', info.horario);
          }
        }
        
        // Actualizar contacto
        let contactoText = '<strong>Contacto:</strong> ';
        if (info.telefono && info.correo) {
          contactoText += `${info.telefono} | ${info.correo}`;
        } else if (info.telefono) {
          contactoText += info.telefono;
        } else if (info.correo) {
          contactoText += info.correo;
        } else {
          contactoText += 'No disponible';
        }
        
        const footerContacto = document.getElementById("footer-contacto");
        if (footerContacto) {
          footerContacto.innerHTML = contactoText;
          console.log('✅ Contacto:', contactoText);
        }
        
        // Actualizar web
        const footerWeb = document.getElementById("footer-web");
        if (footerWeb) {
          footerWeb.replaceChildren(crearContenidoWeb(info.web));
        }
        
        // Actualizar redes sociales
        if (info.redes && info.redes.trim() !== '') {
          const footerRedesValue = document.getElementById("footer-redes-value");
          if (footerRedesValue) {
            footerRedesValue.textContent = info.redes;
            console.log('✅ Redes:', info.redes);
          }
        } else {
          const footerRedesValue = document.getElementById("footer-redes-value");
          if (footerRedesValue) {
            footerRedesValue.textContent = 'No disponible';
            console.log('⚠️ Redes no disponibles');
          }
        }
        
        console.log('Footer actualizado completamente.');
      }
  
      // Cargar menú
      fetch(`menu.php?restaurante=${encodeURIComponent(nombre)}`)
        .then(res => res.text())
        .then(html => document.getElementById("menu-restaurante").innerHTML = html)
        .catch(error => {
          console.error("Error al cargar el menú:", error);
          document.getElementById("menu-restaurante").innerHTML = "<p>Error al cargar el menú</p>";
        });
  
      // Cargar galería de mesas
      fetch(`../mesas/listar_restaurante.php?restaurante=${encodeURIComponent(nombre)}`)
        .then(res => res.text())
        .then(html => document.getElementById("mesas-galeria").innerHTML = html)
        .catch(error => {
          console.error("Error al cargar las mesas:", error);
          document.getElementById("mesas-galeria").innerHTML = "<p>Error al cargar las mesas</p>";
        });

      // Cargar platos especiales para reservas
      fetch(`../platos/listar_para_reserva.php?restaurante=${encodeURIComponent(nombre)}`)
        .then(res => res.text())
        .then(html => document.getElementById("platos-reservas").innerHTML = html)
        .catch(error => {
          console.error("Error al cargar los platos:", error);
          document.getElementById("platos-reservas").innerHTML = "<p>Error al cargar los platos especiales</p>";
        });

      // Verificar footer cada 2 segundos para asegurar que esté visible
      setInterval(() => {
        const footerCheck = document.querySelector('.footer-restaurante');
        if (footerCheck) {
          footerCheck.style.display = 'block';
          footerCheck.style.visibility = 'visible';
          footerCheck.style.opacity = '1';
          
          // Actualizar timestamp
          const timestamp = document.getElementById('footer-timestamp');
          if (timestamp) {
            timestamp.textContent = new Date().toLocaleTimeString();
          }
          
          // Verificar si los datos del restaurante están disponibles y el footer aún muestra "Cargando..."
          if (window.restauranteInfo) {
            const footerDireccion = document.getElementById('footer-direccion');
            if (footerDireccion && footerDireccion.innerHTML.includes('Cargando')) {
              console.log('Datos del restaurante detectados. Actualizando footer desde setInterval...');
              if (window.actualizarFooterDesdeRestaurante) {
                window.actualizarFooterDesdeRestaurante();
              } else {
                actualizarFooterDinamico();
              }
            }
          }
        } else {
          console.error('¡FOOTER PERDIDO! No se encuentra en el DOM');
        }
      }, 2000);

      // Mensaje inicial
      console.log('Footer inicializado correctamente');
      const initialTimestamp = document.getElementById('footer-timestamp');
      if (initialTimestamp) {
        initialTimestamp.textContent = new Date().toLocaleTimeString();
      }

      // FUNCIONALIDAD DE BOTONES Y NAVEGACIÓN
      setupNavigation();
      setupSmoothScrolling();
      
      // Hacer la función disponible globalmente
      window.restauranteActual = nombre;
    });

    // Función para configurar la navegación
    function setupNavigation() {
      // Navegación del sidebar
      document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
          const href = this.getAttribute('href');
          
          // Solo aplicar smooth scroll a enlaces internos
          if (href && href.startsWith('#')) {
            e.preventDefault();
            const targetId = href.substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
              // Actualizar navegación activa
              document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
              this.classList.add('active');
              
              // Scroll suave
              targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
              });
            }
          }
        });
      });

      // Navegación del footer
      document.querySelectorAll('.footer-nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
          const href = this.getAttribute('href');
          
          if (href && href.startsWith('#')) {
            e.preventDefault();
            const targetId = href.substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
              // Actualizar navegación activa en sidebar
              document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
              const correspondingNavLink = document.querySelector(`.nav-link[href="${href}"]`);
              if (correspondingNavLink) {
                correspondingNavLink.classList.add('active');
              }
              
              // Scroll suave
              targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
              });
            }
          }
        });
      });
    }

    // Función para configurar smooth scrolling automático en botones hero
    function setupSmoothScrolling() {
      // Agregar event listener para cuando se carguen los botones del hero
      const observer = new MutationObserver(() => {
        // Botones hero (se cargan dinámicamente)
        document.querySelectorAll('.btn-hero').forEach(btn => {
          if (!btn.classList.contains('scroll-configured')) {
            btn.classList.add('scroll-configured');
            btn.addEventListener('click', function(e) {
              const href = this.getAttribute('href');
              
              if (href && href.startsWith('#')) {
                e.preventDefault();
                const targetId = href.substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                  // Actualizar navegación activa
                  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                  const correspondingNavLink = document.querySelector(`.nav-link[href="${href}"]`);
                  if (correspondingNavLink) {
                    correspondingNavLink.classList.add('active');
                  }
                  
                  // Scroll suave con efecto especial
                  targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                  });
                  
                  // Efecto visual temporal
                  setTimeout(() => {
                    targetElement.style.transition = 'all 0.3s ease';
                    targetElement.style.transform = 'scale(1.01)';
                    setTimeout(() => {
                      targetElement.style.transform = 'scale(1)';
                    }, 300);
                  }, 500);
                }
              }
            });
          }
        });
      });

      // Observar cambios en el DOM para botones que se cargan dinámicamente
      observer.observe(document.body, {
        childList: true,
        subtree: true
      });
    }

    // Función global para scroll suave (usada por botones de mesas y platos)
    window.smoothScrollTo = function(targetSelector) {
      const target = document.querySelector(targetSelector);
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
        
        // Actualizar navegación si es una sección principal
        if (targetSelector.includes('reservas')) {
          document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
          document.querySelector('.nav-link[href="#reservas"]')?.classList.add('active');
        } else if (targetSelector.includes('menu')) {
          document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
          document.querySelector('.nav-link[href="#menu"]')?.classList.add('active');
        }
      }
    };

    // Detectar scroll para actualizar navegación automáticamente
    window.addEventListener('scroll', () => {
      const sections = ['inicio', 'menu', 'reservas'];
      const scrollPosition = window.scrollY + 100;
      
      sections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        if (section) {
          const sectionTop = section.offsetTop;
          const sectionHeight = section.offsetHeight;
          
          if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            document.querySelector(`.nav-link[href="#${sectionId}"]`)?.classList.add('active');
          }
        }
      });
    });

  </script>
  
  <!-- Script para manejo dinámico del footer -->
  <script src="../../../public/assets/js/footer-dinamico.js?v=20261021-restaurant-2"></script>

</body>
</html>
