<?php

/*
 * Copyright (C) 2016 Joe Nilson <joenilson at gmail.com>
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
require_model('residentes_edificaciones_tipo.php');
/**
 * Description of edificaciones
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class edificaciones extends fs_controller{
    public $edificaciones_tipo;
    public $allow_delete;
    public function __construct() {
        parent::__construct(__CLASS__, 'Edificaciones', 'residentes', FALSE, TRUE, FALSE);
    }

    protected function private_core() {

        $this->shared_extensions();
        $this->allow_delete = ($this->user->admin)?TRUE:$this->user->allow_delete_on(__CLASS__);


        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
    }

    public function shared_extensions(){
        $extensiones = array(
            array(
                'name' => 'tipo_edificaciones',
                'page_from' => __CLASS__,
                'page_to' => __CLASS__,
                'type' => 'button',
                'text' => '<span class="fa fa-list-ol"></span>&nbsp;Tipo Edificaciones',
                'params' => '&tipo_edificaciones=TRUE'
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
