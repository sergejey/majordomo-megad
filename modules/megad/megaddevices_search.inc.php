<?php

$go_linked_object=gr('go_linked_object');
$go_linked_property=gr('go_linked_property');
if ($go_linked_object && $go_linked_property) {
 $tmp = SQLSelectOne("SELECT ID, DEVICE_ID FROM megadproperties WHERE LINKED_OBJECT = '".DBSafe($go_linked_object)."' AND LINKED_PROPERTY='".DBSafe($go_linked_property)."'");
 if ($tmp['ID']) {
  $this->redirect("?id=".$tmp['ID']."&view_mode=edit_megaddevices&id=".$tmp['DEVICE_ID']."&tab=data&property_id=".$tmp['ID']);
 }
}

/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  //searching 'TITLE' (varchar)
  global $title;
  if ($title!='') {
   $qry.=" AND TITLE LIKE '%".DBSafe($title)."%'";
   $out['TITLE']=$title;
  }
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['megaddevices_qry'];
  } else {
   $session->data['megaddevices_qry']=$qry;
  }
  if (!$qry) $qry="1";
  // FIELDS ORDER
  global $sortby_megaddevices;
  if (!$sortby_megaddevices) {
   $sortby_megaddevices=$session->data['megaddevices_sort'];
  } else {
   if ($session->data['megaddevices_sort']==$sortby_megaddevices) {
    if (Is_Integer(strpos($sortby_megaddevices, ' DESC'))) {
     $sortby_megaddevices=str_replace(' DESC', '', $sortby_megaddevices);
    } else {
     $sortby_megaddevices=$sortby_megaddevices." DESC";
    }
   }
   $session->data['megaddevices_sort']=$sortby_megaddevices;
  }
  if (!$sortby_megaddevices) $sortby_megaddevices="TITLE";
  $out['SORTBY']=$sortby_megaddevices;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM megaddevices WHERE $qry ORDER BY ".$sortby_megaddevices);
  if ($res[0]['ID']) {
   colorizeArray($res);
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
    $latest_update=SQLSelectOne("SELECT UPDATED FROM megadproperties WHERE DEVICE_ID=".$res[$i]['ID']." ORDER BY UPDATED DESC LIMIT 1");
    if ($latest_update['UPDATED']) {
     $res[$i]['UPDATED']=$latest_update['UPDATED'];
    }
   }
   $out['RESULT']=$res;
  }
