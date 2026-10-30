<?php
/**
 * Ejercicio 4: firmar un mensaje y verificarlo con un certificado X.509.
 * Reutiliza la clave privada y el certificado creados en firmaOtra.php.
 * La comprobación del texto alterado se realiza con la MISMA firma original.
 */
// Mostrar de forma segura los mensajes ingresados por el usuario.
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
// Preparar variables de entrada, errores y resultado para la página.
$mensaje = '';
$error = '';
$salida = null;
// Cargar los archivos creados por firmaOtra.php desde una carpeta no pública.
$dir = dirname(__DIR__, 2) . '/openssl-lab-private/keysCert';
$existen = is_file($dir . '/privkey.pem') && is_file($dir . '/certout.pem');
// Procesar la firma únicamente cuando el usuario envía el formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = trim((string)($_POST['mensaje'] ?? ''));
    if (!$existen) {
        $error = 'Primero debes generar el certificado en firmaOtra.php.';
    } elseif ($mensaje === '') {
        $error = 'Escribe un mensaje para firmar.';
    } else {
        // Obtener la clave privada para firmar y la clave pública del certificado.
        $privada = openssl_pkey_get_private(file_get_contents($dir . '/privkey.pem'));
        $publica = openssl_pkey_get_public(file_get_contents($dir . '/certout.pem'));
        if ($privada === false || $publica === false) {
            $error = 'No se pudieron leer la clave privada o el certificado.';
        } else {
            $firma = '';
            // Crear la firma RSA/SHA-256: no se cifra ni se oculta el mensaje.
            if (!openssl_sign($mensaje, $firma, $privada, OPENSSL_ALGO_SHA256)) {
                $error = 'No se pudo firmar el mensaje.';
            } else {
                // Verificar con la clave pública extraída del certificado X.509.
                $verificacion = openssl_verify($mensaje, $firma, $publica, OPENSSL_ALGO_SHA256);
                // Cambiar deliberadamente el contenido, conservando la firma original.
                $mensajeAlterado = $mensaje . ' [alterado]';
                $alterada = openssl_verify($mensajeAlterado, $firma, $publica, OPENSSL_ALGO_SHA256);
                // Guardar la firma en Base64 para mostrarla y calcular dos huellas
                // ilustrativas (la verificación real siempre la hace openssl_verify).
                $salida = ['base64'=>base64_encode($firma), 'verificacion'=>$verificacion, 'alterada'=>$alterada, 'mensajeAlterado'=>$mensajeAlterado,
                    'hashOriginal'=>hash('sha256', $mensaje), 'hashAlterado'=>hash('sha256', $mensajeAlterado)];
            }
        }
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Firmar y verificar un mensaje</title><link rel="stylesheet" href="estilo.css"></head><body>
<header><h1>4. FirmarMensaje.php</h1><p>Firma SHA-256/RSA y verificación mediante certificado X.509</p></header><div class="container"><nav><a href="index.php">Inicio</a><a href="firmaOtra.php">Generar certificado</a><a href="Sodium.php">Siguiente: Sodium</a></nav><main>
<?php if (!$existen): ?><div class="info">Todavía no se detectan las claves de prueba. Abre primero <a href="firmaOtra.php">firmaOtra.php</a>.</div><?php endif; ?>
<!-- Formulario para enviar el mensaje que se firmará con la clave existente. -->
<form method="post"><label for="mensaje">Mensaje que deseas firmar</label><textarea id="mensaje" name="mensaje" maxlength="3000" required><?=h($mensaje)?></textarea><button type="submit">Firmar y verificar</button></form>
<?php if ($error): ?><div class="error"><?=h($error)?></div><?php endif; ?>
<?php if ($salida): ?><div class="result"><h2>Resultado de la firma</h2><p><strong>Mensaje original:</strong> <?=h($mensaje)?></p>
<p><strong>Firma Base64:</strong></p><pre><?=h($salida['base64'])?></pre>
<p><strong>Verificación original:</strong> <?=$salida['verificacion']===1 ? 'Firma válida: mensaje intacto.' : 'Firma no válida o error.'?></p>
<p><strong>Mensaje modificado para la prueba:</strong> <?=h($salida['mensajeAlterado'])?></p>
<p><strong>Verificación de mensaje modificado con la firma original:</strong> <?=$salida['alterada']===0 ? 'Firma inválida: alteración detectada.' : 'Resultado no esperado: ' . (int)$salida['alterada']?></p>
<!-- Detalle de comprobación con el texto intacto y con el texto alterado. -->
<details class="verification-detail">
  <summary>Detalle del proceso de verificación</summary>
  <div class="verification-body">
    <h3>1. Comparación de mensajes</h3>
    <p><strong>Mensaje original:</strong></p><pre><?=h($mensaje)?></pre>
    <p><strong>Mensaje alterado:</strong></p><pre><?=h($salida['mensajeAlterado'])?></pre>
    <p>La prueba agrega <code>[alterado]</code> al texto, pero mantiene la firma digital obtenida inicialmente.</p>
    <h3>2. Huellas SHA-256 (para visualizar la alteración)</h3>
    <p><strong>SHA-256 del original:</strong></p><pre><?=h($salida['hashOriginal'])?></pre>
    <p><strong>SHA-256 del alterado:</strong></p><pre><?=h($salida['hashAlterado'])?></pre>
    <p><strong>¿Coinciden?</strong> <?=$salida['hashOriginal'] === $salida['hashAlterado'] ? 'Sí' : 'No, son distintas.'?></p>
    <p class="explanation">Las huellas son una ayuda visual. La validación de la firma se hace realmente mediante <code>openssl_verify()</code>.</p>
    <h3>3. Clave pública del certificado y firma original</h3>
    <p>Se reutilizan la <strong>misma firma digital</strong> y la <strong>clave pública extraída del certificado X.509</strong> para verificar los dos mensajes con SHA-256/RSA.</p>
    <h3>4. Resultado de openssl_verify()</h3>
    <p><strong>Original:</strong> <?= (int)$salida['verificacion'] ?> — <?= $salida['verificacion'] === 1 ? '1: firma válida.' : ($salida['verificacion'] === 0 ? '0: firma inválida.' : '-1: error de verificación.') ?></p>
    <p><strong>Alterado:</strong> <?= (int)$salida['alterada'] ?> — <?= $salida['alterada'] === 0 ? '0: firma inválida.' : ($salida['alterada'] === 1 ? '1: firma válida.' : '-1: error de verificación.') ?></p>
    <p class="explanation">Se detecta la alteración que nosotros hicimos. Una verificación fallida fuera de esta prueba no permite saber por sí sola cuál fue el cambio.</p>
  </div>
</details>
</div><?php endif; ?></main></div></body></html>
