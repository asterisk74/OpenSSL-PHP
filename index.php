<?php
/**
 * Página de inicio del laboratorio.
 * Los enlaces permiten visitar los cuatro ejemplos OpenSSL y el de Sodium.
 */
// Función auxiliar para escapar texto al insertarlo en una página HTML.
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Laboratorio OpenSSL y Sodium</title><link rel="stylesheet" href="estilo.css"></head>
<body><header><h1>Laboratorio OpenSSL en PHP</h1><p>Cifrado, firmas digitales, certificados y Sodium</p></header>
<!-- Índice de navegación: los ejemplos se abren en el orden de la práctica. -->
<div class="container"><main><h2>Ejemplos del laboratorio</h2><nav>
<a href="Encriptacion.php">1. Encriptación</a>
<a href="firma7.php">2. firma7</a>
<a href="firmaOtra.php">3. firmaOtra (certificado)</a>
<a href="FirmarMensaje.php">4. FirmarMensaje</a>
<a href="Sodium.php">5. Sodium</a>
</nav><p>Ejecuta <strong>firmaOtra.php</strong> antes de <strong>FirmarMensaje.php</strong>, porque el segundo usa el certificado de prueba generado por el primero.</p><p class="info">Demostración local de aprendizaje. No uses información sensible ni publiques claves privadas.</p></main></div></body></html>
