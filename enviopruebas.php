<?php

class VeriFactu {
    private $invoiceData;
    private $certificatePath;
    private $certificatePassword;
    private $wsdlUrl;

    public function __construct($invoiceData, $certificatePath, $certificatePassword) {
        $this->invoiceData = $invoiceData;
        $this->certificatePath = $certificatePath;
        $this->certificatePassword = $certificatePassword;
        $this->wsdlUrl = 'https://prewww2.aeat.es/verifactu/wsdl'; // URL correcta para el entorno de pruebas
    }

    public function generateXML() {
        $xml = new SimpleXMLElement('<Factura></Factura>');
        
        $emisor = $xml->addChild('Emisor');
        $emisor->addChild('NIF', $this->invoiceData['Emisor']['NIF']);
        $emisor->addChild('Nombre', $this->invoiceData['Emisor']['Nombre']);

        $receptor = $xml->addChild('Receptor');
        $receptor->addChild('NIF', $this->invoiceData['Receptor']['NIF']);
        $receptor->addChild('Nombre', $this->invoiceData['Receptor']['Nombre']);

        $factura = $xml->addChild('Factura');
        $factura->addChild('Numero', $this->invoiceData['Factura']['Numero']);
        $factura->addChild('Fecha', $this->invoiceData['Factura']['Fecha']);
        $factura->addChild('Concepto', $this->invoiceData['Factura']['Concepto']);

        $impuestos = $factura->addChild('Impuestos');
        foreach ($this->invoiceData['Factura']['Impuestos'] as $impuesto) {
            $imp = $impuestos->addChild('Impuesto');
            $imp->addChild('Tipo', $impuesto['Tipo']);
            $imp->addChild('Base', $impuesto['Base']);
            $imp->addChild('Cuota', $impuesto['Cuota']);
        }

        return $xml->asXML();
    }

    public function sendToAEAT() {
        $xml = $this->generateXML();
        
        $client = new SoapClient($this->wsdlUrl, [
            'local_cert' => $this->certificatePath,
            'passphrase' => $this->certificatePassword,
            'trace' => 1,
            'exceptions' => 1
        ]);
        
        try {
            $response = $client->__soapCall('EnviarFactura', [['facturaXML' => $xml]]);
            return $response;
        } catch (SoapFault $fault) {
            return "Error: " . $fault->getMessage();
        }
    }
}

// Datos de la factura
$invoiceData = [
    'Emisor' => ['NIF' => 'B72877814', 'Nombre' => 'WEFINZ GANDIA SL'],
    'Receptor' => ['NIF' => 'B44531218', 'Nombre' => 'WEFINZ SOLUTIONS SL'],
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

$verifactu = new VeriFactu($invoiceData, $certificatePath, $certificatePassword);
$response = $verifactu->sendToAEAT();

print_r($response);
?>
