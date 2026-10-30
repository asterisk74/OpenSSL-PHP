<?php
/**
 * Ejercicio 5: cifrado autenticado con la extensión Sodium de PHP.
 * secretbox protege confidencialidad e integridad y verifica antes de descifrar.
 * La clave de esta demostración solo se usa durante una ejecución.
 */
// Codificar al imprimir HTML para evitar interpretar contenido como etiquetas.
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
// Variables de control para conservar el mensaje, los resultados y los errores.
$mensaje=''; $salida=null; $error='';
// Se procesa la prueba al enviar el formulario por POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = trim((string)($_POST['mensaje']??''));
    if ($mensaje === '') {
        $error = 'Escribe un mensaje antes de cifrarlo.';
    // Confirmar que la extensión Sodium esté disponible en este PHP.
    } elseif (!function_exists('sodium_crypto_secretbox')) {
        $error = 'La extensión Sodium no está habilitada.';
    } else {
        // Generar una clave secreta nueva y aleatoria para esta ejecución.
        $clave = sodium_crypto_secretbox_keygen();
        // El nonce no es secreto, pero no se debe reutilizar con la misma clave.
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        // secretbox produce texto cifrado autenticado (cifrado + autenticador).
        $cifrado = sodium_crypto_secretbox($mensaje, $nonce, $clave);
        // Al abrir, Sodium verifica la autenticidad antes de devolver el texto.
        $recuperado = sodium_crypto_secretbox_open($cifrado, $nonce, $clave);
        $alterado = $cifrado;
        // Cambiamos un bit del último byte del texto cifrado (no del mensaje original).
        $indice = strlen($alterado) - 1;
        $byteAntes = ord($alterado[$indice]);
        // XOR con 1 invierte el bit menos significativo del último byte.
        $alterado[$indice] = chr($byteAntes ^ 1);
        $byteDespues = ord($alterado[$indice]);
        // Se espera false: el contenido cifrado ya no supera la autenticación.
        $verificacionAlterada = sodium_crypto_secretbox_open($alterado, $nonce, $clave);
        // Base64 permite presentar el nonce y los bytes cifrados como texto visible.
        // NO equivale a cifrar ni a firmar los datos.
        $salida = ['cifrado'=>base64_encode($nonce.$cifrado), 'recuperado'=>$recuperado,
            'alteracionDetectada'=>$verificacionAlterada === false,
            'cifradoAlterado'=>base64_encode($nonce.$alterado),
            'byteAntes'=>sprintf('%02X', $byteAntes), 'byteDespues'=>sprintf('%02X', $byteDespues),
            'indiceByte'=>$indice, 'longitudCifrado'=>strlen($cifrado),
            'originalAutenticado'=>$recuperado !== false];
        // Intentar borrar la clave temporal de la memoria al terminar la prueba.
        sodium_memzero($clave);
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sodium - cifrado autenticado</title><link rel="stylesheet" href="estilo.css"></head><body>
<header><h1>5. Sodium.php</h1><p>Ejemplo adicional requerido: cifrado autenticado y detección de alteraciones</p></header><div class="container"><nav><a href="index.php">Inicio</a></nav><main>
<!-- El mensaje ingresado se cifra mediante Sodium al enviar el formulario. -->
<form method="post"><label for="mensaje">Mensaje a cifrar</label><textarea id="mensaje" name="mensaje" maxlength="3000" required><?=h($mensaje)?></textarea><button type="submit">Cifrar, descifrar y verificar</button></form>
<?php if($error): ?><div class="error"><?=h($error)?></div><?php endif; ?>
<?php if($salida): ?><div class="result"><h2>Resultados de Sodium</h2><p><strong>Texto original:</strong> <?=h($mensaje)?></p>
<p><strong>Nonce + cifrado (Base64):</strong></p><pre><?=h($salida['cifrado'])?></pre>
<p><strong>Descifrado:</strong> <?=h((string)$salida['recuperado'])?></p>
<p><strong>Nonce + cifrado alterado (Base64):</strong></p><pre><?=h($salida['cifradoAlterado'])?></pre>
<p><strong>Cambio realizado:</strong> último byte del cifrado: 0x<?=h($salida['byteAntes'])?> → 0x<?=h($salida['byteDespues'])?> (se invirtió un bit).</p>
<p><strong>Verificación del cifrado alterado:</strong> <?=$salida['alteracionDetectada'] ? 'Alteración detectada; descifrado rechazado.' : 'No se detectó alteración.'?></p>
<!-- Explicación de la alteración de un byte y del rechazo de autenticación. -->
<details class="verification-detail">
  <summary>Detalle del proceso de verificación</summary>
  <div class="verification-body">
    <h3>1. Cifrado autenticado del texto original</h3>
    <p><code>sodium_crypto_secretbox()</code> crea el contenido cifrado junto con su autenticador. El programa usa una clave aleatoria temporal y un nonce aleatorio para esa ejecución.</p>
    <p><strong>Nonce + cifrado original en Base64:</strong></p><pre><?=h($salida['cifrado'])?></pre>
    <h3>2. Se modifica un byte de la parte cifrada</h3>
    <p>Se cambia el último byte, en la <strong>posición <?=$salida['indiceByte'] + 1?> de <?=$salida['longitudCifrado']?></strong> bytes del bloque cifrado (sin contar el nonce). El cambio exacto es <code>0x<?=h($salida['byteAntes'])?></code> → <code>0x<?=h($salida['byteDespues'])?></code> al invertir un bit.</p>
    <p><strong>Nonce + cifrado alterado en Base64:</strong></p><pre><?=h($salida['cifradoAlterado'])?></pre>
    <p class="explanation">Base64 solo representa bytes como texto legible para mostrarlos; no es un algoritmo de cifrado ni la comprobación de integridad.</p>
    <h3>3. Prueba con la misma clave y el mismo nonce</h3>
    <p>Se llama a <code>sodium_crypto_secretbox_open()</code> primero con el cifrado original y luego con el alterado. Antes de devolver el texto descifrado, Sodium comprueba su autenticidad.</p>
    <h3>4. Resultado de autenticación</h3>
    <p><strong>Cifrado original:</strong> <?=$salida['originalAutenticado'] ? 'Autenticación válida; devuelve el mensaje original.' : 'Autenticación fallida.'?></p>
    <p><strong>Cifrado alterado:</strong> <?=$salida['alteracionDetectada'] ? 'Devuelve false; la autenticación falla y no se entrega texto descifrado.' : 'La alteración no fue rechazada (resultado inesperado).'?></p>
    <p class="explanation">En este caso no hay una comparación de hashes SHA-256 ni una firma RSA: <code>secretbox_open()</code> comprueba internamente el autenticador criptográfico y rechaza los datos modificados.</p>
  </div>
</details>
</div><?php endif; ?>
<p class="info">El texto original no se modificó: la prueba altera un byte de los datos cifrados y usa el mismo nonce y la misma clave temporal. Si <code>sodium_crypto_secretbox_open()</code> devuelve <code>false</code>, se detectó la manipulación y no se entrega un mensaje descifrado.</p>
<p class="info">Se utiliza <code>sodium_crypto_secretbox()</code>. La clave es aleatoria y temporal; no aparece en pantalla. Para descifrar después de una recarga sería necesario conservarla de forma segura.</p>
</main></div></body></html>
