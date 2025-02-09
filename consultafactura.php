<?php

function consultarFactura($invoiceNumber, $certificatePath, $certificatePassword) {
    $wsdlUrl = 'https://prewww2.aeat.es/verifactu/wsdl'; // URL del entorno de pruebas
    
    try {
        $client = new SoapClient($wsdlUrl, [
            'local_cert' => $certificatePath,
            'passphrase' => $certificatePassword,
            'trace' => 1,
            'exceptions' => 1
        ]);
        
        $response = $client->__soapCall('ConsultarFactura', [['numeroFactura' => $invoiceNumber]]);
        return $response;
    } catch (SoapFault $fault) {
        return "Error: " . $fault->getMessage();
    }
}

// Datos de la consulta
$invoiceNumber = 'GIT-EJ-0002';
$certificatePath = '/ruta/al/certificado.pfx';
$certificatePassword = 'password';

$response = consultarFactura($invoiceNumber, $certificatePath, $certificatePassword);
print_r($response);

?>
