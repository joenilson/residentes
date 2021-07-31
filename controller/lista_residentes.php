<?php
/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
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
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */
require_once 'plugins/residentes/extras/residentes_controller.php';
/**
 * Description of lista_residentes
 *
 * @author carlos <neorazorx@gmail.com>
 * @author Joe Nilson <joenilson at gmail.com>
 */
class lista_residentes extends residentes_controller
{
    public $bloque;
    public $cliente;
    public $deudores;
    public $residente;
    public $query;
    public $query_r;
    public $query_i;
    public $query_v;
    public $order;
    public $sort;
    public $offset;
    public $resultados;
    public $total_resultados;
    public $edificacion_tipo;
    public $edificacion_mapa;
    public $tipo_edificaciones;
    public $residente_informacion;
    public $residente_vehiculo;
    public $cli;
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Residentes', 'residentes', false, true);
    }

    protected function private_core()
    {
        parent::private_core();
        $this->init_variables();
        $this->filters();

        if ($this->filter_request('buscar_cliente')) {
            $this->buscar_cliente();
        } elseif ($this->filter_request('buscar_cliente_avanzado')) {
            $this->buscar_cliente_avanzado();
        } elseif ($this->filter_request('buscar_inmueble')) {
            $this->buscar_inmueble();
        } elseif (filter_input(INPUT_GET, 'delete')) {
            $inq = $this->residente->get(filter_input(INPUT_GET, 'delete'));
            if ($inq) {
                $inq->ocupado = false;
                $inq->codcliente = '';
                $inq->fecha_disponibilidad = null;
                $inq->fecha_ocupacion = null;
                if ($inq->save()) {
                    $this->new_message('Inquilino removido correctamente.');
                } else
                    $this->new_error_msg('Error al remover el inquilino.');
            } else
                $this->new_error_msg('Inquilino no encontrado.');
        }
        
        $tipo = $this->filter_request('type');
        if ($tipo === 'select-iddireccion' ) {
            $this->mostrar_direcciones_residente(\filter_input(INPUT_GET, 'codcliente'));
        }

        $accion = $this->filter_request('accion');
        switch ($accion) {
            case "agregar_residente":
                $this->agregar_residente();
                break;
            case "mostrar_informacion_residente":
                $this->mostrar_informacion_residente();
                break;
            default:
                $this->buscar();
                break;
        }
    }


    public function init_variables()
    {
        $this->bloque = '';
        $this->cliente = new cliente();
        $this->residente = new residentes_edificaciones();
        $this->residente_informacion = new residentes_informacion();
        $this->residente_vehiculo = new residentes_vehiculos();
        $this->edificacion_tipo = new residentes_edificaciones_tipo();
        $this->edificacion_mapa = new residentes_edificaciones_mapa();
        $this->tipo_edificaciones = $this->edificacion_tipo->all();
        $this->offset = 0;
    }

    public function filters()
    {
        $this->query_r = '';
        if ($this->filter_request('query_r')) {
            $this->query_r = $this->filter_request('query_r');
        }

        $this->query_v = '';
        if ($this->filter_request('query_v')) {
            $this->query_v = $this->filter_request('query_v');
        }

        $this->query_i = '';
        if ($this->filter_request('query_i')) {
            $this->query_i = $this->filter_request('query_i');
        }

        $this->sort = 'ASC';
        if ($this->filter_request('sort')) {
            $this->sort = $this->filter_request('sort');
        }

        $this->order = 'r.codigo, r.numero ';
        if ($this->filter_request('orden')) {
            $this->order = $this->filter_request('orden');
        }

        $this->offset = $this->filter_request('offset');

        $this->deudores = $this->filter_request('deudores');
        if ($this->deudores) {
            $this->sort = 'DESC';
            $this->order = 'pendiente';
        }
        
        $this->disponibles = $this->filter_request('disponibles');
        if ($this->disponibles) {
            $this->sort = 'ASC';
            $this->order = 'codcliente';
        }
    }

    public function buscar()
    {
        $where = "";
        if ($this->query_r) {
            $param = mb_strtolower($this->cliente->no_html($this->query_r), 'UTF8');
            $where = " WHERE ".$this->buscar_residentes($param);
        }

        if ($this->query_v) {
            $param = mb_strtolower($this->cliente->no_html($this->query_v), 'UTF8');
            $where = " WHERE ".$this->buscar_vehiculos($param);
        }

        if ($this->query_i) {
            $param = mb_strtolower($this->cliente->no_html($this->query_i), 'UTF8');
            $where = " WHERE ".$this->buscar_inmuebles($param);
        }

        list($this->resultados, $this->total_resultados) = $this->residente->lista_residentes(
            $where,
            $this->order,
            $this->sort,
            FS_ITEM_LIMIT,
            $this->offset
        );
    }

    public function buscar_residentes($param)
    {
        if (is_numeric($param)) {
            $where = "(r.codcliente LIKE '%" . $param . "%'"
                    . " OR c.cifnif LIKE '%" . $param . "%'"
                    . " OR c.telefono1 LIKE '" . $param . "%'"
                    . " OR c.telefono2 LIKE '" . $param . "%'"
                    . " OR ca_telefono LIKE '" . $param . "%'"
                    . " OR r.observaciones LIKE '%" . $param . "%')";
        } else {
            $buscar = str_replace(' ', '%', $param);
            $where = "(lower(nombre) LIKE '%" . $buscar . "%'"
                    . " OR lower(razonsocial) LIKE '%" . $buscar . "%'"
                    . " OR lower(ca_apellidos) LIKE '%" . $buscar . "%'"
                    . " OR lower(ca_nombres) LIKE '%" . $buscar . "%'"
                    . " OR lower(cifnif) LIKE '%" . $buscar . "%'"
                    . " OR lower(observaciones) LIKE '%" . $buscar . "%'"
                    . " OR lower(ca_email) LIKE '%" . $buscar . "%'"
                    . " OR lower(email) LIKE '%" . $buscar . "%')";
        }
        return $where;
    }

    public function buscar_inmuebles($param)
    {
        if (is_numeric($param)) {
            $where = " r.codigo LIKE '%" . $param . "%'"
                    . " OR r.numero LIKE '%" . $param . "%'"
                    . " OR CONCAT(r.codigo, r.numero) LIKE '%" . $param . "%'";
        } else {
            $buscar = str_replace(' ', '%', $param);
            $where = " lower(r.codigo) LIKE '%" . $buscar . "%'"
                    . " OR lower(r.numero) LIKE '%" . $buscar . "%'"
                    . " OR CONCAT(lower(r.codigo), r.numero) LIKE '%" . $param . "%'";
        }
        return $where;
    }

    public function buscar_vehiculos($param)
    {
        if (is_numeric($param)) {
            $where = "(r.codcliente LIKE '%" . $param . "%'"
                    . " OR vehiculo_placa LIKE '%" . $param . "%'"
                    . " OR CAST(idvehiculo AS CHAR) LIKE '" . $param . "%'"
                    . " OR telefono2 LIKE '" . $param . "%'"
                    . " OR observaciones LIKE '%" . $param . "%')";
        } else {
            $buscar = str_replace(' ', '%', $param);
            $where = "(lower(vehiculo_marca) LIKE '%" . $buscar . "%'"
                    . " OR lower(vehiculo_modelo) LIKE '%" . $buscar . "%'"
                    . " OR lower(vehiculo_color) LIKE '%" . $buscar . "%'"
                    . " OR lower(vehiculo_placa) LIKE '%" . $buscar . "%'"
                    . " OR lower(vehiculo_tipo) LIKE '%" . $buscar . "%'"
                    . " OR lower(codigo_tarjeta) LIKE '%" . $buscar . "%')";
        }
        return $where;
    }

    public function agregar_residente(){
        $id_edificacion = \filter_input(INPUT_POST, 'id_edificacion');
        $codcliente = \filter_input(INPUT_POST, 'codcliente');
        $iddireccion = \filter_input(INPUT_POST, 'iddireccion');
        $fecha_ocupacion = \filter_input(INPUT_POST, 'fecha_ocupacion');
        $fecha_disponibilidad = \filter_input(INPUT_POST, 'fecha_disponibilidad');
        $accion = \filter_input(INPUT_POST, 'accion');
        $inmueble = $this->residente->get($id_edificacion);
        if ($inmueble && $accion === 'agregar_residente') {
            $nueva_direccion = $inmueble->codigo_externo().' Apartamento '.$inmueble->numero;
            $inmueble->ocupado = true;
            $inmueble->codcliente = $codcliente;
            $inmueble->iddireccion = $this->actualizar_direccion_residente($codcliente, $iddireccion, $nueva_direccion);
            $inmueble->fecha_ocupacion = ($fecha_ocupacion)?\date('Y-m-d', strtotime($fecha_ocupacion)):null;
            $inmueble->fecha_disponibilidad = ($fecha_disponibilidad)
                ?\date('Y-m-d', strtotime($fecha_disponibilidad))
                :null;
            if ($inmueble->save()) {
                $this->new_message('Residente agregado exitosamente.');
            } else {
                $this->new_error_msg('No se pudo agregar al residente confirme el nombre '.
                                    'del residente y las fechs de ocupación y disponibilidad');
            }
        } elseif ($inmueble and $accion === 'quitar_residente') {
            $inmueble->ocupado = false;
            $inmueble->codcliente = '';
            $inmueble->iddireccion = null;
            $inmueble->fecha_ocupacion = '';
            $inmueble->fecha_disponibilidad = '';
            if ($inmueble->save()) {
                $this->new_message('Residente removido exitosamente.');
            } else {
                $this->new_error_msg('No se pudo remover al residente');
            }
        }
    }

    private function buscar_cliente()
    {
        /// desactivamos la plantilla HTML
        $this->template = false;

        $json = array();
        foreach ($this->cliente->search($_REQUEST['buscar_cliente']) as $cli) {
            $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json), JSON_THROW_ON_ERROR);
    }

    private function buscar_cliente_avanzado()
    {
        /// desactivamos la plantilla HTML
        $this->template = false;
        $json = array();
        //Buscamos en la lista de clientes
        foreach ($this->cliente->search($_REQUEST['buscar_cliente_avanzado']) as $cli) {
            $lista = $this->residente->get_by_field('codcliente', $cli->codcliente);
            if ($lista) {
                foreach ($lista as $residente) {
                    $json[$cli->codcliente] = array('value' => $cli->nombre,
                        'data' => $cli->codcliente,
                        'nombre' => $cli->nombre,
                        'asignado' => true);
                }
            } else {
                $json[$cli->codcliente] = array('value' => $cli->nombre,
                    'data' => $cli->codcliente,
                    'nombre' => $cli->nombre,
                    'asignado' => false);
            }
        }
        //Buscamos en los datos adicionales del residente
        foreach ($this->residente_informacion->search($_REQUEST['buscar_cliente_avanzado']) as $cli) {
            if (!empty($cli)) {
                $json[$cli->codcliente] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
            }
        }
        //Buscamos en los datos de vehiculos del residente
        foreach ($this->residente_vehiculo->search($_REQUEST['buscar_cliente_avanzado']) as $cli) {
            if (!empty($cli)) {
                $json[$cli->codcliente] = array(
                    'value' => $cli->nombre.' '.$cli->vehiculo_placa." ".
                                $cli->vehiculo_marca.''.$cli->vehiculo_modelo, 'data' => $cli->codcliente);
            }
        }
        //Buscamos en las residencias
        foreach ($this->residente->search($_REQUEST['buscar_cliente_avanzado']) as $cli) {
            if (!empty($cli)) {
                $json[$cli->codcliente] = array('value' => $cli->nombre." ".$cli->codigo.' '.
                                            $cli->numero, 'data' => $cli->id, 'asignado' => true);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_cliente_avanzado'], 'suggestions' => $json),
            JSON_THROW_ON_ERROR);
    }

    private function buscar_inmueble()
    {
        /// desactivamos la plantilla HTML
        $this->template = false;

        $json = array();
        foreach ($this->residente->search($_REQUEST['buscar_inmueble'],'inmueble') as $inmueble) {
            if (!$inmueble->ocupado) {
                $json[] = array('value' => $inmueble->codigo.$inmueble->numero, 'data' => $inmueble->id);
            }
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_inmueble'], 'suggestions' => $json),
            JSON_THROW_ON_ERROR);
    }

    public function paginas()
    {
        $url = $this->url() . "&query=" . $this->query
                . "&query_r=" . $this->query_r
                . "&query_v=" . $this->query_v
                . "&query_i=" . $this->query_i
                . "&orden=" . $this->order
                . "&deudores=" . $this->deudores
                . "&disponibles=" . $this->disponibles;

        $paginas = array();
        $i = 0;
        $num = 0;
        $actual = 1;

        /// añadimos todas la página
        while ($num < $this->total_resultados) {
            $paginas[$i] = array(
                'url' => $url . "&offset=" . ($i * FS_ITEM_LIMIT),
                'num' => $i + 1,
                'actual' => ($num === $this->offset)
            );

            if ($num === $this->offset) {
                $actual = $i;
            }

            $i++;
            $num += FS_ITEM_LIMIT;
        }

        /// ahora descartamos
        foreach ($paginas as $j => $value) {
            $enmedio = (int)($i / 2);

            /**
             * descartamos todo excepto la primera, la última, la de enmedio,
             * la actual, las 5 anteriores y las 5 siguientes
             */
            if (($j>1 && $j<$actual-5 && $j !== $enmedio) || ($j > $actual + 5 && $j < $i - 1 && $j !== $enmedio)) {
                unset($paginas[$j]);
            }
        }

        if (count($paginas) > 1) {
            return $paginas;
        } else {
            return array();
        }
    }

}
