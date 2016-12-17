<?php

/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */
require_model('residente.php');

/**
 * Description of informe_residentes
 *
 * @author carlos <neorazorx@gmail.com>
 * @author Joe Nilson <joenilson at gmail.com>
 */
class informe_residentes extends fs_controller
{
   public $bloque;
   public $desde;
   public $hasta;
   public $resultados;
   public $tipo;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Residentes', 'informes', FALSE, TRUE);
   }

   protected function private_core()
   {
      /// forzamos la comprobación de la tabla residentes
      $residente = new residente();

      $this->bloque = NULL;
      if( isset($_GET['bloque']) )
      {
         $this->bloque = $_GET['bloque'];
      }

      $this->tipo = 'mensualidad';
      if( isset($_GET['tipo']) )
      {
         $this->tipo = $_GET['tipo'];
      }

      $this->desde = Date('01-m-Y');
      if( isset($_POST['desde']) )
         $this->desde = $_POST['desde'];

      $this->hasta = Date('t-m-Y');
      if( isset($_POST['hasta']) )
         $this->hasta = $_POST['hasta'];

      switch ($this->tipo)
      {
         case 'agua':
            $this->resultados = $this->datos_informe_agua();
            break;

         case 'gas':
            $this->resultados = $this->datos_informe_gas();
            break;

         default:
            $this->resultados = $this->datos_informe_mensualidad();
            break;
      }
   }

   public function url()
   {
      if( isset($_REQUEST['bloque']) )
      {
         return 'index.php?page=informe_residentes&bloque='.$_REQUEST['bloque'];
      }
      else
         return parent::url();
   }

   public function bloques()
   {
      $blist = array();

      $data = $this->db->select("SELECT DISTINCT bloque FROM residentes ORDER BY bloque ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $blist[] = $d['bloque'];
         }
      }

      return $blist;
   }

   private function datos_informe_mensualidad()
   {
      $dlist = array();

      $data = $this->db->select("SELECT l.pvptotal,l.iva,l.idfactura,f.fecha,f.pagada,f.codcliente,f.nombrecliente,i.id,i.piso
         FROM lineasfacturascli l, facturascli f, residentes i
         WHERE l.descripcion LIKE 'Mensualidad%' AND l.idfactura = f.idfactura AND f.codcliente = i.codcliente
         AND fecha >= ".$this->empresa->var2str($this->desde)." AND fecha <= ".$this->empresa->var2str($this->hasta)."
         AND bloque = ".$this->empresa->var2str($this->bloque)." ORDER BY piso ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $aux = array(
                'residente' => $d['id'],
                'piso' => $d['piso'],
                'nombre' => $d['nombrecliente'],
                'idfactura' => $d['idfactura'],
                'fecha' => date('d-m-Y', strtotime($d['fecha'])),
                'importe' => floatval($d['pvptotal']) * (100+floatval($d['iva'])) / 100,
                'pagada' => $this->str2bool($d['pagada'])
            );

            $dlist[] = $aux;
         }
      }

      return $dlist;
   }

   private function datos_informe_agua()
   {
      $dlist = array();

      $data = $this->db->select("SELECT l.cantidad,l.pvptotal,l.iva,l.idfactura,f.fecha,f.pagada,f.codcliente,f.nombrecliente,i.id,i.piso
         FROM lineasfacturascli l, facturascli f, residentes i
         WHERE l.descripcion LIKE 'Consumo de agua%' AND l.idfactura = f.idfactura AND f.codcliente = i.codcliente
         AND fecha >= ".$this->empresa->var2str($this->desde)." AND fecha <= ".$this->empresa->var2str($this->hasta)."
         AND bloque = ".$this->empresa->var2str($this->bloque)." ORDER BY piso ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $aux = array(
                'residente' => $d['id'],
                'piso' => $d['piso'],
                'nombre' => $d['nombrecliente'],
                'idfactura' => $d['idfactura'],
                'fecha' => date('d-m-Y', strtotime($d['fecha'])),
                'consumo' => floatval($d['cantidad']),
                'importe' => floatval($d['pvptotal']) * (100+floatval($d['iva'])) / 100,
                'pagada' => $this->str2bool($d['pagada'])
            );

            $dlist[] = $aux;
         }
      }

      return $dlist;
   }

   private function datos_informe_gas()
   {
      $dlist = array();

      $data = $this->db->select("SELECT l.cantidad,l.pvptotal,l.iva,l.idfactura,f.fecha,f.pagada,f.codcliente,f.nombrecliente,i.id,i.piso
         FROM lineasfacturascli l, facturascli f, residentes i
         WHERE l.descripcion LIKE 'Consumo de gas%' AND l.idfactura = f.idfactura AND f.codcliente = i.codcliente
         AND fecha >= ".$this->empresa->var2str($this->desde)." AND fecha <= ".$this->empresa->var2str($this->hasta)."
         AND bloque = ".$this->empresa->var2str($this->bloque)." ORDER BY piso ASC;");
      if($data)
      {
         foreach($data as $d)
         {
            $aux = array(
                'residente' => $d['id'],
                'piso' => $d['piso'],
                'nombre' => $d['nombrecliente'],
                'idfactura' => $d['idfactura'],
                'fecha' => date('d-m-Y', strtotime($d['fecha'])),
                'consumo' => floatval($d['cantidad']),
                'importe' => floatval($d['pvptotal']) * (100+floatval($d['iva'])) / 100,
                'pagada' => $this->str2bool($d['pagada'])
            );

            $dlist[] = $aux;
         }
      }

      return $dlist;
   }

   private function str2bool($v)
   {
      return ($v == 't' OR $v == '1');
   }
}
