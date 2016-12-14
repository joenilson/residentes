<?php
/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */

/**
 * Description of inquilino
 *
 * @author carlos
 */

class inquilino extends fs_model
{
   public $id;
   public $codcliente;
   public $fechaalta;
   public $nombre;
   public $piso;
   public $bloque;
   public $mensualidad;
   public $agua;
   public $gas;
   public $observaciones;
   public $fechaagua;
   public $fechagas;
   public $deudas;

   public function __construct($i = FALSE)
   {
      parent::__construct('inquilinos', 'plugins/inquilinos/');
      if($i)
      {
         $this->id = $this->intval($i['id']);
         $this->codcliente = $i['codcliente'];
         $this->fechaalta = Date('d-m-Y', strtotime($i['fechaalta']));
         $this->nombre = $i['nombre'];
         $this->piso = $i['piso'];
         $this->bloque = $i['bloque'];
         $this->mensualidad = floatval($i['mensualidad']);
         $this->agua = floatval($i['agua']);
         $this->gas = floatval($i['gas']);
         $this->observaciones = $i['observaciones'];

         $this->fechaagua = NULL;
         if( isset($i['fechaagua']) )
         {
            $this->fechaagua = Date('d-m-Y', strtotime($i['fechaagua']));
         }

         $this->fechagas = NULL;
         if( isset($i['fechagas']) )
         {
            $this->fechagas = Date('d-m-Y', strtotime($i['fechagas']));
         }

         $this->deudas = 0;
         if( isset($i['deudas']) )
         {
            $this->deudas = floatval($i['deudas']);
         }
      }
      else
      {
         $this->id = NULL;
         $this->codcliente = NULL;
         $this->fechaalta = Date('d-m-Y');
         $this->nombre = '';
         $this->piso = '';
         $this->bloque = '';
         $this->mensualidad = 0;
         $this->agua = 0;
         $this->gas = 0;
         $this->observaciones = '';
         $this->fechaagua = NULL;
         $this->fechagas = NULL;
         $this->deudas = 0;
      }
   }

   protected function install()
   {
      return '';
   }

   public function url()
   {
      return 'index.php?page=ver_inquilino&id='.$this->id;
   }

   public function observaciones()
   {
      if($this->observaciones == '')
      {
         return '-';
      }
      else if( strlen($this->observaciones) < 60 )
      {
         return $this->observaciones;
      }
      else
         return substr($this->observaciones, 0, 60).'...';
   }

   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM inquilinos WHERE id = ".$this->var2str($id).";");
      if($data)
      {
         return new inquilino($data[0]);
      }
      else
         return FALSE;
   }

   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM inquilinos WHERE id = ".$this->var2str($this->id).";");
   }

   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE inquilinos SET codcliente = ".$this->var2str($this->codcliente).",
            nombre = ".$this->var2str($this->nombre).", fechaalta = ".$this->var2str($this->fechaalta).",
            piso = ".$this->var2str($this->piso).", bloque = ".$this->var2str($this->bloque).",
            mensualidad = ".$this->var2str($this->mensualidad).", agua = ".$this->var2str($this->agua).",
            gas = ".$this->var2str($this->gas).", observaciones = ".$this->var2str($this->observaciones).",
            fechaagua = ".$this->var2str($this->fechaagua).", fechagas = ".$this->var2str($this->fechagas).",
            deudas = ".$this->var2str($this->deudas)." WHERE id = ".$this->var2str($this->id).";";

         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO inquilinos (codcliente,nombre,fechaalta,piso,bloque,mensualidad,agua,gas,observaciones,fechaagua,fechagas,deudas) "
                 . "VALUES (".$this->var2str($this->codcliente).",".$this->var2str($this->nombre).",".$this->var2str($this->fechaalta).","
                 .$this->var2str($this->piso).",".$this->var2str($this->bloque).",".$this->var2str($this->mensualidad).","
                 .$this->var2str($this->agua).",".$this->var2str($this->gas).",".$this->var2str($this->observaciones).","
                 .$this->var2str($this->fechaagua).",".$this->var2str($this->fechagas).",".$this->var2str($this->deudas).");";

         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }

   public function delete()
   {
      return $this->db->exec("DELETE FROM inquilinos WHERE id = ".$this->var2str($this->id).";");
   }

   public function all($offset = 0)
   {
      $ilist = array();

      $data = $this->db->select_limit("SELECT * FROM inquilinos ORDER BY fechaalta DESC", FS_ITEM_LIMIT, $offset);
      if($data)
      {
         foreach($data as $d)
            $ilist[] = new inquilino($d);
      }

      return $ilist;
   }

   public function all_from_cliente($cod)
   {
      $ilist = array();

      $data = $this->db->select("SELECT * FROM inquilinos WHERE codcliente = ".$this->var2str($cod)." ORDER BY fechaalta DESC;");
      if($data)
      {
         foreach($data as $d)
            $ilist[] = new inquilino($d);
      }

      return $ilist;
   }

   public function all_from_bloque($blo)
   {
      $ilist = array();

      $data = $this->db->select("SELECT * FROM inquilinos WHERE bloque = ".$this->var2str($blo)." ORDER BY piso ASC;");
      if($data)
      {
         foreach($data as $d)
            $ilist[] = new inquilino($d);
      }

      return $ilist;
   }
}
