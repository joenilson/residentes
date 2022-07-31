<?php

/*
 * Copyright (C) 2019 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/residentes/extras/residentes_pdf.php';
require_once 'extras/phpmailer/class.phpmailer.php';
require_once 'extras/phpmailer/class.smtp.php';
require_once 'plugins/residentes/extras/residentes_controller.php';
require_once 'plugins/residentes/extras/residentesEnviarMail.php';


/**
 * Description of facturacion_residentes
 *
 * @author joenilson
 */

class facturacion_residentes extends residentes_controller 
{
    public $printExtensions;
    public $configuracionEmail;
    public $edificaciones_tipo;
    public $edificaciones_mapa;
    public $familia;
    public $familias;
    public $impuesto;
    public $mapa;
    public $padre;
    public $padre_interior;
    public $referencia;
    public $forma_pago;
    public $loop_horas;
    public $proxima_hora;
    public $idProg;
    public $programaciones;
    public $programaciones_conceptos;
    public $programaciones_edificaciones;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Facturación Residentes', 'residentes', false, true);
    }
    
    protected function private_core()
    {
        parent::private_core();
        $this->shared_functions();
        $this->tratarAccion();

        $eCfg = new residentes_email_config();
        $this->configuracionEmail = $eCfg->currentConfig();
        $this->programaciones = new residentes_facturacion_programada();
        $this->programaciones_conceptos = new residentes_facturacion_programada_conceptos();
        $this->programaciones_edificaciones = new residentes_facturacion_programada_edificaciones();
    }
    
    public function init()
    {
        parent::init();
        $extensions = new fs_extension();
        $this->printExtensions = $extensions->all_to('ventas_factura');
        $this->forma_pago = new forma_pago();
        $this->familia = new familia();
        $this->familias = $this->familia->get("RESIDENT");
        $this->impuesto = new impuesto();
        $this->loop_horas = [];
        //Creamos un array para el selector de horas para cron
        for ($x = 0; $x < 24; $x++) {
            $this->loop_horas[] = str_pad($x, 2, "0", STR_PAD_LEFT);
        }
        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
        $this->edificaciones_mapa = new residentes_edificaciones_mapa();
        $tipos = $this->edificaciones_tipo->all();
        $this->padre = $tipos[0];
        $this->mapa = $this->edificaciones_mapa->get_by_field('id_tipo', $this->padre->id);
        $this->proxima_hora = date('H', strtotime('+1 hour'));
    }
    
    private function shared_functions()
    {
        $extensiones = array(
            array(
                'name' => '001_facturacion_residentes',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="' . FS_PATH .
                            'plugins/residentes/view/css/bootstrap-select.min.css"/>',
                'params' => ''
            ),
            array(
                'name' => '002_facturacion_residentes',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.
                            'plugins/residentes/view/js/bootstrap-select.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '003_facturacion_residentes',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.
                            'plugins/residentes/view/js/i18n/defaults-es_ES.min.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '004_facturacion_residentes',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.
                            'plugins/residentes/view/js/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '005_facturacion_residentes',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.
                            'plugins/residentes/view/js/bootstrap-wysihtml5/locales/bootstrap-wysihtml5.es-ES.js" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '006_facturacion_residentes',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'head',
                'text' => '<link rel="stylesheet" type="text/css" media="screen" href="' . FS_PATH .
                    'plugins/residentes/view/js/bootstrap-wysihtml5/bootstrap3-wysihtml5.css"/>',
                'params' => ''
            ),

        );

        foreach ($extensiones as $ext) {
            $fsext0 = new fs_extension($ext);
            if (!$fsext0->save()) {
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }
    
    public function tratarAccion()
    {
        $accion = $this->filter_request('accion');
        switch ($accion) {
            case "nueva_programacion":
                $this->template = 'block/nueva_programacion_facturacion';
                break;
            case "configurar_metodo_envio":
                $this->template = 'block/configurar_metodo_envio';
                break;
            case "guardar_configuracion_envio":
                $this->guardarConfiguracionMetodoEnvio();
                break;
            case "guardar_programacion":
                $this->guardarProgramacion();
                break;
            case "eliminar_programacion":
                $this->eliminarProgramacion();
                break;
            case "reiniciar_programacion":
                $this->reiniciarProgramacion();
                break;
            case "lista_programacion":
                $this->listaProgramacion();
                break;
            case "test_email":
                $this->testEmail();
                break;
            default:
                break;
        }
    }
    
    public function listaProgramacion()
    {
        $idProgramacion = $this->filter_request('idprogramacion');
        $this->template = 'block/lista_programacion_facturacion';
        $rfpe = new residentes_facturacion_programada_edificaciones();
        $this->lista_programaciones = $rfpe->get_lista_edificaciones($idProgramacion);
        $this->idProg = $idProgramacion;
    }
    
    public function eliminarProgramacion()
    {
        $estado = false;
        $idProgramacion = $this->filter_request('idprogramacion');
        if ($this->user->allow_delete_on(__CLASS__) && isset($idProgramacion) && $idProgramacion !== '') {
            $programaciones = new residentes_facturacion_programada();
            $programa = $programaciones->get($idProgramacion);
            if ($programa) {
                $estado = $programa->delete();
            }
        }
        if ($estado === true) {
            $this->new_message('Programaci&oacute;n eliminada correctamente.');
        } else {
            $this->new_error_msg('La Programaci&oacute;n no pudo ser eliminada, verifique los datos o sus permisos.');
        }
    }
    
    public function reiniciarProgramacion()
    {
        $estado = false;
        $idProgramacion = $this->filter_request('idprogramacion');
        if ($this->user->allow_delete_on(__CLASS__) && isset($idProgramacion) && $idProgramacion !== '') {
            $programaciones = new residentes_facturacion_programada();
            $programa = $programaciones->get($idProgramacion);
            if ($programa) {
                $programa->eliminar_facturas();
                $programa->estado = 'ENCOLA';
                $programa->facturas_generadas = 0;
                $estado = $programa->save();
            }
        }
        if ($estado === true) {
            $this->new_message('Programaci&oacute;n reiniciada correctamente.');
        } else {
            $this->new_error_msg('La Programaci&oacute;n no pudo ser reiniciada, verifique los datos o sus permisos.');
        }
    }

    public function guardarConfiguracionMetodoEnvio()
    {
        $this->template = 'block/configurar_metodo_envio';
        $id = $this->filter_request('id');
        $tiposervicio = $this->filter_request('tiposervicio');
        $apikey = $this->filter_request('apikey');
        $apisecret = $this->filter_request('apisecret');
        $apisenderemail = $this->filter_request('apisenderemail');
        $apisendername = $this->filter_request('apisendername');
        $emailsubject = stripslashes(htmlentities($this->filter_request('emailsubject')));
        $remcfg = new residentes_email_config();
        $remcfg->id = $id;
        $remcfg->tiposervicio = $tiposervicio;
        $remcfg->apikey = $apikey;
        $remcfg->apisecret = $apisecret;
        $remcfg->apisenderemail = $apisenderemail;
        $remcfg->apisendername = $apisendername;
        $remcfg->emailsubject = $emailsubject;
        $remcfg->fecha_creacion = \date('Y-m-d h:i:s');
        $remcfg->usuario_creacion = $this->user->nick;
        $remcfg->fecha_modificacion = \date('Y-m-d h:i:s');
        $remcfg->usuario_modificacion = $this->user->nick;
        return $remcfg->save();
        $this->new_message('Configuración de envio guardada.');
    }

    public function testEmail()
    {
        $this->template = false;
        header('Content-Type: application/json');
        $cfg = new residentes_email_config();
        $actualConfig = $cfg->currentConfig();
        $envioMails = new ResidentesEnviarMail();
        switch($actualConfig->tiposervicio) {
            case "mailjet":
                echo $envioMails->mailjetEmailTest();
                break;
            case "sendgrid":
                echo $envioMails->sendgridEmailTest();
                break;
            case "interno": default:
                echo $envioMails->internalEmailTest();
                break;
        }

    }
    
    public function guardarProgramacion()
    {
        $residentesProcesar = $this->cantidadResidentesProcesar();
        $cantidadResidentesProcesar  = count($residentesProcesar);
        $idProgramacion = $this->cabeceraProgramacion($cantidadResidentesProcesar);
        
        if ($idProgramacion) {
            $this->detalleProgramacion($idProgramacion);
            $this->edificacionesProgramacion($idProgramacion, $residentesProcesar);
        }
        $this->new_message('¡Programaci&oacute;n generada!');
    }
    
    private function cabeceraProgramacion($cantidadResidentesProcesar)
    {
        //Cabecera de Programacion
        $id = $this->filter_request('id');
        $descripcion = $this->filter_request('descripcion');
        $tipo_programacion = $this->filter_request('tipo_programacion');
        $forma_pago = $this->filter_request('forma_pago');
        $fecha_vencimiento = $this->filter_request('fecha_vencimiento');
        $fecha_envio = $this->filter_request('fecha_envio');
        $hora_envio = $this->filter_request('hora_envio');
        
        $rfp = new residentes_facturacion_programada();
        $rfp->id = (isset($id) && $id !== '') ? $id : null;
        $rfp->descripcion = htmlentities(trim($descripcion));
        $rfp->tipo_programacion = $tipo_programacion;
        $rfp->forma_pago = $forma_pago;
        $rfp->fecha_vencimiento = $fecha_vencimiento;
        $rfp->fecha_envio = $fecha_envio;
        $rfp->hora_envio = $hora_envio;
        $rfp->residentes_facturar = $cantidadResidentesProcesar;
        $rfp->estado = 'ENCOLA';
        $rfp->fecha_creacion = \date('Y-m-d h:i:s');
        $rfp->usuario_creacion = $this->user->nick;
        $rfp->fecha_modificacion = \date('Y-m-d h:i:s');
        $rfp->usuario_modificacion = $this->user->nick;
        
        return $rfp->save();
    }
    
    public function detalleProgramacion($idProgramacion)
    {
        $referencias = $this->filter_request_array('referencia');
        $cantidades = $this->filter_request_array('cantidad');
        $pvps = $this->filter_request_array('pvp');
        $impuestos = $this->filter_request_array('impuesto');
        $importes = $this->filter_request_array('importe');
        foreach ($referencias as $id => $referencia) {
            $rfpc = new residentes_facturacion_programada_conceptos();
            $rfpc->idprogramacion = $idProgramacion;
            $rfpc->referencia = $referencia;
            $rfpc->cantidad = $cantidades[$id];
            $rfpc->pvp = $pvps[$id];
            $rfpc->codimpuesto = $impuestos[$id];
            $rfpc->importe = $importes[$id];
            if (!$rfpc->save()) {
                $this->new_error_msg('¡Ocurri&oacute; un error al grabar el concepto con codigo: '.$referencia);
            }
        }
        return true;
    }
    
    public function edificacionesProgramacion($idProgramacion, $residentesProcesar)
    {
        $edificaciones_residentes = new residentes_edificaciones();
        foreach ($residentesProcesar as $codcliente => $datos) {
            $data_edificacion = $edificaciones_residentes->get_by_field('codcliente', $codcliente);
            $rfpe = new residentes_facturacion_programada_edificaciones();
            $rfpe->idprogramacion = $idProgramacion;
            $rfpe->codcliente = $codcliente;
            $rfpe->id_edificacion = $data_edificacion[0]->id;
            $rfpe->save();
        }
    }
    
    private function cantidadResidentesProcesar()
    {
        $listaResidentes = [];
        $iEdificaciones = $this->filter_request_array('edificacion');
        $edificaciones_mapa = new residentes_edificaciones_mapa();
        foreach ($iEdificaciones as $edificacion_id) {
            $listaEdificaciones = $edificaciones_mapa->get_by_field('padre_id', $edificacion_id);
            foreach ($listaEdificaciones as $edificacion) {
                $this->cargarResidentesEdificacion($edificacion->id, $listaResidentes);
            }
        }
        return $listaResidentes;
    }
    
    private function cargarResidentesEdificacion($edificacion, &$listaResidentes)
    {
        $residentesDisponibles = [];
        $edificaciones_residentes = new residentes_edificaciones();
        $edificacionesDisponibles = $edificaciones_residentes->get_by_field('id_edificacion', $edificacion);
        foreach ($edificacionesDisponibles as $edif) {
            if ($edif->ocupado === true) {
                $residentesDisponibles[] = ['id_edificacion'=>$edif->id_edificacion,'codcliente'=>$edif->codcliente];
            }
        }
        $this->cargarResidentesFacturables($residentesDisponibles, $listaResidentes);
    }
    
    private function cargarResidentesFacturables($residentesDisponibles, &$listaResidentes)
    {
        foreach ($residentesDisponibles as $datosResidente) {
            $this->generarPrefacturacion($datosResidente['codcliente'], $listaResidentes);
        }
    }
    
    private function generarPrefacturacion($codcliente, &$listaResidentes)
    {
        $listaReferencias = $this->filter_request_array('referencia');
        foreach ($listaReferencias as $referencia) {
            $sql = "SELECT count(referencia) as facturado from lineasfacturascli where referencia = '".$referencia."' ".
               " AND idfactura IN (select idfactura from facturascli WHERE codcliente = '".$codcliente."');";
            $data = $this->db->select($sql);
            if (!$data[0]['facturado']) {
                $listaResidentes[$codcliente][] = $referencia;
            }
        }
    }
}
