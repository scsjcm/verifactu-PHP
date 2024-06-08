<?php 
// https://www.agenciatributaria.es/static_files/AEAT_Desarrolladores/EEDD/IVA/VERI-FACTU/Veri-Factu_especificaciones_huella_hash_registros.pdf

function getHashVerifactu($idEmisoraFactura, $numFacturaSerie, $fechaExpedicion, $tipoFactura, $cuotaTotal, $importeTotal, $huellaAnterior, $fechaUsoHorarioRegistro){ 
	$cadena = "IDEmisorFactura=$idEmisoraFactura&NumSerieFactura=$numFacturaSerie&FechaExpedicionFactura=$fechaExpedicion&TipoFactura=$tipoFactura&CuotaTotal=$cuotaTotal&ImporteTotal=$importeTotal&Huella=$huellaAnterior&FechaHoraHusoGenRegistro=$fechaUsoHorarioRegistro";
	
	return hash('sha256', $cadena); 
}
?>
