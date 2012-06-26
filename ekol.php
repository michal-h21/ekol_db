<html>
<head>
<title>Ekologicka databaze</title>
</head>
<body><table>
<?php
//require('./iso2709.inc.php');

require_once 'meekrodb.2.0.class.php';

class isoRecord{
      var $fields=array();
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
      function cleanNumbers($s){
         return iconv('CP852','UTF-8',str_replace(array("\n","\r",'
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


class Hash{
  function getHash($s){
      return iconv("UTF-8","ASCII//TRANSLIT",mb_strtolower($s,'UTF-8'));
  }
}

class JournalIssue extends Hash{
  function getUri($journal,$vol,$issue){
     return parent::getHash($journal) . "_{$vol}_$issue";
  }
}

$test="EKO07.ISO";
$zaznamy=explode("##",file_get_contents($test));
echo count($zaznamy);
//$zaznam=$zaznamy[1];
$i=0;
//$p=new iso2709_record($zaznam[1]);
//$zaznamy=array_slice($zaznamy,0,10);
//print_r($p);
$id=array();
foreach($zaznamy as $zaznam0){
//   foreach(explode("#",$zaznam) as $radek){
//     $zaznam=str_replace("\n",'',$radek);                           
//     echo '<div style="margin:3px;border:1px solid black;">'.$i.' - '.$radek."</div>";
//     $i++;
//   }
  $rec=new isoRecord($zaznam0);
  if($rec->getField("source")){
	  $journal= array_shift($rec->getField("source"));
	 // $volume = array_shift($rec->getField("volume"));
	 // $issue =array_shift($rec->getField("number"));
	  array_push($id,$journal);
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

$result = DB::query("SELECT * FROM journal WHERE match (title) against('eko* envi* bedrník' IN BOOLEAN MODE);");
/*echo mysql_numrows($result)."\n";
while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
    printf("ID: %s  Name: %s\n", $row[0], $row[1]);  
}
*/
foreach($result as $row){
  printf("ID: %s  Name: %s\n", $row["journal_id"], $row["title"]);
}
?>
</table>
</body>
</html>
