<?php
/**
 * Ejercicio 2: generar un par de claves RSA y firmar un mensaje con SHA-256.
 * La firma se crea con la clave privada y se comprueba con la clave pública.
 * Se incluye una prueba controlada con un mensaje alterado para demostrar integridad.
 */
// Escapar los datos de entrada antes de imprimirlos en una página HTML.
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
// Se separa el mensaje del formulario de los valores que se mostrarán al finalizar.
$datos = '';
$salida = null;
$error = '';
// El par de claves y la firma solo se generan tras enviar el mensaje.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = trim((string)($_POST['datos'] ?? ''));
    if ($datos === '') {
        $error = 'Debes escribir el mensaje que deseas firmar.';
    } else {
        // Archivo openssl.cnf de WampServer: se usa solo cuando existe.
        $configArgs = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        $cnf = 'C:/wamp64/bin/php/php8.5.0/extras/ssl/openssl.cnf';
        if (is_file($cnf)) { $configArgs['config'] = $cnf; }
        // Generar en memoria una clave privada RSA y su clave pública asociada.
        $par = openssl_pkey_new($configArgs);
        if ($par === false) {
            $error = 'No se pudo crear el par de claves RSA. Revisa la configuración OpenSSL.';
        } else {
            // Extraer la clave pública en formato PEM (esta sí se puede mostrar).
            $detalles = openssl_pkey_get_details($par);
            $publica = $detalles['key'] ?? '';
            $firma = '';
            $privada = '';
            // Exportar la clave privada y firmar el mensaje con RSA y SHA-256.
            if (!openssl_pkey_export($par, $privada, null, $configArgs) ||
                !openssl_sign($datos, $firma, $par, OPENSSL_ALGO_SHA256)) {
                $error = 'No se pudo exportar la clave o firmar el mensaje.';
            } else {
                // Guardar los archivos FUERA de www para no publicarlos por HTTP.
                // Esta clave de laboratorio no tiene contraseña: no usarla en producción.
                $dir = dirname(__DIR__, 2) . '/openssl-lab-private/keys';
                if (!is_dir($dir) && !mkdir($dir, 0700, true)) {
                    $error = 'No se pudo crear la carpeta privada de claves fuera de www.';
                } else {
                    $okGuardado = file_put_contents($dir . '/private_key.pem', $privada) !== false
                        && file_put_contents($dir . '/public_key.pem', $publica) !== false
                        && file_put_contents($dir . '/signature.dat', $firma) !== false;
                    if (!$okGuardado) {
                        $error = 'No se pudieron guardar los archivos de prueba.';
                    } else {
                        // 1 indica firma válida; 0, firma inválida; -1, error.
                        $correcta = openssl_verify($datos, $firma, $publica, OPENSSL_ALGO_SHA256);
                        // Alterar el mensaje SIN generar otra firma para demostrar el fallo.
                        $mensajeAlterado = $datos . ' [alterado]';
                        $alterada = openssl_verify($mensajeAlterado, $firma, $publica, OPENSSL_ALGO_SHA256);
                        $salida = ['publica' => $publica, 'firma' => base64_encode($firma),
                            'correcta' => $correcta, 'alterada' => $alterada, 'mensajeAlterado' => $mensajeAlterado,
                            // Las huellas sirven de apoyo visual: openssl_verify decide la validez.
                            'hashOriginal' => hash('sha256', $datos),
                            'hashAlterado' => hash('sha256', $mensajeAlterado),
                            'ruta' => $dir];
                    }
                }
            }
        }
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Firma RSA con OpenSSL</title><link rel="stylesheet" href="estilo.css"></head><body>
<header><h1>2. firma7.php</h1><p>Generación RSA 2048, firma SHA-256 y verificación</p></header><div class="container"><nav><a href="index.php">Inicio</a><a href="firmaOtra.php">Siguiente: firmaOtra</a></nav><main>
<!-- Entrada del mensaje que será firmado digitalmente. -->
<form method="post"><label for="datos">Mensaje para firmar</label><textarea id="datos" name="datos" required maxlength="3000"><?=h($datos)?></textarea><button type="submit">Generar claves y firmar</button></form>
<?php if ($error): ?><div class="error"><?=h($error)?></div><?php endif; ?>
<?php if ($salida): ?><div class="result"><h2>Resultados de firma7</h2>
<p><strong>Mensaje:</strong> <?=h($datos)?></p><p><strong>Firma Base64:</strong></p><pre><?=h($salida['firma'])?></pre>
<p><strong>Verificación del original:</strong> <?=$salida['correcta'] === 1 ? 'Firma válida' : 'No válida / error'?></p>
<p><strong>Mensaje modificado para la prueba:</strong> <?=h($salida['mensajeAlterado'])?></p>
<p><strong>Verificación del mensaje alterado con la firma original:</strong> <?=$salida['alterada'] === 0 ? 'Firma inválida (alteración detectada)' : 'Resultado no esperado: ' . (int)$salida['alterada']?></p>
<!-- Pasos que explican por qué la firma del mensaje alterado es inválida. -->
<details class="verification-detail">
  <summary>Detalle del proceso de verificación</summary>
  <div class="verification-body">
    <h3>1. Comparación de los mensajes</h3>
    <p><strong>Original:</strong></p><pre><?=h($datos)?></pre>
    <p><strong>Alterado automáticamente:</strong></p><pre><?=h($salida['mensajeAlterado'])?></pre>
    <p>Para realizar la prueba se agrega <code>[alterado]</code> al final del texto. El programa <strong>no crea una nueva firma</strong> para ese mensaje.</p>
    <h3>2. Huellas SHA-256 (ilustran el cambio)</h3>
    <p><strong>SHA-256 del original:</strong></p><pre><?=h($salida['hashOriginal'])?></pre>
    <p><strong>SHA-256 del alterado:</strong></p><pre><?=h($salida['hashAlterado'])?></pre>
    <p><strong>¿Son iguales las huellas?</strong> <?=$salida['hashOriginal'] === $salida['hashAlterado'] ? 'Sí' : 'No, son diferentes.'?></p>
    <p class="explanation">Estas huellas se muestran para ilustrar la modificación. La comprobación criptográfica real la realiza <code>openssl_verify()</code>, no una simple comparación de hashes.</p>
    <h3>3. Misma clave pública y misma firma</h3>
    <p>Ambas llamadas verifican la <strong>misma firma original</strong> con la <strong>misma clave pública RSA</strong> y el algoritmo SHA-256; únicamente cambia el mensaje que se comprueba.</p>
    <h3>4. Resultado de openssl_verify()</h3>
    <p><strong>Con el mensaje original:</strong> <?= (int)$salida['correcta'] ?> — <?= $salida['correcta'] === 1 ? '1 significa firma válida.' : ($salida['correcta'] === 0 ? '0 significa firma inválida.' : '-1 indica un error en la verificación.') ?></p>
    <p><strong>Con el mensaje alterado:</strong> <?= (int)$salida['alterada'] ?> — <?= $salida['alterada'] === 0 ? '0 significa firma inválida.' : ($salida['alterada'] === 1 ? '1 significa firma válida.' : '-1 indica un error en la verificación.') ?></p>
    <p class="explanation">En esta prueba sabemos qué cambió porque modificamos el mensaje deliberadamente. En situaciones reales, una firma inválida también podría indicar una firma equivocada u otra discrepancia.</p>
  </div>
</details>
<p><strong>Clave pública PEM (se puede mostrar):</strong></p><pre><?=h($salida['publica'])?></pre>
<p><small>Archivos guardados fuera del directorio web en <?=h($salida['ruta'])?>. No publiques la clave privada.</small></p></div><?php endif; ?>
</main></div></body></html>
