<?php

/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */
require_model('cliente.php');

require_model('residentes_edificaciones.php');

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

    public function __construct() {
        parent::__construct(__CLASS__, 'Residentes', 'residentes', FALSE, TRUE);
    }

    protected function private_core() {
        $this->bloque = '';
        $this->cliente = new cliente();
        $this->residente = new residentes_edificaciones();

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
        } elseif (isset($_REQUEST['buscar_inmueble'])) {
            $this->buscar_inmueble();
        } elseif (isset($_GET['delete'])) {
            $inq = $this->residente->get($_GET['delete']);
            if ($inq) {
                if ($inq->delete()) {
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
            $inmueble->ocupado = 'TRUE';
            $inmueble->codcliente = $codcliente;
            $inmueble->fecha_ocupacion = ($fecha_ocupacion)?\date('Y-m-d',strtotime($fecha_ocupacion)):NULL;
            $inmueble->fecha_disponibilidad = ($fecha_disponibilidad)?\date('Y-m-d',strtotime($fecha_disponibilidad)):NULL;
            if($inmueble->save()){
                $this->new_message('Residente agregado exitosamente.');
            }else{
                $this->new_error_msg('No se pudo agregar al residente confirme el nombre del residente y las fechs de ocupación y disponibilidad');
            }
        }elseif($inmueble AND $accion == 'quitar_residente'){
            $inmueble->ocupado = 'FALSE';
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
