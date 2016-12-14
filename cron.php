<?php
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */

require_model('inquilino.php');

class cron_inquilinos
{
   private $db;
   private $inquilino;

   public function __construct($db)
   {
      $this->db = $db;
      $this->inquilino = new inquilino();

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

new cron_inquilinos($db);