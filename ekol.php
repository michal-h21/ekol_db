<html>
<head>
<title>Ekologicka databaze</title>
</head>
<body><table>
<?php
//require('./iso2709.inc.php');

require_once 'meekrodb.2.0.class.php';
require_once 'inserter.php';


class isoRecord{
      var $fields=array();
      var $encoding="CP852";
      var $header;
      var $directory=array();
      var $records=array();
      var $convertTable=array(
          "450"=>"keywords_cs",
          "460"=>"keywords_en",
          "010"=>"author",
          "100"=>"title",
          "030"=>"owner",
          "200"=>"source",
          "220"=>"year",
          "440"=>"annotation",
          "240"=>"pages",
          "210"=>"volume",
          "230"=>"number",
          "250"=>"vybaveni",
          "470"=>"kody_ministerstva",
          "780"=>"kody_obsahu",
          "790"=>"kody_vyuziti"
      );
      function setEncoding($enc){
         $this->encoding=$enc;
      }
      function isoRecord($recordString){
          $this->records=explode('#',$recordString);
          $metadata=$this->cleanNumbers($this->records[0]);
          $this->header=substr($metadata,0,24);
          $this->directory=str_split(substr($metadata,25),12);     
      }
      function convertField($s){
          $s=strlen($s)>3 ? substr($s,0,3) : $s;
          if(array_key_exists($s,$this->convertTable)) return $this->convertTable[$s];
          else 
          return $s;
      }
      function pretyPrint(){
         echo "Tisknu záznam  : {$this->header}\n";
         //echo "<tr><td>".count($this->directory)."</td><td>".count($this->records)."</td></tr>\n";
         for($i=0;$i<count($this->directory);$i++){
            $field=$this->convertField($this->directory[$i]);
             echo "".$field." : ".$this->cleanNumbers($this->records[$i+1])."\n";
         
         }
      }
      function buildFields(){
         if(count($this->fields)==0){
            for($i=0;$i<count($this->directory);$i++){
               $fieldName = $this->convertField($this->directory[$i]);
               if(array_key_exists($fieldName,$this->fields)){
                 array_push($this->fields[$fieldName],$this->cleanNumbers($this->records[$i+1])); 
               }else{
                 $this->fields[$fieldName]=array($this->cleanNumbers($this->records[$i+1]));
               }
            }  
         }
      } 
      function getField($field){
        $this->buildFields();
        if(array_key_exists($field,$this->fields))
          return $this->fields[$field]; 
        else 
          return false; 
      }
      function getFieldOrNull($field){
         if(isset($this->fields[$field]) && is_array($this->fields[$field]))
            return array_shift($this->fields[$field]);
         else if(isset($this->fields[$field]))
            return $this->fields[$field];
         else
            return null;     
      }
      function cleanNumbers($s){
         return iconv($this->encoding,'UTF-8',str_replace(array("\n","\r",'
'),'',$s));
      }
}
function findAuthor(&$arr,$pos){
         if(strpos($arr[$pos],",")===false){
               return 'False';
         }else {
               return $arr[$pos];
         }
}




//$test="EKO07.ISO";
$test="envir.iso";
$encoding="UTF-8";
$zaznamy=explode("##",file_get_contents($test));
echo count($zaznamy);
//$zaznam=$zaznamy[1];
$i=0;
//$p=new iso2709_record($zaznam[1]);
//$zaznamy=array_slice($zaznamy,0,10);
//print_r($p);
$id=array();
$authors=array();
//$journals = array();
$issues = array();
$articles = array();
$keywords_cs = new RecList("keyword_cs","keyword_cs");
$keywords_en = new RecList("keyword_en","keyword_en");
$kody_vyuziti = new RecList("use_code","use_code");
$kody_obsahu = new RecList("content_code","content_code");
$kody_ministerstva = new RecList("ministry_code","ministry_code");

foreach($zaznamy as $zaznam0){
//   foreach(explode("#",$zaznam) as $radek){
//     $zaznam=str_replace("\n",'',$radek);                           
//     echo '<div style="margin:3px;border:1px solid black;">'.$i.' - '.$radek."</div>";
//     $i++;
//   }
  $rec=new isoRecord($zaznam0);
  $rec->setEncoding($encoding);
  if($rec->getField("source")){
	  $journal= $rec->getFieldOrNull("source");
	  $volume = $rec->getFieldOrNull("volume");
	  $number = explode(",",$rec->getFieldOrNull("number"));
          $comment=""; 
          if(count($number)>1){
             $comment=trim($number[1]);
             $number=trim($number[0]); 
          }else{
             $number=trim(array_shift($number));  
          }
          $pages= $rec->getFieldOrNull("pages"); 
          $title = $rec->getFieldOrNull("title");
          $abstract = $rec->getFieldOrNull("annotation");
          $keywords_cs->add(explode(" ",$rec->getFieldOrNull("keywords_cs")));
          $keywords_en->add(explode(" ",$rec->getFieldOrNull("keywords_en")));
          $kody_vyuziti->add($rec->getFieldOrNull("kody_vyuziti"));
          $kody_obsahu->add($rec->getFieldOrNull("kody_obsahu"));
          $kody_ministerstva->add($rec->getFieldOrNull("kody_ministerstva"));
          $year = array_shift($rec->getField("year"));
          $hash=JournalIssue::getUri($journal,$volume,$number,$comment);
          array_push($issues,array($hash=>array("year"=>$year,"volume"=>$volume,"journal"=>$journal,"number"=>$number,"comment"=>$comment)));   
	  array_push($id,$journal);
          array_push($articles,array("hash"=>$hash,"pages"=>$pages,"title"=>$title,"abstract"=>$abstract,"authors"=>$rec->getField("author")));
         if(is_array($rec->getField("author")))
            foreach($rec->getField("author") as $aut)array_push($authors,$aut);
  }else{
  	$rec->pretyPrint();  
  }
  //echo JournalIssue::getUri($journal , $volume , $issue)."\n";
  //$rec->pretyPrint();
  /*$zaznam=explode("#",$zaznam0);
  $p=explode('0',$zaznam[0]);
  $autor=findAuthor($zaznam,10);
  $delka=(strlen($zaznam[0])-24)/count($zaznam);
  echo "<tr><td>".count($zaznam)."</td><td>".strlen($zaznam[0]).' - '.$delka."</td></tr>\n";
  */
  //echo "<tr><td>".$autor."</td><td>{$zaznam[11]}</td></tr>\n";
}
setlocale(LC_ALL, 'cs_CZ');

 /* mysql_connect("localhost","root","mint") or die (mysql_error()); 
  mysql_select_db("bibeko"); 
  mysql_query("SET NAMES 'utf8'"); */
DB::$user = 'root';
DB::$password = 'mint';
DB::$dbName = 'bibeko';
DB::$encoding = 'utf8';

foreach(array_unique($id,SORT_STRING) as $j){
        //mysql_query("insert into journal (title) values ('".mysql_escape_string($j)."')") or  die (mysql_error());
	echo $j."\n";
}

$result = DB::query("SELECT * FROM journal WHERE match (journal) against('eko* envi* bedrník' IN BOOLEAN MODE);");
/*echo mysql_numrows($result)."\n";
while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
    printf("ID: %s  Name: %s\n", $row[0], $row[1]);  
}
*/
foreach($result as $row){
  printf("ID: %s  Name: %s\n", $row["id"], $row["journal"]);
}
$journals = new Inserter("journal","journal",array_unique($id,SORT_STRING));
//print_r($ins->getItems());

$authors = new Inserter("author","name",array_unique($authors,SORT_STRING));
//print_r($ins->getItems());
$issue_table = new Issues($journals->getItems(),$issues);
$art=new Articles($articles,$authors,$issue_table);
//print_r($issue_table->getItems());
$search = "ekol*";
$res=DB::query("SELECT a.title, j.journal, au.name FROM article AS a LEFT JOIN issue AS i ON a.issue_id = i.id LEFT JOIN journal AS j ON i.journal_id = j.id LEFT JOIN article_author AS a_u ON a.id = a_u.article_id LEFT JOIN author AS au ON a_u.author_id= au.id WHERE MATCH a.title AGAINST (%s In BOOLEAN MODE) OR MATCH a.abstract AGAINST (%s)",$search,$search);
foreach($res as $row){
  print_r($row);
}
$k_cs = $keywords_cs->getInserter();
$keywords_en->getInserter();
$kody_vyuziti->getInserter();
$kody_obsahu->getInserter();
$kody_ministerstva->getInserter();
?>
</table>
</body>
</html>
