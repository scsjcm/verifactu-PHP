<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo 1;

// 1. Funciones auxiliares y generación de hash

// Incluye la librería para SOAP
require 'vendor/autoload.php';
use Laminas\Soap\Client as SoapClient;

function Trimestre($dia) {
    $mes = date('n', strtotime($dia));
    if ($mes <= 3) return 1;
    if ($mes <= 6) return 2;
    if ($mes <= 9) return 3;
    return 4;
}

function GenerarHashRegistroVerifactu($cadenaVerifactu) {
    return hash('sha256', $cadenaVerifactu);
}

// Definición de clases para tipos SOAP
class ClaveTipoFacturaType {}
class ClaveTipoRectificativaType {}
class IdOperacionesTrascendenciaTributariaType {}
class CalificacionOperacionType {}
class OperacionExentaType {}



// 2. Funciones para generar registro de facturación y agregar detalles///////////////////////

function GenerarRegistroFacturacion_AltaVerifactu($conexion, $tablaMaestra, $idDocumento) {
    $nombreTabla = obtenerValorCampo($conexion, 'Tablas', 'Codigo', 'Nombre', $tablaMaestra);
    $nombreVista = 'Vista' . $nombreTabla;

    $dtFactura = crearDataset($conexion, 'DTFactura', $nombreVista, $idDocumento);
    $dtDesgloseIvas = crearDataset($conexion, 'DTDesgloseIvas', 'DocumentosDesgloseIvas', $idDocumento);

    $idFactura = $dtFactura['Identificador'];

    // Crear la estructura de la factura
    $factura = new stdClass();
    $factura->RegistroFacturacion = new stdClass();
    $factura->RegistroFacturacion->IDFactura = new stdClass();
    $factura->RegistroFacturacion->IDFactura->IDEmisorFactura = new stdClass();
    $factura->RegistroFacturacion->IDFactura->IDEmisorFactura->NIF = obtenerValorCampoEmpresa('NIF');
    $factura->RegistroFacturacion->IDFactura->NumSerieFacturaEmisor = $dtFactura['ReferenciaDocumento'];
    $factura->RegistroFacturacion->IDFactura->FechaExpedicionFacturaEmisor = str_replace('/', '-', $dtFactura['FECH']); // (dd-mm-yyyy)
    $factura->RegistroFacturacion->NombreRazonEmisor = obtenerValorCampoEmpresa('C_EMPRESA');
    $factura->RegistroFacturacion->TipoRegistroSIF = 'S0'; // Averiguar cuándo usar
    $factura->RegistroFacturacion->TipoFactura = TipoFacturaVerifactu($dtFactura);

    if (!empty(trim($dtFactura['CorrectionMethod1']))) {
        $factura->RegistroFacturacion->TipoRectificativa = TipoRectificativaVerifactu($dtFactura['CorrectionMethod1']);
    }

    // Agregar detalles y desglose
    $factura->RegistroFacturacion->Desglose = agregarDesglose($dtDesgloseIvas, $factura);

    // Otros campos
    $factura->RegistroFacturacion->FechaOperacion = str_replace('/', '-', $dtFactura['FECH']); // (dd-mm-yyyy)
    $factura->RegistroFacturacion->DescripcionOperacion = 'Venta';
    $factura->RegistroFacturacion->FacturaSimplificadaArticulos7_2_7_3 = 'N'; // Factura simplificada Articulo 7,2 Y 7,3 RD 1619/2012.
    $factura->RegistroFacturacion->FacturaSinIdentifDestinatarioArticulo6_1_d = 'N'; // Si es simplificada N ordinaria S, por aclarar
    $factura->RegistroFacturacion->Macrodato = 'N';

    // Destinatarios
    $destinatario = new stdClass();
    $destinatario->NombreRazon = $dtFactura['NOMB'];
    $destinatario->NIF = $dtFactura['NIF'];
    $destinatario->IDOtro = new stdClass();
    $destinatario->IDOtro->IdType = '02';
    $destinatario->IDOtro->ID = $dtFactura['NIF'];

    $factura->RegistroFacturacion->Destinatarios = [$destinatario];

    return $factura;
}

function agregarDesglose($dtDesgloseIvas, $factura) {
    $desglose = [];
    foreach ($dtDesgloseIvas as $detalleIva) {
        $detalleDesglose = new stdClass();
        $detalleDesglose->ClaveRegimen = ClaveRegimenVerifactu($detalleIva['RegimenIva']);
        $detalleDesglose->CalificacionOperacion = TipoCalificacionOperacion();
        if (in_array($detalleDesglose->CalificacionOperacion, ['N1', 'N2'])) {
            $detalleDesglose->OperacionExenta = TipoOperacionExenta();
        }
        $detalleDesglose->TipoImpositivo = number_format($detalleIva['IVA'], 2);
        $detalleDesglose->BaseImponibleOimporteNoSujeto = number_format($detalleIva['BASIMP'], 2);
        $detalleDesglose->BaseImponibleACoste = '0.00';
        $detalleDesglose->CuotaRepercutida = number_format($detalleIva['BASIVA'], 2);
        $detalleDesglose->TipoRecargoEquivalencia = number_format($detalleIva['REC'], 2);
        $detalleDesglose->CuotaRecargoEquivalencia = number_format($detalleIva['BASREC'], 2);

        $desglose[] = $detalleDesglose;
    }
    return $desglose;
}

function ClaveRegimenVerifactu($regimenIva) {
    switch ($regimenIva) {
        case '01': return '01';
        case '02': return '02';
        case '03': return '03';
        case '04': return '04';
        case '05': return '05';
        case '06': return '06';
        case '07': return '07';
        case '08': return '08';
        case '09': return '09';
        case '10': return '10';
        case '11': return '11';
        case '12': return '12';
        case '13': return '13';
        case '14': return '14';
        case '15': return '15';
        case '16': return '16';
        default: return '01';
    }
}

function TipoCalificacionOperacion() {
    return 'S1'; // Default value
}

function TipoOperacionExenta() {
    return 'E0'; // Default value
}






// 3. Función para enviar factura y generar XML

function enviarFactura($factura) {
    $wsdl = 'URL_DEL_WSDL';
    $client = new SoapClient($wsdl, [
        'trace' => 1,
        'exceptions' => true
    ]);

    try {
        $response = $client->AltaFactuSistemaFacturacion(new SoapVar($factura, SOAP_ENC_OBJECT, null, null, 'AltaFactuSistemaFacturacion', 'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd'));
        echo 'Se ha realizado el envío; <br>';
        echo 'CSV: ' . $response->CSV . '<br>';
        echo 'TimeStamp: ' . $response->DatosPresentacion->TimestampPresentacion . '<br>';
    } catch (Exception $e) {
        echo 'Error al realizar el envío; (' . $e->getCode() . ')-' . $e->getMessage();
    }
}

function crearDataset($conexion, $nombreDataset, $nombreVista, $idDocumento) {
    // Implementar la lógica para crear y devolver el dataset basado en la conexión y parámetros
    // Esto generalmente implica ejecutar una consulta SQL y devolver los resultados
}

function obtenerValorCampo($conexion, $tabla, $campoClave, $campoValor, $clave) {
    // Implementar la lógica para obtener un valor de campo desde la base de datos
}

function obtenerValorCampoEmpresa($campo) {
    // Implementar la lógica para obtener un valor de campo de la tabla de empresa
}









// 2. Convertir el XML a un Objeto PHP


// Cargar el archivo XML
$xml = simplexml_load_file('RegistroFacturacion.xml');

// Convertir SimpleXML a JSON y luego a un array PHP
$json = json_encode($xml);
$array = json_decode($json, true);

// Crear la estructura de la factura
$factura = new stdClass();
$factura->RegistroFacturacion = new stdClass();
$factura->RegistroFacturacion->IDFactura = new stdClass();
$factura->RegistroFacturacion->IDFactura->NumSerieFacturaEmisor = $array['IDFactura']['NumSerieFacturaEmisor'];
$factura->RegistroFacturacion->IDFactura->FechaExpedicionFacturaEmisor = $array['IDFactura']['FechaExpedicionFacturaEmisor'];
$factura->RegistroFacturacion->IDFactura->IDEmisorFactura = new stdClass();
$factura->RegistroFacturacion->IDFactura->IDEmisorFactura->NIF = $array['IDFactura']['IDEmisorFactura']['NIF'];
$factura->RegistroFacturacion->DescripcionOperacion = $array['DescripcionOperacion'];

// Mostrar los datos
echo '2 ' . $factura->RegistroFacturacion->DescripcionOperacion . ' ' . $factura->RegistroFacturacion->IDFactura->NumSerieFacturaEmisor;

?>
