<?php
/**
 * Ejercicio 1: cifrado y descifrado simétrico utilizando OpenSSL.
 * Se mantiene AES-128-CBC para practicar el algoritmo del repositorio original.
 * Advertencia: CBC por sí solo no proporciona autenticación ni detecta alteraciones.
 */
// Escapar el contenido recibido antes de mostrarlo en HTML ayuda a evitar XSS.
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
// Variables iniciales: mensaje enviado, resultado del cifrado y errores.
$texto = '';
$resultado = null;
$error = '';
// Solo se cifra cuando el usuario envía el formulario por POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texto = trim((string)($_POST['texto'] ?? ''));
    // Evitar intentar cifrar mensajes vacíos.
    if ($texto === '') {
        $error = 'Escribe un mensaje antes de cifrarlo.';
    } else {
        // Se conserva AES-128-CBC del ejemplo original, pero se genera una clave aleatoria.
        $algoritmo = 'AES-128-CBC';
        // AES-128 necesita una clave secreta de 16 bytes (128 bits).
        $clave = random_bytes(16);
        // El IV debe ser nuevo e impredecible; se utiliza también para descifrar.
        $iv = random_bytes(openssl_cipher_iv_length($algoritmo));
        // Con options=0, openssl_encrypt devuelve el cifrado codificado en Base64.
        $cifrado = openssl_encrypt($texto, $algoritmo, $clave, 0, $iv);
        if ($cifrado === false) {
            $error = 'No se pudo cifrar el mensaje.';
        } else {
            // Recuperar el texto original usando exactamente la misma clave y el mismo IV.
            $descifrado = openssl_decrypt($cifrado, $algoritmo, $clave, 0, $iv);
            if ($descifrado === false) {
                $error = 'No se pudo descifrar el mensaje.';
            } else {
                // Comparar el texto recuperado con el original para mostrar el resultado.
                $resultado = ['cifrado' => $cifrado, 'descifrado' => $descifrado,
                    'coincide' => hash_equals($texto, $descifrado)];
            }
        }
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Encriptación con OpenSSL</title><link rel="stylesheet" href="estilo.css"></head><body>
<header><h1>1. Encriptacion.php</h1><p>Cifrado y descifrado simétrico: AES-128-CBC</p></header><div class="container"><nav><a href="index.php">Inicio</a><a href="firma7.php">Siguiente: firma7</a></nav><main>
<!-- El formulario entrega el mensaje a este mismo archivo mediante POST. -->
<form method="post"><label for="texto">Mensaje a cifrar</label><textarea id="texto" name="texto" required maxlength="3000"><?=h($texto)?></textarea><button type="submit">Cifrar y descifrar</button></form>
<?php if ($error): ?><div class="error"><?=h($error)?></div><?php endif; ?>
<!-- Mostrar resultados solo si la operación terminó satisfactoriamente. -->
<?php if ($resultado): ?><div class="result"><h2>Resultados</h2>
<dl><dt>Texto original</dt><dd><?=h($texto)?></dd><dt>Cifrado (Base64)</dt><dd><pre><?=h($resultado['cifrado'])?></pre></dd><dt>Texto descifrado</dt><dd><?=h($resultado['descifrado'])?></dd><dt>Comprobación</dt><dd><?=$resultado['coincide'] ? 'Correcto: el texto recuperado coincide.' : 'El texto recuperado no coincide.'?></dd></dl></div><?php endif; ?>
<p class="info">Ejemplo didáctico del algoritmo AES-CBC utilizado en el repositorio. CBC por sí solo no autentica el mensaje; la parte de Sodium demuestra cifrado autenticado. La clave y el IV se generan nuevamente en cada ejecución y no se muestran.</p></main></div></body></html>
