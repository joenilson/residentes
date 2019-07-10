<?php

/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */
require_model('cron_residentes.php');
/*
class cron_residentes
{

    private $db;
    private $residentes_facturacion_programada;
    private $residentes_generar_facturacion;

    public function __construct($db)
    {
        $this->db = $db;
        
          $this->db = $db;
          $this->residentes_facturacion_programada = new residentes_facturacion_programada();

          foreach($this->inquilino->all() as $inq)
          {
          $deuda = 0;
          $data = $this->db->select("SELECT SUM(total) as deuda FROM facturascli WHERE pagada IS FALSE AND codcliente = ".$inq->var2str($inq->codcliente).";");
          if($data)
          {
          $deuda = floatval($data[0]['deuda']);
          }

          if($inq->deudas != $deuda)
          {
          $inq->deudas = $deuda;
          $inq->save();
          }
          }
        
    }

}
 * */
$cron_residentes = new cron_residentes();
$con_residentes->startJob();

