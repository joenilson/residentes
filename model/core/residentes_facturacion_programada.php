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
    public $forma_pago;
    public $formato_factura;
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
            $this->forma_pago = $t['forma_pago'];
            $this->formato_factura = $t['formato_factura'];
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
        $this->horaActual = strtotime($this->ahora->format('H'));
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
            $sql = "UPDATE ".$this->table_name." SET ".
            "descripcion = ".$this->var2str($this->descripcion).", ".
            "forma_pago = ".$this->var2str($this->forma_pago).", ".
            "formato_factura = ".$this->var2str($this->formato_factura).", ".
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
            " (descripcion, forma_pago, formato_factura, fecha_envio, hora_envio, residentes_facturar, ".
                "facturas_generadas, usuario_creacion, fecha_creacion, estado) VALUES (".
            $this->var2str($this->descripcion).", ".
            $this->var2str($this->forma_pago).", ".
            $this->var2str($this->formato_factura).", ".
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
    
    public function conceptoFacturable($codcliente, $referencia)
    {
        $sql = "SELECT count(referencia) as facturado from lineasfacturascli where referencia = ".
            $this->var2str($referencia)." ".
            " AND idfactura IN (select idfactura from facturascli WHERE codcliente = ".$this->var2str($codcliente).");";
        $data = $this->db->select($sql);
        if (!$data[0]['facturado']) {
            return true;
        }
        return false;
    }
    
    public function startJob($jobDisponible)
    {
        $idProg = $jobDisponible->id;
        $residentesProgramados = new residentes_facturacion_programada_edificaciones();
        $listaResidentes = $residentesProgramados->getByIdProgramacionPendientes($idProg);
        foreach ($listaResidentes as $residente) {
            $this->stepJob($residente, $jobDisponible);
        }
        
        $this->finishJob($jobDisponible);
    }
    
    public function stepJob(&$residente, &$jobDisponible)
    {
        if ($residente->procesado === false) {
            $this->nuevaFactura($residente, $jobDisponible);
        }
    }
    
    public function stopJob()
    {
    }
    
    public function finishJob(&$jobDisponible)
    {
        $residentesProgramados = new residentes_facturacion_programada_edificaciones();
        $residentesPendientes = $residentesProgramados->getByIdProgramacionPendientes($jobDisponible->id);
        $residentesFacturados = $residentesProgramados->getByIdProgramacion($jobDisponible->id);
        if ($residentesPendientes === false) {
            $jobDisponible->estado = 'CONCLUIDO';
            $jobDisponible->facturas_generadas = ($residentesFacturados) ? count($residentesFacturados) : 0;
            $jobDisponible->usuario_modificacion = 'cron';
            $jobDisponible->fecha_modificacion = \date('Y-m-d H:i:s');
            $jobDisponible->save();
        }
    }
    
    public function initCron()
    {
        $ahora = \date('Y-m-d');
        $horaActual = \date('H');
        $jobDisponible = $this->get_by_date_hour_status($ahora, $horaActual, 'ENCOLA');
        if ($jobDisponible) {
            echo " ** Se inicia el proceso de Facturación Programada ".$ahora." ".$horaActual." ** \n";
            $this->new_advice(' ** Se inicia el proceso de Facturación Programada ** ');
            $jobDisponible->estado = 'ENPROCESO';
            $jobDisponible->usuario_modificacion = 'cron';
            $jobDisponible->fecha_modificacion = \date('Y-m-d H:i:s');
            $jobDisponible->save();
            $this->startJob($jobDisponible);
        } else {
            echo " ** No coincide la hora de proceso con la de ejecucion de cron se omite el proceso ".
                "$ahora $horaActual ** \n";
            $this->new_advice(' ** No coincide la hora de proceso con la de ejecucion de cron se omite el proceso ** ');
        }
    }
    
    public function statusJob()
    {
    }
    
    public function nuevaFactura($resProgramado, &$jobDisponible)
    {
        $clienteTable = new cliente();
        $empresaTable = new empresa();
        $residente = $clienteTable->get($resProgramado->codcliente);
        if ($residente) {
            $factura = new factura_cliente();
            $this->nuevaCabeceraFactura($factura, $residente, $empresaTable, $jobDisponible);
            
            if ($factura->save()) {
                $conceptosProgramados = new residentes_facturacion_programada_conceptos();
                $listaArticulos = $conceptosProgramados->getConceptosByIdProgramacion($resProgramado->idprogramacion);
                
                $this->nuevoDetalleFactura($factura, $residente, $listaArticulos);
                
                $this->nuevoTotalFactura($factura, $resProgramado, $empresaTable);
                ++$jobDisponible->facturas_generadas;
                $jobDisponible->save();
            } else {
                $this->new_error_msg('Imposible guardar la factura.');
            }
        } else {
            $this->new_error_msg('Cliente no encontrado.');
        }
    }
    
    public function nuevaCabeceraFactura(&$factura, &$residente, &$empresaTable, &$jobDisponible)
    {
        $factura->codserie = ($residente->codserie) ?: $empresaTable->codserie;
        $factura->codpago = $jobDisponible->forma_pago;
        $factura->codalmacen = $empresaTable->codalmacen;
        $factura->codagente = '1';
        $factura->set_fecha_hora(\date('Y-m-d'), \date('H:i:s'));

        $this->nuevaVerificacionContabilidadFactura($factura, $residente, $empresaTable);

        $this->nuevaDivisaFactura($factura, $residente);

        $factura->codcliente = $residente->codcliente;
        $this->nuevaInformacionResidenteFactura($factura, $residente);
        
        /// función auxiliar para implementar en los plugins que lo necesiten
        if (!fs_generar_numero2($factura)) {
            $factura->numero2 = '';
            echo "No hay funcion libre. \n";
        }
    }
    
    public function nuevoDetalleFactura(&$factura, &$residente, $listaArticulos)
    {
        $art0 = new articulo();
        $impuesto = new impuesto();
        
        foreach ($listaArticulos as $concepto) {
            $art = $art0->get($concepto->referencia);
            if ($this->conceptoFacturable($residente->codcliente, $concepto->referencia)) {
                $linea = new linea_factura_cliente();
                $linea->idfactura = $factura->idfactura;
                $linea->referencia = $concepto->referencia;
                $linea->descripcion = $art->descripcion;
                $linea->cantidad = $concepto->cantidad;
                $imp = $impuesto->get($concepto->codimpuesto);
                $linea->codimpuesto = $imp->codimpuesto;
                $linea->iva = $imp->iva;
                $linea->pvpsindto = $concepto->pvp;
                $linea->pvpunitario = $concepto->pvp;
                $linea->pvptotal = $concepto->pvp * $concepto->cantidad;
                $this->nuevoTotalLineasFactura($factura, $linea);
            }
        }
    }
    
    public function nuevoTotalLineasFactura(&$factura, &$linea)
    {
        if ($linea->save()) {
            $factura->neto += $linea->pvptotal;
            $factura->totaliva += $linea->pvptotal * $linea->iva / 100;
        }
    }
    
    public function nuevoTotalFactura(&$factura, &$residenteProgramado, &$empresaTable)
    {
        /// redondeamos
        $factura->neto = round($factura->neto, FS_NF0);
        $factura->totaliva = round($factura->totaliva, FS_NF0);
        $factura->totalirpf = round($factura->totalirpf, FS_NF0);
        $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
        $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;

        if ($factura->save()) {
            $this->generar_asiento($factura, $empresaTable);
            /// Función de ejecución de tareas post guardado correcto de la factura
            fs_documento_post_save($factura);
            //Actualizamos la data del residente
            $residenteProgramado->idfactura = $factura->idfactura;
            $residenteProgramado->procesado = true;
            $residenteProgramado->save();
        } else {
            $factura->delete();
        }
    }
    
    public function nuevaVerificacionContabilidadFactura(&$factura, &$residente, &$empresaTable)
    {
        $eje0 = new ejercicio();
        $ejercicio = $eje0->get_by_fecha(date('d-m-Y'));
        if ($ejercicio) {
            $factura->codejercicio = $ejercicio->codejercicio;
        }
        if ($empresaTable->contintegrada) {
            /// forzamos crear la subcuenta
            $residente->get_subcuenta($empresaTable->codejercicio);
        }
    }
    
    public function nuevaDivisaFactura(&$factura, &$residente)
    {
        $div0 = new divisa();
        $divisa = $div0->get($residente->coddivisa);
        if ($divisa) {
            $factura->coddivisa = $divisa->coddivisa;
            $factura->tasaconv = $divisa->tasaconv;
        }
    }
    
    public function nuevaInformacionResidenteFactura(&$factura, &$residente)
    {
        foreach ($residente->get_direcciones() as $d) {
            if ($d->domfacturacion) {
                $factura->codcliente = $residente->codcliente;
                $factura->cifnif = $residente->cifnif;
                $factura->nombrecliente = $residente->nombre;
                $factura->apartado = $d->apartado;
                $factura->ciudad = $d->ciudad;
                $factura->coddir = $d->id;
                $factura->codpais = $d->codpais;
                $factura->codpostal = $d->codpostal;
                $factura->direccion = $d->direccion;
                $factura->provincia = $d->provincia;
                break;
            }
        }
    }

    /**
     * Genera el asiento para la factura, si procede
     * @param factura_cliente $factura
     */
    public function generar_asiento(&$factura, &$empresaTable)
    {
        if ($empresaTable->contintegrada) {
            $asiento_factura = new asiento_factura();
            $asiento_factura->generar_asiento_venta($factura);
        } else {
            /// de todas formas forzamos la generación de las líneas de iva
            $factura->get_lineas_iva();
        }
    }
}
