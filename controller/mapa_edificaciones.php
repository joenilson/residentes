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
require_model('residentes_edificaciones_tipo.php');
require_model('residentes_edificaciones_mapa.php');
/**
 * Description of mapa_edificaciones
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class mapa_edificaciones extends fs_controller{
    public $edificaciones_tipo;
    public $edificaciones_mapa;
    public $padre;
    public $mapa;
    public function __construct() {
        parent::__construct(__CLASS__, 'Mapa de Edificaciones', 'residentes', FALSE, FALSE, FALSE);
    }

    protected function private_core() {
        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
        $this->edificaciones_mapa = new residentes_edificaciones_mapa();
        $tipos = $this->edificaciones_tipo->all();
        $this->padre = $tipos[0];

        $accion = \filter_input(INPUT_POST, 'accion');
        if($accion == 'agregar_base'){
            $inicio = \filter_input(INPUT_POST, 'inicio');
            $final_p = \filter_input(INPUT_POST, 'final');
            $final=(!empty($final_p))?$final_p:$inicio;
            foreach(range($inicio,$final) as $item){
                $punto = new residentes_edificaciones_mapa();
                $punto->id_tipo = $this->padre->id;
                $punto->codigo_edificacion = $item;
                $punto->padre_tipo = $this->padre->padre;
                $punto->numero = '';
                $punto->save();
            }
        }
        $this->mapa = $this->edificaciones_mapa->get_by_field('id_tipo', $this->padre->id);
    }
}
