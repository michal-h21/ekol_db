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
   function getId($hash){
      return $this->items[$hash];
   }
}

class Issues extends Inserter{
   function __construct($journals,$values){
       foreach($values as $ble){
       foreach($ble as $h=>$j){
          if(!isset($this->items[$h])){
             $journal_id = $journals[$j["journal"]];
             $res=DB::query("SELECT id FROM issue WHERE number = %s AND year = %i AND volume = %i AND journal_id = %i AND comment=%s",$j["number"],$j["year"],$j["volume"],$journal_id,$j["comment"]);
             if(DB::count()==0){
               DB::insertIgnore("issue",array("volume"=>$j["volume"],"year"=>$j["year"],"number"=>$j["number"],"journal_id"=>$journal_id,"comment"=>$j["comment"]));
               $this->items[$h]=DB::insertId();
             }else{
               foreach($res as $row)$this->items[$h] = $row["id"]; 
             }  
             //echo $h ." : ".$hash."\n";*/
          }  
       }}
   }
}

class Articles extends Inserter{
  function __construct($articles,$authors,$issues){
     foreach($articles as $a){
        $issue_id=$issues->getId($a["hash"]);
        $h=ArticleHash::getUri($a["title"],$a["pages"],$issue_id);
       $res=DB::query("SELECT id FROM article WHERE title_hash=%i AND pages=%s AND issue_id=%i",crc32($a["title"]),$a["pages"],$issue_id);
        if(DB::count()==0){
          DB::insertIgnore("article",array("title"=>$a["title"],"abstract"=>$a["abstract"],"title_hash"=>crc32($a["title"]),"pages"=>$a["pages"],"issue_id"=>$issue_id));
          $this->items[$h] = DB::insertId();
          echo "Inserting {$a['title']} - {$a['pages']}\n";
          $curr_id=$this->getId($h);
          if(is_array($a["authors"]))foreach($a["authors"] as $author)DB::insertIgnore("article_author",array("article_id"=>$curr_id,"author_id"=>$authors->getId($author)));
        }else{
           foreach($res as $row)$this->items[$h] = $row["id"]; 
        }
        $curr_id=$this->getId($h);
        if(!$curr_id)echo "Nemám id od {$a['title']} - {$a['pages']}";
     }  
  }
}

class Hash{
  function getHash($s){
      return iconv("UTF-8","ASCII//TRANSLIT",mb_strtolower($s,'UTF-8'));
  }
}

class JournalIssue extends Hash{
  function getUri($journal,$vol,$issue,$comment=""){
     return parent::getHash($journal) . "_{$vol}_$issue:$comment";
  }
}

class ArticleHash extends Hash{
  function getUri($title,$pages,$issue){
    return crc32($title)."_".$pages."_".$issue;
  }
}
?>
