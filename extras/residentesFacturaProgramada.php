<?php
/*
 * Copyright (C) 2021 Joe Nilson <joenilson@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'base/fs_model.php';

class residentesFacturaProgramada extends fs_model
{
    public $db;
    public $log;
    public $ahora;
    public $horaActual;
    public $residentesFactProg;
    public $residentesFactProgEdif;
    public $residentesFactProgCon;
    public function __construct(&$db, &$core_log)
    {
        $this->db = $db;
        $this->log = $core_log;
        $this->ahora = \date('Y-m-d');
        $this->horaActual = \date('H');
        $this->residentesFactProg = new residentes_facturacion_programada();
        $this->residentesFactProgEdif = new residentes_facturacion_programada_edificaciones();
        $this->residentesFactProgCon = new residentes_facturacion_programada_conceptos();
    }

    public function conceptoFacturable($codcliente, $referencia)
    {
        $sql = "SELECT count(referencia) as facturado from lineasfacturascli where referencia = ".
            $this->var2str($referencia) .
            " AND idfactura IN (select idfactura from facturascli WHERE codcliente = ".$this->var2str($codcliente).");";
        $data = $this->db->select($sql);
        if (!$data[0]['facturado']) {
            return true;
        }
        return false;
    }

    public function nuevaFactura($residenteProg, &$jobDisponible)
    {
        $clienteTable = new cliente();
        $empresaTable = new empresa();
        $residente = $clienteTable->get($residenteProg->codcliente);
        if ($residente) {
            $factura = new factura_cliente();
            $this->nuevaCabeceraFactura($factura, $residente, $empresaTable, $jobDisponible);

            if ($factura->save()) {
                $listaArticulos = $this->residentesFactProgCon->getConceptosByIdProgramacion($residenteProg->idprogramacion);

                $this->nuevoDetalleFactura($factura, $residente, $listaArticulos);

                $this->nuevoTotalFactura($factura, $residenteProg, $empresaTable);
                ++$jobDisponible->facturas_generadas;
                $jobDisponible->save();
            } else {
                $this->log->new_error_msg('Imposible guardar la factura.');
            }
        } else {
            $this->log->new_error_msg('Cliente no encontrado.');
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

    /**
     * @param factura_cliente $factura
     * @param object $residenteProgramado
     * @param object $empresaTable
     */
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

    public function delete()
    {
        // TODO: Implement delete() method.
    }

    public function exists()
    {
        // TODO: Implement exists() method.
    }

    public function save()
    {
        // TODO: Implement save() method.
    }
}