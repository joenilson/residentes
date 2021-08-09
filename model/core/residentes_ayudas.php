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
namespace FacturaScripts\model;
/**
 * Description of residentes_ayudas
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class residentes_ayudas extends \fs_model{
    /**
     * El codigo de la ayuda ya sea para parentesco u ocupacion
     * @var string
     */
    public $codigo;
    /**
     * El tipo de la ayuda PARENTESCO | OCUPACION
     * @var string
     */
    public $tipo;
    /**
     * Esta es la descripción de la ayuda
     * @var string
     */
    public $descripcion;
    public function __construct($t = FALSE) {
        parent::__construct('residentes_ayudas','plugins/residentes');
        if($t){
            $this->codigo = $t['codigo'];
            $this->tipo = $t['tipo'];
            $this->descripcion = $t['descripcion'];
        }else{
            $this->codigo = null;
            $this->tipo = null;
            $this->descripcion = null;
        }
    }

    public function install(){
        return "insert into residentes_ayudas (codigo,tipo, descripcion) VALUES ".
            "('EMPPUB','OCUPACION','Empleado Publico'),".
            "('EMPRI','OCUPACION','Empleado Privado'),".
            "('NEGPRO','OCUPACION','Negocio Propio'),".
            "('PROIND','OCUPACION','Profesional Independiente'),".
            "('PADRE','PARENTESCO','Padre'),".
            "('MADRE','PARENTESCO','Madre'),".
            "('ESPOSO','PARENTESCO','Esposo'),".
            "('ESPOSA','PARENTESCO','Esposa'),".
            "('HERMANO','PARENTESCO','Hermano (a)'),".
            "('HIJO','PARENTESCO','HermanHijo (a)');";
    }

    public function all(){
        $sql = "SELECT * FROM ".$this->table_name." ORDER BY tipo,codigo";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $lista[] = new residentes_ayudas($d);
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function all_tipo($tipo){
        $sql = "SELECT * FROM ".$this->table_name." WHERE tipo = ".$this->var2str($tipo)." ORDER BY codigo";
        $data = $this->db->select($sql);
        if($data){
            $lista = array();
            foreach($data as $d){
                $lista[] = new residentes_ayudas($d);
            }
            return $lista;
        }else{
            return false;
        }
    }

    public function get($codigo,$tipo){
        $sql = "SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($codigo)." AND tipo = ".$this->var2str($tipo).";";
        $data = $this->db->select($sql);
        if($data){
            return new residentes_ayudas($data[0]);
        }else{
            return false;
        }
    }

    public function exists() {
        if(is_null($this->codigo)){
            return false;
        }else{
            return $this->get($this->codigo,$this->tipo);
        }
    }

    public function save() {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
                    "descripcion = ".$this->var2str($this->descripcion)." ".
                    "WHERE codigo = ".$this->var2str($this->codigo)." AND tipo = ".$this->var2str($this->tipo).";";
            return $this->db->exec($sql);
        }else{
            $sql = "INSERT INTO ".$this->table_name." (codigo, tipo, descripcion) VALUES (".
                    $this->var2str($this->codigo).", ".
                    $this->var2str($this->tipo).", ".
                    $this->var2str($this->descripcion).");";
            if($this->db->exec($sql)){
                return $this->db->lastval();
            }else{
                return false;
            }
        }
    }
    public function delete() {
        $sql = "DELETE FROM ".$this->table_name." WHERE codigo = ".$this->var2str($this->codigo)." AND tipo = ".$this->var2str($this->tipo).";";
        return $this->db->exec($sql);
    }

}
