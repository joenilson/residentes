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
/**
 * Description of mapa_edificaciones
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class mapa_edificaciones extends fs_controller{
    public $edificaciones_tipo;
    public function __construct() {
        parent::__construct(__CLASS__, 'Mapa de Edificaciones', 'residentes', FALSE, FALSE, FALSE);
    }

    protected function private_core() {
        $this->edificaciones_tipo = new residentes_edificaciones_tipo();
    }
}