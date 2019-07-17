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
 * Description of residentes_facturacion_programada_edificaciones
 *
 * @author joenilson
 */
class residentes_facturacion_programada_edificaciones extends \fs_model
{
    public $id;
    public $idprogramacion;
    public $id_edificacion;
    public $codcliente;
    public $idfactura;
    public $procesado;
    
    public function __construct($t = FALSE) {
        parent::__construct('residentes_fact_prog_edificaciones','plugins/residentes');
        if($t){
            $this->id = $t['id'];
            $this->idprogramacion = $t['idprogramacion'];
            $this->id_edificacion = $t['id_edificacion'];
            $this->codcliente = $t['codcliente'];
            $this->idfactura = $t['idfactura'];
            $this->procesado = boolval($t['procesado']);
        }else{
            $this->id = null;
            $this->idprogramacion = null;
            $this->id_edificacion = null;
            $this->codcliente = '';
            $this->idfactura = null;
            $this->procesado = false;
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
            "idfactura = ".$this->intval($this->idfactura).", ".
            "codcliente = ".$this->var2str($this->codcliente).", ".
            "id_edificacion = ".$this->intval($this->id_edificacion).", ".
            "idprogramacion = ".$this->intval($this->idprogramacion)." ".
            "procesado = ".$this->var2str($this->procesado).", ".
            "WHERE id = ".$this->intval($this->id).";";
            $data = $this->db->exec($sql);
            return $data;
        }else{
            $sql = "INSERT INTO ".$this->table_name.
            " (idprogramacion, id_edificacion, codcliente, procesado) VALUES (".
            $this->intval($this->idprogramacion).", ".
            $this->intval($this->id_edificacion).", ".
            $this->var2str($this->codcliente).", ";
            $this->var2str($this->procesado).");";
            
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
        $this->new_advice($sql);
        $data = $this->db->select($sql);
        if($data) {
            return new residentes_facturacion_programada_edificaciones($data[0]);
        }
        return false;
    }
    
    public function getByIdProgramacion($idProg) 
    {
        $sql = "select * from ".$this->table_name." WHERE idprogramacion = ".$this->intval($idProg)." ORDER BY id";
        $data = $this->db->select($sql);
        $lista = array();
        if($data) {
            foreach($data as $d) {
                $lista[] = new residentes_facturacion_programada_edificaciones($d);
            }
            return $lista;
        }
        return false;   
    }
    
    public function getByIdProgramacionPendientes($idProg) 
    {
        $sql = "select * from ".$this->table_name." WHERE idprogramacion = ".$this->intval($idProg).
                " AND procesado = FALSE ORDER BY id";
        $data = $this->db->select($sql);
        $lista = array();
        if($data) {
            foreach($data as $d) {
                $lista[] = new residentes_facturacion_programada_edificaciones($d);
            }
            return $lista;
        }
        return false;   
    }
    
    public function get_by_date_status($date, $status)
    {
        $sql = "select * from ".$this->table_name." WHERE fecha_envio = ".$this->var2str($date)
                ." AND "."estado = ".$this->var2str($status)." ORDER BY idprogramacion, id";
        $data = $this->db->select($sql);
        $lista = array();
        if($data) {
            foreach($data as $d) {
                $lista[] = new residentes_facturacion_programada_edificaciones($d);
            }
            return $lista;
        }
        return false;   
    }
    
    public function all()
    {
        $sql = "select * from ".$this->table_name." ORDER BY idprogramacion, id";
        $data = $this->db->select($sql);
        $lista = array();
        if($data) {
            foreach($data as $d) {
                $lista[] = new residentes_facturacion_programada_edificaciones($d);
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
