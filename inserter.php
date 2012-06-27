<?php 
require_once 'meekrodb.2.0.class.php';
class Inserter{
   var $column="";
   var $table=""; 
   var $items=array();
   function __construct($table,$column,$values){
     //$values=array("BEDRNÍK","Ekoton");
     $this->table=$table;
     $this->column=$column;
     $result= DB::query("SELECT * FROM %l WHERE %l IN %ls",$table,$column,$values);
     $this->buildItems($result,$column);
     $this->checkInsert($values);
   }
   function buildItems($result){
      foreach($result as $row){ 
          //printf("ID: %s  Name: %s\n", $row["id"], $row[$column]);
          $this->items[$row[$this->column]]=$row["id"];
      } 
   }
   function checkInsert($values){
      foreach($values as $val){
         if(!isset($this->items[$val])){
           echo "Inserting $val\n";
           DB::insert($this->table,array("id"=>0,"name"=>$val));
           $this->items[$val]=DB::insertId();
         }
      }
   }
   function getItems(){
      return $this->items;
   }
}

class Issues extends Inserter{
   function __construct($journals,$values){
       foreach($values as $ble){
       foreach($ble as $h=>$j){
          if(!isset($this->items[$h])){
             $journal_id = $journals[$j["journal"]];
             $res=DB::query("SELECT id FROM issue WHERE number = %s AND year = %i AND volume = %i AND journal_id = %i",$j["number"],$j["year"],$j["volume"],$journal_id);
             if(DB::count()==0){
               DB::insertIgnore("issue",array("volume"=>$j["volume"],"year"=>$j["year"],"number"=>$j["number"],"journal_id"=>$journal_id));
               $this->items[$h]=DB::insertId();
             }else{
               //$this->items[$h] = DB::queryFirstField(); 
             }  
             //echo $h ." : ".$hash."\n";*/
          }  
       }}
   }
}

?>
