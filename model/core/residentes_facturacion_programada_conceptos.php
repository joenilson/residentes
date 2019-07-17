<?php

/*
 * Copyright (C) 2019 joenilson.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace FacturaScripts\model;

/**
 * Description of residentes_facturacion_programada_conceptos
 *
 * @author joenilson
 */
class residentes_facturacion_programada_conceptos extends \fs_model
{
    public $id;
    public $idprogramacion;
    public $referencia;
    public $pvp;
    public $cantidad;
    public $codimpuesto;
    public $importe;
    
    public function __construct($t = FALSE) {
        parent::__construct('residentes_fact_prog_conceptos','plugins/residentes');
        if($t){
            $this->id = $t['id'];
            $this->idprogramacion = $t['idprogramacion'];
            $this->referencia = $t['referencia'];
            $this->pvp = floatval($t['pvp']);
            $this->cantidad = floatval($t['cantidad']);
            $this->codimpuesto = $t['codimpuesto'];
            $this->importe = floatval($t['importe']);
        }else{
            $this->id = null;
            $this->idprogramacion = null;
            $this->referencia = '';
            $this->pvp = 0;
            $this->cantidad = 0;
            $this->codimpuesto = null;
            $this->importe = 0;
        }
    }

    public function install(){
        return "";
    }
    
    public function exists()
    {
        if(is_null($this->id)) {
            return false;
        }
        return true;
    }
    
    public function save()
    {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
            "referencia = ".$this->var2str($this->referencia).", ".
            "pvp = ".$this->var2str($this->pvp).", ".
            "cantidad = ".$this->var2str($this->cantidad).", ".
            "codimpuesto = ".$this->var2str($this->codimpuesto).", ".
            "importe = ".$this->var2str($this->importe)." ".
            "WHERE id = ".$this->intval($this->id).";";
            $data = $this->db->exec($sql);
            return $data;
        }else{
            $sql = "INSERT INTO ".$this->table_name.
            " (idprogramacion, referencia, pvp, cantidad, codimpuesto, importe) VALUES (".
            $this->intval($this->idprogramacion).", ".
            $this->var2str($this->referencia).", ".
            $this->var2str($this->pvp).", ".
            $this->var2str($this->cantidad).", ".
            $this->var2str($this->codimpuesto).", ".
            $this->var2str($this->importe).");";
            
            if($this->db->exec($sql)){
                return true;
            }else{
                return false;
            }
        }
        
    }
    
    public function get($id)
    {
        $sql = "select * from ".$this->table_name." WHERE id = ".$this->intval($id);
        $data = $this->db->select($sql);
        if($data) {
            return new residentes_facturacion_programada_conceptos($data[0]);
        }
        return false;
    }
    
    public function getConceptosByIdProgramacion($idProg) 
    {
        $sql = "select * from ".$this->table_name." WHERE idprogramacion = ".$this->intval($idProg)." ORDER BY id";
        $data = $this->db->select($sql);
        $lista = array();
        if($data) {
            foreach($data as $d) {
                $lista[] = new residentes_facturacion_programada_conceptos($d);
            }
            return $lista;
        }
        return false;   
    }
    
    public function get_by_date($date) 
    {
        $sql = "select * from ".$this->table_name." WHERE fecha_envio = ".$this->var2str($date)." ORDER BY fecha_envio, hora_envio";
        $data = $this->db->select($sql);
        $lista = array();
        if($data) {
            foreach($data as $d) {
                $lista[] = new residentes_facturacion_programada_conceptos($d);
            }
            return $lista;
        }
        return false;   
    }
    
    public function get_by_date_status($date, $status)
    {
        $sql = "select * from ".$this->table_name." WHERE fecha_envio = ".$this->var2str($date)
                ." AND "."estado = ".$this->var2str($status)." ORDER BY fecha_envio, hora_envio";
        $data = $this->db->select($sql);
        $lista = array();
        if($data) {
            foreach($data as $d) {
                $lista[] = new residentes_facturacion_programada_conceptos($d);
            }
            return $lista;
        }
        return false;   
    }
    
    public function all()
    {
        $sql = "select * from ".$this->table_name." ORDER BY fecha_envio, hora_envio";
        $data = $this->db->select($sql);
        $lista = array();
        if($data) {
            foreach($data as $d) {
                $lista[] = new residentes_facturacion_programada_conceptos($d);
            }
            return $lista;
        }
        return false;   
    }
    
    public function delete()
    {
        $sql = "DELETE from ".$this->table_name." WHERE id = ".$this->intval($this->id);
        $data = $this->db->exec($sql);
        if($data) {
            return true;
        }
        return false;   
    }
}
