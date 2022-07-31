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
 * Description of residentes_email_config
 *
 * @author joenilson
 */
class residentes_email_config extends \fs_model
{
    public $id;
    public $tiposervicio;
    public $apikey;
    public $apisecret;
    public $apisenderemail;
    public $apisendername;
    public $emailsubject;
    public $usuario_creacion;
    public $usuario_modificacion;
    public $fecha_creacion;
    public $fecha_modificacion;
    public $ahora;

    public function __construct($t = false)
    {
        parent::__construct('residentes_email_config', 'plugins/residentes');
        if ($t) {
            $this->id = $t['id'];
            $this->tiposervicio = $t['tiposervicio'];
            $this->apikey = $t['apikey'];
            $this->apisecret = $t['apisecret'];
            $this->apisenderemail = $t['apisenderemail'];
            $this->apisendername = $t['apisendername'];
            $this->emailsubject = $t['emailsubject'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->fecha_creacion = $t['fecha_creacion'];
            $this->fecha_modificacion = $t['fecha_modificacion'];
        } else {
            $this->id = null;
            $this->tiposervicio = 'interno';
            $this->apikey = '';
            $this->apisecret = '';
            $this->apisenderemail = '';
            $this->apisendername = '';
            $this->emailsubject = '';
            $this->usuario_creacion = null;
            $this->usuario_modificacion = null;
            $this->fecha_creacion = null;
            $this->fecha_modificacion = null;
        }
        $this->ahora = new \DateTime('NOW');
    }

    public function install()
    {
        return "";
    }
    
    public function exists()
    {
        if (is_null($this->id)) {
            return false;
        }
        return true;
    }
    
    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET ".
            "tiposervicio = ".$this->var2str($this->tiposervicio).", ".
            "apikey = ".$this->var2str($this->apikey).", ".
            "apisecret = ".$this->var2str($this->apisecret).", ".
            "apisenderemail = ".$this->var2str($this->apisenderemail).", ".
            "apisendername = ".$this->var2str($this->apisendername).", ".
            "emailsubject = ".$this->var2str($this->emailsubject).", ".
            "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
            "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
            "WHERE id = ".$this->intval($this->id).";";
            $data = $this->db->exec($sql);
            return $data;
        } else {
            $sql = "INSERT INTO ".$this->table_name.
            " (tiposervicio, apikey, apisecret, apisenderemail, apisendername, emailsubject, usuario_creacion, fecha_creacion) " .
            " VALUES (".
            $this->var2str($this->tiposervicio).", ".
            $this->var2str($this->apikey).", ".
            $this->var2str($this->apisecret).", ".
            $this->var2str($this->apisenderemail).", ".
            $this->var2str($this->apisendername).", ".
            $this->var2str($this->emailsubject).", ".
            $this->var2str($this->usuario_creacion).", ".
            $this->var2str($this->fecha_creacion).");";
            if ($this->db->exec($sql)) {
                return $this->db->lastval();
            } else {
                return false;
            }
        }
    }

    /**
     * @param integer $id
     * @return residentes_email_config|false
     */
    public function get($id)
    {
        $sql = "select * from ".$this->table_name." WHERE id = ".$this->intval($id);
        
        $data = $this->db->select($sql);
        if ($data) {
            return new residentes_email_config($data[0]);
        }
        return false;
    }

    /**
     * @return residentes_email_config|false
     */
    public function currentConfig()
    {
        $sql = "select * from ".$this->table_name;

        $data = $this->db->select($sql);
        if ($data) {
            return new residentes_email_config($data[0]);
        }
        return new residentes_email_config();
    }

    /**
     * @param string $service
     * @return array|false
     */
    public function get_by_service($service)
    {
        $sql = "select * from ".$this->table_name." WHERE tiposervicio = ".$this->var2str($service).
            " ORDER BY id";
        $data = $this->db->select($sql);
        $lista = array();
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new residentes_email_config($d);
            }
            return $lista;
        }
        return false;
    }

    /**
     * @param integer $id
     * @return array|false
     */
    public function get_by_id($id)
    {
        $sql = "select * from ".$this->table_name." WHERE id = ".$this->intval($id);
        $data = $this->db->select($sql);
        $lista = array();
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new residentes_email_config($d);
            }
            return $lista;
        }
        return false;
    }

    public function all()
    {
        $sql = "select * from ".$this->table_name." ORDER BY id, tiposervicio";
        $data = $this->db->select($sql);
        $lista = array();
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new residentes_email_config($d);
            }
            return $lista;
        }
        return false;
    }
    
    public function delete()
    {
        $sql = "DELETE from ".$this->table_name." WHERE id = ".$this->intval($this->id);
        $data = $this->db->exec($sql);
        if ($data) {
            return true;
        }
        return false;
    }
}
