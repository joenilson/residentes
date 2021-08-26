<?php

/**
 * @author Carlos García Gómez      neorazorx@gmail.com
 * @author Joe Nilson Zegarra Galvez      joenilson@gmail.com
 * @copyright 2015, Carlos García Gómez. All Rights Reserved.
 */

require_once 'plugins/residentes/extras/residentesCronJobs.php';

class cron_residentes
{

    private $db;

    public function __construct(&$db, &$core_log)
    {
        $this->db = $db;
        $this->core_log = $core_log;
        $residentesCronJobs = new residentesCronJobs($db, $core_log);
        $residentesCronJobs->initCron();
    }

}

$cron_residentes = new cron_residentes($db, $core_log);


