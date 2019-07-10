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
 * Description of residentes_facturacion_programada
 *
 * @author joenilson
 */
class residentes_facturacion_programada extends \fs_model
{
    public $id;
    public $conceptos;
    public $forma_pago;
    public $formato_factura;
    public $fecha_envio;
    public $hora_envio;
    public $facturas_generadas;
    public $usuario_creacion;
    public $estado;
    public $fecha_creacion;
    public $fecha_modificacion;
    
    public function __construct($t = FALSE) {
        parent::__construct('residentes_facturacion_programada','plugins/residentes');
        if($t){
            $this->id = $t['id'];
            $this->conceptos = $t['conceptos'];
            $this->descripcion = $t['descripcion'];
            $this->forma_pago = $t['forma_pago'];
            $this->formato_factura = $t['formato_factura'];
            $this->fecha_envio = $t['fecha_envio'];
            $this->hora_envio = $t['hora_envio'];
            $this->facturas_generadas = $t['facturas_generadas'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->estado = $t['estado'];
            $this->fecha_creacion = $t['fecha_creacion'];
            $this->fecha_modificacion = $t['fecha_modificacion'];
        }else{
            $this->id = null;
            $this->conceptos = '';
            $this->descripcion = '';
            $this->forma_pago = '';
            $this->formato_factura = '';
            $this->fecha_envio = null;
            $this->hora_envio = null;
            $this->facturas_generadas = null;
            $this->usuario_creacion = null;
            $this->estado = null;
            $this->fecha_creacion = null;
            $this->fecha_modificacion = null;
        }
    }

    public function install(){
        return "";
    }
    
    public function exists()
    {
        if($this->get($this->id)) {
            return true;
        }
        return false;
    }
    
    public function save()
    {
        if($this->exists()){
            $sql = "UPDATE ".$this->table_name." SET ".
            "conceptos = ".$this->intval($this->conceptos).", ".
            "descripcion = ".$this->intval($this->descripcion).", ".
            "forma_pago = ".$this->intval($this->forma_pago).", ".
            "formato_factura = ".$this->intval($this->formato_factura).", ".
            "fecha_envio = ".$this->intval($this->fecha_envio).", ".
            "hora_envio = ".$this->intval($this->hora_envio).", ".
            "facturas_generadas = ".$this->intval($this->facturas_generadas).", ".
            "usuario_creacion = ".$this->intval($this->usuario_creacion).", ".
            "estado = ".$this->var2str($this->estado).", ".
            "fecha_creacion = ".$this->var2str($this->fecha_creacion).", ".
            "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
            "WHERE id = ".$this->var2str($this->id).";";
            return $this->db->exec($sql);
        }else{
            $sql = "INSERT INTO ".$this->table_name.
            " (conceptos, descripcion, forma_pago, formato_factura, fecha_envio, hora_envio, facturas_generadas, usuario_creacion, fecha_creacion, estado) VALUES (".
            $this->var2str($this->conceptos).", ".
            $this->var2str($this->descripcion).", ".
            $this->var2str($this->forma_pago).", ".
            $this->var2str($this->formato_factura).", ".
            $this->var2str($this->fecha_envio).", ".
            $this->var2str($this->hora_envio).", ".
            $this->intval($this->facturas_generadas).", ".
            $this->var2str($this->usuario_creacion).", ".
            $this->var2str($this->fecha_creacion).", ".
            $this->var2str($this->estado).");";
            
            if($this->db->exec($sql)){
                return true;
            }
            return false;
        }
    }
    
    public function get($id)
    {
        $sql = "select * from ".$this->table_name." WHERE id = ".$this->intval($id);
        $data = $this->db->select($sql);
        if($data) {
            return new residentes_facturacion_programada($data[0]);
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
                $lista[] = new residentes_facturacion_programada($d);
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
                $lista[] = new residentes_facturacion_programada($d);
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
                $lista[] = new residentes_facturacion_programada($d);
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
