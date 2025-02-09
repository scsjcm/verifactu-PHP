<?php

function generateXML($invoiceData) {
    $xml = new SimpleXMLElement('<Factura></Factura>');
    
    $emisor = $xml->addChild('Emisor');
    $emisor->addChild('NIF', $invoiceData['Emisor']['NIF']);
    $emisor->addChild('Nombre', $invoiceData['Emisor']['Nombre']);

    $receptor = $xml->addChild('Receptor');
    $receptor->addChild('NIF', $invoiceData['Receptor']['NIF']);
    $receptor->addChild('Nombre', $invoiceData['Receptor']['Nombre']);

    $factura = $xml->addChild('Factura');
    $factura->addChild('Numero', $invoiceData['Factura']['Numero']);
    $factura->addChild('Fecha', $invoiceData['Factura']['Fecha']);
    $factura->addChild('Concepto', $invoiceData['Factura']['Concepto']);

    $impuestos = $factura->addChild('Impuestos');
    foreach ($invoiceData['Factura']['Impuestos'] as $impuesto) {
        $imp = $impuestos->addChild('Impuesto');
        $imp->addChild('Tipo', $impuesto['Tipo']);
        $imp->addChild('Base', $impuesto['Base']);
        $imp->addChild('Cuota', $impuesto['Cuota']);
    }

    return $xml->asXML();
}

function sendToAEAT($invoiceData, $certificatePath, $certificatePassword) {
    $wsdlUrl = 'https://prewww2.aeat.es/verifactu/wsdl'; // URL del entorno de pruebas
    $xml = generateXML($invoiceData);
    
    try {
        $client = new SoapClient($wsdlUrl, [
            'local_cert' => $certificatePath,
            'passphrase' => $certificatePassword,
            'trace' => 1,
            'exceptions' => 1
        ]);
        
        $response = $client->__soapCall('EnviarFactura', [['facturaXML' => $xml]]);
        return $response;
    } catch (SoapFault $fault) {
        return "Error: " . $fault->getMessage();
    }
}

// Datos de la factura
$invoiceData = [
    'Emisor' => ['NIF' => 'B00000000', 'Nombre' => 'EMISOR PRUEBAS SL'],
    'Receptor' => ['NIF' => 'B00000000', 'Nombre' => 'RECEPTOR PRUEBAS SL'],
    'Factura' => [
        'Numero' => 'GIT-EJ-0002',
        'Fecha' => '2024-11-15',
        'Concepto' => 'PRESTACION SERVICIOS DESARROLLO SOFTWARE',
        'Impuestos' => [
            ['Tipo' => 4, 'Base' => 10, 'Cuota' => 0.4],
            ['Tipo' => 21, 'Base' => 100, 'Cuota' => 21]
        ]
    ]
];

$certificatePath = '/ruta/al/certificado.pfx';
$certificatePassword = 'password';

$response = sendToAEAT($invoiceData, $certificatePath, $certificatePassword);
print_r($response);

?>
