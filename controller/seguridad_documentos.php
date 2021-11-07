<?php
/*
 * Copyright (C) 2021 Joe Nilson <joenilson@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'plugins/residentes/extras/residentes_controller.php';

class seguridad_documentos extends residentes_controller
{
    public function __construct()
    {
        parent::__construct(__CLASS__, 'Seguridad Documentos', 'admin', true, false, false);
    }

    protected function private_core()
    {
        $this->shared_extensions();
    }

    private function shared_extensions()
    {
        $extensiones = array(
            array(
                'name' => '001_seguridad_doc_venta_js',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_factura',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/residentes/view/js/seguridad_documentos.js?cod='.
                    rand(1000,10000).'" type="text/javascript"></script>',
                'params' => ''
            ),
            array(
                'name' => '002_seguridad_doc_compra_js',
                'page_from' => __CLASS__,
                'page_to' => 'compras_factura',
                'type' => 'head',
                'text' => '<script src="'.FS_PATH.'plugins/residentes/view/js/seguridad_documentos.js?cod='.
                    rand(1000,10000).'" type="text/javascript"></script>',
                'params' => ''
            ),
        );

        foreach($extensiones as $del){
            $fext = new fs_extension($del);
            if(!$fext->save()){
                $this->new_error_msg('Imposible guardar los datos de la extensión ' . $ext['name'] . '.');
            }
        }
    }

}