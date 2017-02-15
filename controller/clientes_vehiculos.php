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
require_model('cliente.php');
require_model('residentes_vehiculos.php');
/**
 * Description of clientes_vehiculos
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class clientes_vehiculos extends fs_controller {
    public $allow_delete;
    public $codcliente;
    public $cliente;
    public $clientes;
    public $vehiculos_cliente;
    public $residentes_vehiculos;
    public function __construct() {
        parent::__construct(__CLASS__, 'Vehiculos Residente', 'residentes', FALSE, FALSE, FALSE);
    }

    protected function private_core() {
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->shared_extensions();
        $this->clientes = new cliente();
        $this->residentes_vehiculos = new residentes_vehiculos();

        $accion = \filter_input(INPUT_POST, 'accion');
        if(!empty($accion)){
            $idvehiculo = \filter_input(INPUT_POST, 'idvehiculo');
            $codcliente_p = \filter_input(INPUT_POST, 'codcliente');
            $vehiculo_marca = $this->clean_text(\filter_input(INPUT_POST, 'vehiculo_marca'));
            $vehiculo_modelo = $this->clean_text(\filter_input(INPUT_POST, 'vehiculo_modelo'));
            $vehiculo_color = $this->clean_text(\filter_input(INPUT_POST, 'vehiculo_color'));
            $vehiculo_placa = $this->clean_text(\filter_input(INPUT_POST, 'vehiculo_placa'));
            $vehiculo_tipo = $this->clean_text(\filter_input(INPUT_POST, 'vehiculo_tipo'));
            $codigo_tarjeta = $this->clean_text(\filter_input(INPUT_POST, 'codigo_tarjeta'));
            $vehiculo = new residentes_vehiculos();
            $vehiculo->idvehiculo = $idvehiculo;
            $vehiculo->codcliente = $codcliente_p;
            $vehiculo->vehiculo_marca = $vehiculo_marca;
            $vehiculo->vehiculo_modelo = $vehiculo_modelo;
            $vehiculo->vehiculo_color = $vehiculo_color;
            $vehiculo->vehiculo_placa = $vehiculo_placa;
            $vehiculo->vehiculo_tipo = $vehiculo_tipo;
            $vehiculo->codigo_tarjeta = $codigo_tarjeta;
            if($accion=='agregar'){
                if($vehiculo->save()){
                    $this->new_message('Vehiculo agregado exitosamente');
                }else{
                    $this->new_error_msg('No se pudieron agregar los datos del vehiculo, revise la información e intentelo nuevamente.');
                }
            }elseif($accion=='eliminar'){
                if($vehiculo->delete()){
                    $this->new_message('Vehiculo eliminado exitosamente');
                }else{
                    $this->new_error_msg('No se pudieron eliminar los datos del vehiculo, revise la información e intentelo nuevamente.');
                }
            }
        }

        $codcliente = \filter_input(INPUT_GET, 'cod');
        if(!empty($codcliente)){
            $this->codcliente = $codcliente;
            $this->cliente = $this->clientes->get($codcliente);
            $this->cliente_vehiculos = $this->residentes_vehiculos->get_by_field('codcliente',$this->codcliente);
        }
    }

    public function clean_text($text){
        return strtoupper(htmlentities(strip_tags(trim($text))));
    }

    private function shared_extensions() {
        $extensiones = array(
            array(
                'name' => 'vehiculos_residente',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_cliente',
                'type' => 'tab',
                    'text' => '<span class="fa fa-car" aria-hidden="true"></span>&nbsp;Residente Vehiculos',
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
}
