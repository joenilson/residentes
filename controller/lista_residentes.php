<?php

/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */
require_model('cliente.php');

require_model('residentes_edificaciones.php');
require_model('residentes_informacion.php');
require_model('residentes_vehiculos.php');

/**
 * Description of lista_residentes
 *
 * @author carlos <neorazorx@gmail.com>
 * @author Joe Nilson <joenilson at gmail.com>
 */
class lista_residentes extends fs_controller {

    public $bloque;
    public $cliente;
    public $residente;
    public $offset;
    public $resultados;
    public $residente_informacion;
    public $residente_vehiculo;
    public function __construct() {
        parent::__construct(__CLASS__, 'Residentes', 'residentes', FALSE, TRUE);
    }

    protected function private_core() {
        $this->bloque = '';
        $this->cliente = new cliente();
        $this->residente = new residentes_edificaciones();
        $this->residente_informacion = new residentes_informacion();
        $this->residente_vehiculo = new residentes_vehiculos();
        $this->offset = 0;
        if (isset($_REQUEST['offset'])) {
            $this->offset = intval($_REQUEST['offset']);
        }

        $accion = filter_input(INPUT_POST, 'accion');
        switch ($accion) {
            case "agregar_residente":
                $this->agregar_residente();
                break;
            default:
                break;
        }

        if (isset($_REQUEST['buscar_cliente'])) {
            $this->buscar_cliente();
        } elseif (isset($_REQUEST['buscar_cliente_avanzado'])) {
            $this->buscar_cliente_avanzado();
        } elseif (isset($_REQUEST['buscar_inmueble'])) {
            $this->buscar_inmueble();
        } elseif (isset($_GET['delete'])) {
            $inq = $this->residente->get($_GET['delete']);
            if ($inq) {
                $inq->ocupado = FALSE;
                $inq->codcliente = '';
                $inq->fecha_disponibilidad = NULL;
                $inq->fecha_ocupacion = NULL;
                if ($inq->save()) {
                    $this->new_message('Inquilino eliminado correctamente.');
                } else
                    $this->new_error_msg('Error al eliminar el inquilino.');
            } else
                $this->new_error_msg('Inquilino no encontrado.');
        }

        if (isset($_GET['cliente'])) {
            $this->resultados = $this->residente->all_from_cliente($_GET['cliente']);
        } else {
            $this->resultados = $this->residente->all_ocupados();
        }
    }

    public function agregar_residente(){
        $id_edificacion = \filter_input(INPUT_POST, 'id_edificacion');
        $codcliente = \filter_input(INPUT_POST, 'codcliente');
        $fecha_ocupacion = \filter_input(INPUT_POST, 'fecha_ocupacion');
        $fecha_disponibilidad = \filter_input(INPUT_POST, 'fecha_disponibilidad');
        $accion = \filter_input(INPUT_POST, 'accion');
        $inmueble = $this->residente->get($id_edificacion);
        if($inmueble AND $accion == 'agregar_residente'){
            $inmueble->ocupado = TRUE;
            $inmueble->codcliente = $codcliente;
            $inmueble->fecha_ocupacion = ($fecha_ocupacion)?\date('Y-m-d',strtotime($fecha_ocupacion)):NULL;
            $inmueble->fecha_disponibilidad = ($fecha_disponibilidad)?\date('Y-m-d',strtotime($fecha_disponibilidad)):NULL;
            if($inmueble->save()){
                $this->new_message('Residente agregado exitosamente.');
            }else{
                $this->new_error_msg('No se pudo agregar al residente confirme el nombre del residente y las fechs de ocupación y disponibilidad');
            }
        }elseif($inmueble AND $accion == 'quitar_residente'){
            $inmueble->ocupado = FALSE;
            $inmueble->codcliente = '';
            $inmueble->fecha_ocupacion = '';
            $inmueble->fecha_disponibilidad = '';
            if($inmueble->save()){
                $this->new_message('Residente removido exitosamente.');
            }else{
                $this->new_error_msg('No se pudo remover al residente');
            }
        }
    }

    private function buscar_cliente() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $json = array();
        foreach ($this->cliente->search($_REQUEST['buscar_cliente']) as $cli) {
            $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json));
    }

    private function buscar_cliente_avanzado() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $json = array();
        //Buscamos en la lista de clientes
        foreach ($this->cliente->search($_REQUEST['buscar_cliente_avanzado']) as $cli) {

            $lista = $this->residente->get_by_field('codcliente', $cli->codcliente);
            if($lista){
                foreach($lista as $residente){
                    $json[$cli->codcliente] = array('value' => $cli->nombre, 'data' => $cli->codcliente, 'nombre' => $cli->nombre, 'asignado' => true);
                }
            }else{
                $json[$cli->codcliente] = array('value' => $cli->nombre, 'data' => $cli->codcliente, 'nombre' => $cli->nombre, 'asignado' => false);
            }
        }
        //Buscamos en los datos adicionales del residente
        foreach ($this->residente_informacion->search($_REQUEST['buscar_cliente_avanzado']) as $cli) {
            if(!empty($cli)){
                $json[$cli->codcliente] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
            }
        }
        //Buscamos en los datos de vehiculos del residente
        foreach ($this->residente_vehiculo->search($_REQUEST['buscar_cliente_avanzado']) as $cli){
            if(!empty($cli)){
                $json[$cli->codcliente] = array('value' => $cli->nombre.' '.$cli->vehiculo_placa." ".$cli->vehiculo_marca.''.$cli->vehiculo_modelo, 'data' => $cli->codcliente);
            }
        }

        //Buscamos en las residencias
        foreach($this->residente->search($_REQUEST['buscar_cliente_avanzado']) as $cli){
            if(!empty($cli)){
                $json[$cli->codcliente] = array('value' => $cli->nombre." ".$cli->codigo.' '.$cli->numero, 'data' => $cli->id, 'asignado' => true);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_cliente_avanzado'], 'suggestions' => $json));
    }

    private function buscar_inmueble() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $json = array();
        foreach ($this->residente->search($_REQUEST['buscar_inmueble']) as $inmueble) {
            if(!$inmueble->ocupado){
                $json[] = array('value' => $inmueble->codigo.$inmueble->numero, 'data' => $inmueble->id);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_inmueble'], 'suggestions' => $json));
    }

    public function anterior_url() {
        $url = '';

        if ($this->offset > '0') {
            $url = $this->url() . "&offset=" . ($this->offset - FS_ITEM_LIMIT);
        }

        return $url;
    }

    public function siguiente_url() {
        $url = '';

        if (count($this->resultados) == FS_ITEM_LIMIT) {
            $url = $this->url() . "&offset=" . ($this->offset + FS_ITEM_LIMIT);
        }

        return $url;
    }

}
