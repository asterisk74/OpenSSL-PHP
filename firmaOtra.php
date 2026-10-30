<?php
/**
 * Ejercicio 3: generar una clave RSA y un certificado X.509 autofirmado.
 * El certificado contiene información pública, pero la clave privada debe protegerse.
 * Este certificado es solo de laboratorio, no lo avala una autoridad certificadora.
 */
// Escapar la información de los campos antes de imprimirla en el HTML.
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
// Valores de ejemplo para los campos de identidad del certificado (DN).
$campos = ['countryName'=>'PA', 'stateOrProvinceName'=>'Panama', 'localityName'=>'Panama',
    'organizationName'=>'Laboratorio Web', 'commonName'=>'prueba.local', 'emailAddress'=>'prueba@example.test'];
$error = '';
$salida = null;
// Procesar los datos del formulario antes de generar el certificado.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($campos as $campo=>$valor) { $campos[$campo] = trim((string)($_POST[$campo] ?? '')); }
    // Comprobar campos obligatorios, código de país de dos letras y correo válido.
    if (in_array('', $campos, true) || strlen($campos['countryName']) !== 2 ||
        filter_var($campos['emailAddress'], FILTER_VALIDATE_EMAIL) === false) {
        $error = 'Completa los campos; el país debe tener dos letras y el correo debe ser válido.';
    } else {
        // Configurar un nuevo par de claves RSA de 2048 bits con SHA-256.
        $configArgs = ['private_key_bits'=>2048, 'private_key_type'=>OPENSSL_KEYTYPE_RSA,
            'digest_alg'=>'sha256'];
        // En WampServer se usa openssl.cnf si esa ruta realmente existe.
        $cnf = 'C:/wamp64/bin/php/php8.5.0/extras/ssl/openssl.cnf';
        if (is_file($cnf)) { $configArgs['config'] = $cnf; }
        $par = openssl_pkey_new($configArgs);
        if ($par === false) {
            $error = 'No se pudo generar la clave RSA.';
        } else {
            // CSR = solicitud de firma de certificado con los datos ingresados.
            $csr = openssl_csr_new($campos, $par, $configArgs);
            // Al usar null como certificado emisor, se crea un certificado autofirmado
            // para 365 días (válido para práctica, no para acreditar identidad real).
            $cert = $csr !== false ? openssl_csr_sign($csr, null, $par, 365, $configArgs) : false;
            $privada = '';
            $certPem = '';
            if ($cert === false || !openssl_pkey_export($par, $privada, null, $configArgs) ||
                !openssl_x509_export($cert, $certPem)) {
                $error = 'No se pudo crear la CSR o el certificado. Comprueba openssl.cnf.';
            } else {
                // El certificado es público, pero la clave privada se guarda fuera de www.
                // La clave privada exportada no tiene contraseña: solo para laboratorio.
                $dir = dirname(__DIR__, 2) . '/openssl-lab-private/keysCert';
                if ((!is_dir($dir) && !mkdir($dir, 0700, true)) ||
                    file_put_contents($dir . '/privkey.pem', $privada) === false ||
                    file_put_contents($dir . '/certout.pem', $certPem) === false) {
                    $error = 'No se pudieron guardar los archivos de certificado.';
                } else {
                    // Leer las fechas de vigencia para mostrarlas al usuario.
                    $datosCert = openssl_x509_parse($cert);
                    $salida = ['pem'=>$certPem,'ruta'=>$dir,'desde'=>$datosCert['validFrom_time_t']??0,
                        'hasta'=>$datosCert['validTo_time_t']??0];
                }
            }
        }
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Certificado de prueba X.509</title><link rel="stylesheet" href="estilo.css"></head><body>
<header><h1>3. firmaOtra.php</h1><p>Creación de un certificado X.509 autofirmado y su clave privada</p></header><div class="container"><nav><a href="index.php">Inicio</a><a href="FirmarMensaje.php">Siguiente: FirmarMensaje</a></nav><main>
<!-- Formulario para completar los datos del sujeto del certificado X.509. -->
<form method="post">
<?php foreach(['countryName'=>'País (dos letras)','stateOrProvinceName'=>'Provincia','localityName'=>'Localidad','organizationName'=>'Organización ficticia','commonName'=>'Nombre común del certificado','emailAddress'=>'Correo de prueba'] as $n=>$etiqueta): ?>
<label for="<?=h($n)?>"><?=h($etiqueta)?></label><input name="<?=h($n)?>" id="<?=h($n)?>" value="<?=h($campos[$n])?>" maxlength="100" required>
<?php endforeach; ?>
<button type="submit">Generar certificado de prueba</button></form>
<?php if ($error): ?><div class="error"><?=h($error)?></div><?php endif; ?>
<!-- Mostrar el certificado público PEM; NUNCA imprimir la clave privada. -->
<?php if ($salida): ?><div class="result"><h2>Certificado generado</h2><p>Válido desde <?=h(date('Y-m-d H:i:s', $salida['desde']))?> hasta <?=h(date('Y-m-d H:i:s', $salida['hasta']))?></p>
<p>Certificado PEM (público):</p><pre><?=h($salida['pem'])?></pre><p>Se guardó en <code><?=h($salida['ruta'])?></code> fuera de www.</p><p><strong>Siguiente paso:</strong> abrir <a href="FirmarMensaje.php">FirmarMensaje.php</a>.</p></div><?php endif; ?>
<p class="info">Este certificado es autofirmado y sirve para pruebas: no representa una identidad validada por una autoridad certificadora.</p>
</main></div></body></html>
