function showDetails(type) {
    document.getElementById('catalogo').style.display = 'none';
    document.getElementById('details').style.display = 'block';

    const title = document.getElementById('details-title');
    const description = document.getElementById('details-description');

    if (type === 'gourmet') {
        title.textContent = 'Restaurante Gourmet';
        description.innerHTML = '<strong>Menú:</strong><br>- Entrante: Ensalada de cangrejo<br>- Plato principal: Filete a la parrilla con puré de trufa<br>- Postre: Tarta de chocolate<br><br><button>Reservar</button> <button>Pedir a domicilio</button>';
    } else if (type === 'caseras') {
        title.textContent = 'Delicias Caseras';
        description.innerHTML = '<strong>Menú:</strong><br>- Entrante: Sopa de verduras<br>- Plato principal: Estofado de ternera<br>- Postre: Pastel de manzana<br><br><button>Reservar</button> <button>Pedir a domicilio</button>';
    } else if (type === 'italia') {
        title.textContent = 'Sabor a Italia';
        description.innerHTML = '<strong>Menú:</strong><br>- Entrante: Bruschetta<br>- Plato principal: Pizza margarita o Spaghetti carbonara<br>- Postre: Tiramisú<br><br><button>Reservar</button> <button>Pedir a domicilio</button>';
    }
}

function goBack() {
    document.getElementById('catalogo').style.display = 'flex';
    document.getElementById('details').style.display = 'none';
}