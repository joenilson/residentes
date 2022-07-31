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
    public $descripcion;
    public $tipo_programacion;
    public $forma_pago;
    public $formato_factura;
    public $fecha_vencimiento;
    public $fecha_envio;
    public $hora_envio;
    public $residentes_facturar;
    public $facturas_generadas;
    public $usuario_creacion;
    public $usuario_modificacion;
    public $estado;
    public $fecha_creacion;
    public $fecha_modificacion;
    public $ahora;
    public $horaActual;

    public function __construct($t = false)
    {
        parent::__construct('residentes_fact_prog', 'plugins/residentes');
        if ($t) {
            $this->id = $t['id'];
            $this->descripcion = $t['descripcion'];
            $this->tipo_programacion = $t['tipo_programacion'];
            $this->forma_pago = $t['forma_pago'];
            $this->formato_factura = $t['formato_factura'];
            $this->fecha_vencimiento = $t['fecha_vencimiento'];
            $this->fecha_envio = $t['fecha_envio'];
            $this->hora_envio = $t['hora_envio'];
            $this->residentes_facturar = $t['residentes_facturar'];
            $this->facturas_generadas = $t['facturas_generadas'];
            $this->usuario_creacion = $t['usuario_creacion'];
            $this->usuario_modificacion = $t['usuario_modificacion'];
            $this->estado = $t['estado'];
            $this->fecha_creacion = $t['fecha_creacion'];
            $this->fecha_modificacion = $t['fecha_modificacion'];
        } else {
            $this->id = null;
            $this->descripcion = '';
            $this->tipo_programacion = 'generar';
            $this->forma_pago = '';
            $this->formato_factura = 'plantillas_pdf';
            $this->fecha_envio = null;
            $this->hora_envio = null;
            $this->residentes_facturar = 0;
            $this->facturas_generadas = 0;
            $this->usuario_creacion = null;
            $this->usuario_modificacion = null;
            $this->estado = 'ENCOLA';
            $this->fecha_creacion = null;
            $this->fecha_modificacion = null;
        }
        $this->ahora = new \DateTime('NOW');
        $this->horaActual = \strtotime($this->ahora->format('H'));
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
            "descripcion = ".$this->var2str($this->descripcion).", ".
            "tipo_programacion = ".$this->var2str($this->tipo_programacion).", ".
            "forma_pago = ".$this->var2str($this->forma_pago).", ".
            "formato_factura = ".$this->var2str($this->formato_factura).", ".
            "fecha_vencimiento = ".$this->var2str($this->fecha_vencimiento).", ".
            "fecha_envio = ".$this->var2str($this->fecha_envio).", ".
            "hora_envio = ".$this->var2str($this->hora_envio).", ".
            "residentes_facturar = ".$this->intval($this->residentes_facturar).", ".
            "facturas_generadas = ".$this->intval($this->facturas_generadas).", ".
            "estado = ".$this->var2str($this->estado).", ".
            "usuario_modificacion = ".$this->var2str($this->usuario_modificacion).", ".
            "fecha_modificacion = ".$this->var2str($this->fecha_modificacion)." ".
            "WHERE id = ".$this->intval($this->id).";";
            $data = $this->db->exec($sql);
            return $data;
        } else {
            $sql = "INSERT INTO ".$this->table_name.
            " (descripcion, tipo_programacion, forma_pago, formato_factura, fecha_vencimiento, fecha_envio, hora_envio, ".
                "residentes_facturar, facturas_generadas, usuario_creacion, fecha_creacion, estado) VALUES (".
            $this->var2str($this->descripcion).", ".
            $this->var2str($this->tipo_programacion).", ".
            $this->var2str($this->forma_pago).", ".
            $this->var2str($this->formato_factura).", ".
            $this->var2str($this->fecha_vencimiento).", ".
            $this->var2str($this->fecha_envio).", ".
            $this->var2str($this->hora_envio).", ".
            $this->intval($this->residentes_facturar).", ".
            $this->intval($this->facturas_generadas).", ".
            $this->var2str($this->usuario_creacion).", ".
            $this->var2str($this->fecha_creacion).", ".
            $this->var2str($this->estado).");";
            if ($this->db->exec($sql)) {
                return $this->db->lastval();
            } else {
                return false;
            }
        }
    }

    /**
     * @param integer $id
     * @return residentes_facturacion_programada|false
     */
    public function get($id)
    {
        $sql = "select * from ".$this->table_name." WHERE id = ".$this->intval($id);
        
        $data = $this->db->select($sql);
        if ($data) {
            return new residentes_facturacion_programada($data[0]);
        }
        return false;
    }

    /**
     * @param string $date
     * @return array|false
     */
    public function get_by_date($date)
    {
        $sql = "select * from ".$this->table_name." WHERE fecha_envio = ".$this->var2str($date).
            " ORDER BY fecha_envio, hora_envio";
        $data = $this->db->select($sql);
        $lista = array();
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new residentes_facturacion_programada($d);
            }
            return $lista;
        }
        return false;
    }

    /**
     * @param integer $id
     * @param string $status
     * @return array|false
     */
    public function get_by_id_and_status($id, $status = 'CONCLUIDO')
    {
        $sql = "select * from ".$this->table_name." WHERE ESTADO = ".$this->var2str($status).
            " ORDER BY fecha_envio, hora_envio";
        $data = $this->db->select($sql);
        $lista = array();
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new residentes_facturacion_programada($d);
            }
            return $lista;
        }
        return false;
    }

    /**
     * @param string $date
     * @param string $status
     * @return array|false
     */
    public function get_by_date_status($date, $status)
    {
        $sql = "select * from ".$this->table_name." WHERE fecha_envio = ".$this->var2str($date)
                ." AND "."estado = ".$this->var2str($status)." ORDER BY fecha_envio, hora_envio";
        $data = $this->db->select($sql);
        $lista = array();
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new residentes_facturacion_programada($d);
            }
            return $lista;
        }
        return false;
    }

    /**
     * @param string $date
     * @param string $hour
     * @param string $status
     * @return residentes_facturacion_programada|false
     */
    public function get_by_date_hour_status($date, $hour, $status)
    {
        $sql = "select * from ".$this->table_name." WHERE fecha_envio = ".$this->var2str($date)
                ." AND ".
                " hora_envio = ".$this->var2str($hour).
                " AND ".
                "estado = ".$this->var2str($status)." ORDER BY fecha_envio, hora_envio";
        $data = $this->db->select($sql);
        if ($data) {
            return new residentes_facturacion_programada($data[0]);
        }
        return false;
    }
    
    public function all()
    {
        $sql = "select * from ".$this->table_name." ORDER BY fecha_envio, hora_envio";
        $data = $this->db->select($sql);
        $lista = array();
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new residentes_facturacion_programada($d);
            }
            return $lista;
        }
        return false;
    }
    
    public function delete()
    {
        $this->eliminar_facturas();
        $sql = "DELETE from ".$this->table_name." WHERE id = ".$this->intval($this->id);
        $data = $this->db->exec($sql);
        if ($data) {
            return true;
        }
        return false;
    }
    
    public function eliminar_facturas()
    {
        $rfpe = new residentes_facturacion_programada_edificaciones();
        $listaResidentes = $rfpe->getByIdProgramacion($this->id);
        foreach ($listaResidentes as $residente) {
            $fact = new factura_cliente();
            $f = $fact->get($residente->idfactura);
            if ($f) {
                $f->delete();
            }
            $residente->procesado = false;
            $residente->idfactura = '';
            $residente->save();
        }
    }
}
