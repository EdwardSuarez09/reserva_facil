<?php
session_start();
session_destroy();
header("Location: ../../../public/index_cliente.html");
exit;
