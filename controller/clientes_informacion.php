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
require_model('residentes_informacion.php');
/**
 * Controller para el tab de información adicional de los resodientes
 * Va agregado en Ventas > Clientes
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class clientes_informacion extends fs_controller{
    public $allow_delete;
    public $codcliente;
    public $cliente;
    public $clientes;
    public function __construct() {
        parent::__construct(__CLASS__, 'Informacion Residente', 'residentes', FALSE, FALSE, FALSE);
    }

    protected function private_core() {
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
        $this->shared_extensions();
        $this->clientes = new cliente();
        $codcliente = \filter_input(INPUT_GET, 'cod');
        if(!empty($codcliente)){
            $this->codcliente = $codcliente;
            $this->cliente = $this->clientes->get($codcliente);
        }
    }

    private function shared_extensions() {
        $extensiones = array(
            array(
                'name' => 'informacion_residente',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_cliente',
                'type' => 'tab',
                'text' => '<span class="fa fa-building" aria-hidden="true"></span>&nbsp;Residente Info',
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
