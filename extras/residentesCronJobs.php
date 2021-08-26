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
require_once 'plugins/residentes/extras/residentesFacturaProgramada.php';

class residentesCronJobs
{
    public $db;
    public $log;
    public $ahora;
    public $horaActual;
    public $residentesFactProg;
    public $residentesFactProgEdif;
    public $residentesFactProgCon;
    public $residentesFacturaProgramada;
    public function __construct(&$db, &$core_log)
    {
        $this->db = $db;
        $this->log = $core_log;
        $this->ahora = \date('Y-m-d');
        $this->horaActual = \date('H');
        $this->residentesFactProg = new residentes_facturacion_programada();
        $this->residentesFactProgEdif = new residentes_facturacion_programada_edificaciones();
        $this->residentesFactProgCon = new residentes_facturacion_programada_conceptos();
        $this->residentesFacturaProgramada = new residentesFacturaProgramada($db, $core_log);
    }

    /**
     * @inheritDoc
     */
    public function delete()
    {
        // TODO: Implement delete() method.
    }

    /**
     * @inheritDoc
     */
    public function exists()
    {
        // TODO: Implement exists() method.
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        // TODO: Implement save() method.
    }

    public function startJob($jobDisponible)
    {
        $idProg = $jobDisponible->id;
        $listaResidentes = $this->residentesFactProgEdif->getByIdProgramacionPendientes($idProg);
        foreach ($listaResidentes as $residente) {
            $this->stepJob($residente, $jobDisponible);
        }

        $this->finishJob($jobDisponible);
    }

    /**
     * @param object $residente
     * @param object $jobDisponible
     */
    public function stepJob(&$residente, &$jobDisponible)
    {
        if ($residente->procesado === false) {
            $this->residentesFacturaProgramada->nuevaFactura($residente, $jobDisponible);
        }
    }

    public function finishJob(&$jobDisponible)
    {
        $residentesPendientes = $this->residentesFactProgEdif->getByIdProgramacionPendientes($jobDisponible->id);
        $residentesFacturados = $this->residentesFactProgEdif->getByIdProgramacion($jobDisponible->id);
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
        $jobEncola = $this->residentesFactProg->get_by_date_hour_status($this->ahora, $this->horaActual, 'ENCOLA');
        $jobEnProceso = $this->residentesFactProg->get_by_date_hour_status($this->ahora, $this->horaActual, 'ENPROCESO');
        $jobDisponible = ($jobEncola) ?: $jobEnProceso;
        if ($jobDisponible) {
            echo " ** Se inicia el proceso de Facturación Programada ".$this->ahora." ".$this->horaActual." ** \n";
            $this->log->new_advice(' ** Se inicia el proceso de Facturación Programada ** ');
            $jobDisponible->estado = 'ENPROCESO';
            $jobDisponible->usuario_modificacion = 'cron';
            $jobDisponible->fecha_modificacion = \date('Y-m-d H:i:s');
            $jobDisponible->save();
            $this->startJob($jobDisponible);
        } else {
            echo " ** No coincide la hora de proceso con la de ejecucion de cron se omite el proceso ".
                $this->ahora . " " . $this->horaActual . " ** \n";
            $this->log->new_advice(' ** No coincide la hora de proceso con la de ejecucion de cron se omite el proceso ** ');
        }
    }

    public function statusJob()
    {
        $jobDisponible = $this->residentesFactProg->get_by_date_hour_status($this->ahora, $this->horaActual, 'ENPROCESO');
        if ($jobDisponible) {
            return 'ENPROCESO';
        }
        return false;
    }
}