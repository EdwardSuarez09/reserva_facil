<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

fwrite(STDOUT, "Este cambio de esquema ya no es necesario: los campos de horario forman parte de la instalación.\n");
?> 