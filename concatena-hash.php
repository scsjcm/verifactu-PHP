<?php
// Información de la factura
$nif = "00000006Y";
$numSerieFactura = "1.1.1.8";
$fechaExpedicionFactura = "09-04-2024";
$tipoFactura = "F2";
$importeTotal = "2.01";
$cuotaTotal = "0.00";
$huellaAnterior = "E6FE58EE455F233BFA429FA7A9D90EDC006CBB2421876EB2590D37E682414CB3";
$fechaHoraHusoGenRegistro = "2024-04-09T19:12:03+01";

// Concatenar los valores en el orden especificado
$cadenaVerifactu = $nif .
                   $numSerieFactura .
                   $fechaExpedicionFactura .
                   $tipoFactura .
                   $importeTotal .
                   $cuotaTotal .
                   $huellaAnterior .
                   $fechaHoraHusoGenRegistro;

// Calcular el hash SHA-256 de la cadena concatenada
$hashVerifactu = hash('sha256', $cadenaVerifactu);

// Mostrar el resultado
echo "Cadena concatenada: " . $cadenaVerifactu . "\n<br><br>";
echo "Hash SHA-256: " . $hashVerifactu . "\n<br><br>";
?>
