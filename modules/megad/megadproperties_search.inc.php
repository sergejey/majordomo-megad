<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  if (IsSet($this->device_id)) {
   $device_id=$this->device_id;
   $qry.=" AND DEVICE_ID='".$this->device_id."'";
  } else {
   global $device_id;
  }
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['megadproperties_qry'];
  } else {
   $session->data['megadproperties_qry']=$qry;
  }
  if (!$qry) $qry="1";
  // FIELDS ORDER
  global $sortby_megadproperties;
  if (!$sortby_megadproperties) {
   $sortby_megadproperties=$session->data['megadproperties_sort'];
  } else {
   if ($session->data['megadproperties_sort']==$sortby_megadproperties) {
    if (Is_Integer(strpos($sortby_megadproperties, ' DESC'))) {
     $sortby_megadproperties=str_replace(' DESC', '', $sortby_megadproperties);
    } else {
     $sortby_megadproperties=$sortby_megadproperties." DESC";
    }
   }
   $session->data['megadproperties_sort']=$sortby_megadproperties;
  }
  if (!$sortby_megadproperties) $sortby_megadproperties="NUM";
  $out['SORTBY']=$sortby_megadproperties;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM megadproperties WHERE $qry ORDER BY ".$sortby_megadproperties);
  if ($res[0]['ID']) {
   colorizeArray($res);
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
