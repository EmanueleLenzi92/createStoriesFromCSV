<?php
//DB POSTGRES CONN


$dbconn = pg_connect("host=$dbhost dbname=$dbname user=$dbuser password=$dbpass")
    or die('Could not connect: ' . pg_last_error());

$mappa=[];
             
$listNations=[
"Q40",    //austria
"Q219",   //bulgaria
"Q34374",  //crete
"Q213",   //Czech Republic
"Q142",    //france
"Q183",    //germany
"Q41",     //grecee
"Q28",     //hungary
"Q38",     //italy
"Q221",     //north macedonia
"Q20",      //norway
"Q45",     //portugal
"Q218",      //romania
"Q403",      //serbia
"Q145",      //UK
"Q22",      //scotland
"Q214",    //slovakia
"Q29",     //spain
"Q39",      //switzerland
"Q43",       //turkey
"Q215",    // slovenia
"Q33"    // finland

];      




// directory di tutti i csv
$path    = "C:/xampp/htdocs/Moving400Stories/stories2/";
$files = scandir($path);

$table="";
$FinalJsonToWriteStorymap= array();
$FinalJsonToWriteStorymap['slides']= array();

// scorro tutti i csv 
for($i=2; $i<sizeOf($files); $i++){

	
	$contaRighe=0;
	
	// apro il singolo csv
	$file = fopen("C:/xampp/htdocs/Moving400Stories/stories2/".$files[$i], 'r');
	
		
	//scorro le linee del csv
	while (($line = fgetcsv($file)) !== FALSE) {
		

		// escludi prima riga (titolo, ecc.)
		if($contaRighe != 0) {
			
			// esclude descrizioni vuote (non ci dovrebbero essere)
			if($line[1] != ""){
				
				// get all entities of first event and title
				if($contaRighe==1){
					$entitiesFirstEvent= $line[2];
					$linkEntitiesFirstEvent= $line[3];
					$narrationTitle= $line[0];
				}
				
				
				// events without entites get entites of first event
				if($line[2]==""){
					 $line[2] = $entitiesFirstEvent;
					 $line[3] = $linkEntitiesFirstEvent;
				}	
				
				
				// get all entities and entities link from csv line
				$entitiesLinkWithoutEmptySpace= str_replace(' ', '', $line[3]);
				$entitiesLink= explode(",",$entitiesLinkWithoutEmptySpace);
				
				$entitiesWithoutEmptySpace= str_replace(' ', '', $line[2]);
				$eneties= explode(",",$line[2]);
				
				// INSERT/CREATE in narration table of db
				if($contaRighe==1){
					
 					$userId= 1;
					$dbName= "adminNarra.".$userId."-" . basename(strtolower($entitiesLink[0]));
					$subjectNarration = basename(strtolower($entitiesLink[0]));

					$sql= "WITH ins AS (INSERT INTO narrations (id_dbname, title, \"user\", subject) VALUES ('".$dbName."', '".pg_escape_string($narrationTitle)."', '".$userId."', '".$subjectNarration."') RETURNING id_dbname) select count(*) from ins";
					$result = pg_query($sql) or die('Error message: ' . pg_last_error());
					
					
					pg_free_result($result);
					
					$sql= "SELECT last_value FROM id_narration_seq";
					$result = pg_query($sql) or die('Error message: ' . pg_last_error());
					while ($row = pg_fetch_row($result)) {
						$idNarra=$row[0];
					}
					pg_free_result($result);
					
					$table= $idNarra.$dbName;
					$sql='CREATE TABLE IF NOT EXISTS "'.$table.'"(
					"id" character(500) primary key,
					"value" jsonb
					);
					';
					$result = pg_query($sql) or die('Error message: ' . pg_last_error());
					pg_free_result($result);
					
					
					$A1Object= array("id"=>$idNarra,"_id"=>"A1","name"=> $narrationTitle, "author"=> "adminNarra.".$userId, "idNarra"=> $idNarra );
					$A1Json = json_encode($A1Object);
					$sql= "INSERT INTO \"".$table."\" (id, value) 
						VALUES ('A1', '". pg_escape_string($A1Json) . "')";
					$result2 = pg_query($sql) or die('Error message: ' . pg_last_error());	 	
					pg_free_result($result2);   
					
					$FinalJsonToWriteStorymap["A1"] = $A1Object;
				
				}
								
	
				
				// for all entities...
				//$arrProps = array();
				$arrProps= (object)[];
				$arraySlideDescription= array("text" => $line[1] .= " <h5>Entities</h5> " ,"headline" => $line[0]);
				
				
				$stopSearchImage=false;
				$selectedImage="";
				$bastaCercareEntitaPerMappa= false;
				for($j=0; $j<sizeOf($entitiesLink); $j++){
					
					
					
					// get wikidata id
					$idEnt= basename($entitiesLink[$j]);
					
					// create props array to save in json of events
					//$arraySongleItem= array($idEnt => array("class"=> "","notes"=> "", "primary"=> array("text"=> "", "title"=> "", "author"=> "", "reference"=> ""), "secondary"=> array("text"=> "", "title"=> "", "author"=> "", "reference"=> "")));
					//array_push($arrProps,$arraySongleItem);
					$arrProps->$idEnt =array("class"=> "other","notes"=> "", "primary"=> array(array("text"=> "", "title"=> "", "author"=> "", "reference"=> "")), "secondary"=> array(array("text"=> "", "title"=> "", "author"=> "", "reference"=> "")));
					
					// get data entities from wikidata
					$query="PREFIX wd: <http://www.wikidata.org/entity/>
					SELECT DISTINCT ?uri ?coordinates ?type ?itName ?enName ?itDesc ?enDesc ?image ?imgMappa ?birth ?death ?foundation ?foundation2 ?completion ?occupation ?position
					WHERE {
					VALUES ?uri {wd:".$idEnt."}
					OPTIONAL {?uri wdt:P31 ?class.
					}OPTIONAL {?class wdt:P279* ?type.
					 VALUES ?type {
					 wd:Q15222213 wd:Q17334923 wd:Q43229 wd:Q8436 wd:Q488383 wd:Q7184903 wd:Q386724 wd:Q234460 wd:Q5 wd:Q186081 wd:Q1190554 wd:Q35120 wd:Q15474042 wd:Q4167836 wd:Q41176 wd:Q8205328 wd:Q5127848 wd:Q27096213
					}}OPTIONAL { ?uri wdt:P18 ?image. }
					OPTIONAL { ?uri wdt:P569 ?birth. }
					OPTIONAL { ?uri wdt:P570 ?death. }
					OPTIONAL { ?uri wdt:P571 ?foundation. }
					OPTIONAL { ?uri wdt:P580 ?foundation2. }
					OPTIONAL { ?uri wdt:P1619 ?completion. }
					OPTIONAL { ?uri wdt:P106 ?occupation. }
					OPTIONAL { ?uri wdt:P39 ?position. }
					OPTIONAL { ?uri rdfs:label ?itName filter (lang(?itName) = 'it'). }
					OPTIONAL { ?uri rdfs:label ?enName filter (lang(?enName) = 'en'). }
					OPTIONAL { ?uri schema:description ?itDesc filter (lang(?itDesc) = 'it'). }
					OPTIONAL { ?uri schema:description ?enDesc filter (lang(?enDesc) = 'en'). }
					OPTIONAL { ?uri wdt:P242 ?imgMappa. }
					OPTIONAL { ?uri wdt:P625 ?coordinates. }
					} limit 50000 ";
					
					$queryEncoded= urlencode($query);
					
					
					$url = "https://query.wikidata.org/sparql?format=json&query=".$queryEncoded;
					
					// curl for result by wikidata
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					$ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';
					curl_setopt($ch, CURLOPT_USERAGENT, $ua);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$output = json_decode(curl_exec($ch));
					curl_close($ch); 
					
					
					// Creo il JSON PER LA MAPPA (considerando solo il primo evento)
					if($contaRighe==1){	
						
						//se un entità è nella lista delle nazioni
						if(in_array($idEnt, $listNations) && !$bastaCercareEntitaPerMappa){
							$bastaCercareEntitaPerMappa=true;
							$elemArray=[];
							$data = $output->results->bindings;
							$elemArray["Title"] = $narrationTitle;
							$elemArray["Country"] = $data[0]->enName->value;
							$coordinates= preg_match('#\((.*?)\)#', $data[0]->coordinates->value, $match);
							$coordinates2= explode(" ",$match[1]);
							$elemArray["Latitude"]= $coordinates2[1];
							$elemArray["Longitude"]= $coordinates2[0];
							$elemArray['Link']= "storymaps/prova_auto/" . $files[$i];
							array_push($mappa, $elemArray);
						}
					}
					

					// create object of entity with wikidata data to save in db
					$singleEntity= array("_id"=> $idEnt, "_rev"=> "", "itName"=>"", "enName"=>"","itDesc"=>"","enDesc"=>"","image"=>"","type"=>array(),"role"=>array());
					if(!empty($output->results->bindings)){
						
						$data = $output->results->bindings;
						
						//loop in wikidata results and save data in my json
						
						//for($k=0; $k<sizeOf($data); $k++){
							
							if( isset($data[0]->itName)){ 
								$singleEntity["itName"] = $data[0]->itName->value;
							}
							if( isset($data[0]->enName)){ 
								$singleEntity["enName"] = $data[0]->enName->value;
							}
							if( isset( $data[0]->itDesc)){ 
								$singleEntity["itDesc"] = $data[0]->itDesc->value;
							}
							if( isset( $data[0]->enDesc)){ 
								$singleEntity["enDesc"] = $data[0]->enDesc->value;
							}
							
							
							// manage the image
								if($line[0] == "Interest on this VC"){
									$selectedImage= "/images/interestVC.png";
									$singleEntity["image"] = $selectedImage;
									$mapColor="#ff9900";
									$eventType="valorisation event";
								} else if($line[0] == "Mountain landscape and reference chains"){
									$selectedImage= "/images/Mountainlandscapechain.png";
									$singleEntity["image"] = $selectedImage;
									$mapColor="#ff9900";
									$eventType="valorisation event";
								} else if($line[0] == "Key local assets"){
									$selectedImage= "/images/keylocalassets.png";
									$singleEntity["image"] = $selectedImage;
									$mapColor="#ff9900";
									$eventType="valorisation event";
								} else if($line[0] == "Challenges"){
									$selectedImage= "/images/challenges.png";
									$singleEntity["image"] = $selectedImage;
									$mapColor="#ff9900";
									$eventType="valorisation event";
								} else if($line[0] == "Innovation"){
									$selectedImage= "/images/innovation.png";
									$singleEntity["image"] = $selectedImage;
									$mapColor="#ff9900";
									$eventType="valorisation event";
								} else if($line[0] == "Geography and population"){
									$selectedImage= "/images/geographyandpopulation.png";
									$singleEntity["image"] = $selectedImage;
									$mapColor="#ff9900";
									$eventType="valorisation event";
								} else if($line[0] == "Income and gross value added"){
									$selectedImage= "/images/IncomeAdded.png";
									$singleEntity["image"] = $selectedImage;
									$mapColor="#ff9900";
									$eventType="valorisation event";
								} else if($line[0] == "Tourism"){
									$selectedImage= "/images/tourism.png";
									$singleEntity["image"] = $selectedImage;
									$mapColor="#ff9900";
									$eventType="valorisation event";
								} else if($line[0] == "Employment"){
									$selectedImage= "/images/employment.png";
									$singleEntity["image"] = $selectedImage;
									$mapColor="#ff9900";
									$eventType="valorisation event";
								} else if($line[0] == "Concluding remarks"){
									$selectedImage= "/images/concludingRemarks.png";
									$singleEntity["image"] = $selectedImage;
									$mapColor="#ff9900";
									$eventType="valorisation event";
								} else {
									
/* 									if(isset( $data[0]->imgMappa) && $j == 0){
										$singleEntity["image"] = $data[0]->imgMappa->value;
										$selectedImage= $data[0]->imgMappa->value;
									} */
									$eventType="natural event";
									$mapColor=" #2eb82e";
									
 									if( in_array($idEnt, $listNations)){
										
										if(isset( $data[0]->imgMappa) && $stopSearchImage==false){
											$stopSearchImage=true;
											$imgName= substr($data[0]->imgMappa->value, strrpos($data[0]->imgMappa->value, '/') + 1);
											$imgUrl= "https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file&wpvalue=".$imgName."&width=700&type=.jpg";	
											$singleEntity["image"] = $imgUrl;
											$selectedImage= $imgUrl;
										}
									} 
								
								}	

							if( isset( $data[0]->type)){ 
								array_push($singleEntity["type"], basename($data[0]->type->value));
								if($data[0]->type == "Q27096213" || $data[0]->type == "Q17334923"){$arrProps->$idEnt["class"] = "place";}
								if($data[0]->type == "Q5"){$arrProps->$idEnt["class"] = "person";}
								if($data[0]->type == "Q234460" || $data[0]->type == "Q386724"){$arrProps->$idEnt["class"] = "work";}
								if($data[0]->type == "Q7184903"){$arrProps->$idEnt["class"] = "concept";}
								if($data[0]->type == "Q43229"){$arrProps->$idEnt["class"] = "organization";}
								if($data[0]->type == "Q41176" || $data[0]->type == "Q8205328" || $data[0]->type == "Q488383" || $data[0]->type == "Q15222213"){$arrProps->$idEnt["class"] = "object";}
							} else {
								array_push($singleEntity["type"], "other");
								$arrProps->$idEnt["class"] = "other";
							
							}								
						//}
					
					};
					
					// SAVE JSON OF SINGLE ENTITIES IN DB
 					$singleEntityJSON = json_encode($singleEntity);
					
   					$sql2= "INSERT INTO \"".$table."\" (id, value) 
						VALUES ('" . $idEnt . "', '". pg_escape_string($singleEntityJSON) . "')
						ON CONFLICT(id) DO NOTHING";
					$result2 = pg_query($sql2) or die('Error message: ' . pg_last_error());
					pg_free_result($result2);   
					//print_r($singleEntityJSON);
					
					
					$FinalJsonToWriteStorymap["items"][$idEnt] =  $singleEntity;
					
					$arraySlideDescription["text"] .=  "<span class='tl-entities'><a onmouseover='$(this).tooltip(); $(this).tooltip(\"show\")' data-toggle='tooltip' title='".$singleEntity["enDesc"]."' target='_blank' href='".$entitiesLink[$j]."'>".$eneties[$j]."</a></span>";
					if($j < (sizeOf($entitiesLink)-1) ){
							$arraySlideDescription["text"] .= " • "; 
					}
				
				}
				
				
				
				
				// create event json to save in db

				$jsonObject = array(
					"_id" => "ev".$contaRighe,
					"text" => $arraySlideDescription,
					"location" => array(
						"name"=>"","lat"=> floatval($line[5]),"lon"=>floatval($line[4]),"zoom"=>10,"line"=>true
					),
					"media" => array("url"=>$selectedImage),
					"date"=> "",					
					"title" => $line[0],
					"latitud" => $line[5],
					"start"=>"",
					"end"=>"",
					"objurl" =>"",
					"end_date"=> array("year"=>"null","month"=>"","day"=>""),
					"start_date"=> array("year"=>"null","month"=>"","day"=>""),
					"type"=>"no type",
					"longitud" => $line[4],
					"unique_id" => "slide-ev".$contaRighe,
					"eventMedia" => $selectedImage,
					"eventMediaCaption" => "",
					"notes" => "",
					"description" => $line[1],
					"position" => $contaRighe,
					"props" => $arrProps,
					"mapMarkerColor"=> $mapColor,
					"type" => $eventType
				);
				
				//echo $line[0] .": " . $selectedImage . "</br>";
				
				

				
				$myJSON = json_encode($jsonObject);
					

				
   				$sql= "INSERT INTO \"".$table."\" (id, value) 
					VALUES ('ev".$contaRighe."', '". pg_escape_string($myJSON) . "')";
				$result2 = pg_query($sql) or die('Error message: ' . pg_last_error());
				pg_free_result($result2);  
				
				$idEven= "ev".$contaRighe;
				$FinalJsonToWriteStorymap["events"][$idEven] = $jsonObject;
				
				

				
				array_push($FinalJsonToWriteStorymap['slides'], $jsonObject);
				
				//var_dump($FinalJsonToWriteStorymap);
				
				

			
			

		
			}
		
		}
		
		
		
		$contaRighe++;
		
	
	}
	
	//write json
	
	if (!file_exists('stories2Storymap/'.$files[$i])) {
		mkdir('stories2Storymap/'.$files[$i], 0777, true);
		chmod('stories2Storymap/'.$files[$i], 0777);
	}

	$fp = fopen('stories2Storymap/'.$files[$i].'/slide.json', 'w');
	fwrite($fp, json_encode($FinalJsonToWriteStorymap));
	fclose($fp);
	chmod('stories2Storymap/'.$files[$i].'/slide.json', 0777);
	

	$html= '<html>
	<head>
		<meta charset="UTF-8">
		<title>Narrative - '. $narrationTitle .'</title>
		<link rel="stylesheet" type="text/css" href="../../../lib/bootstrap.min.css" />
		<link rel="stylesheet" type="text/css" href="../../../lib/narra.css" />
		<script src="../../../lib/jquery-3.2.1.min.js" type="text/javascript" charset="utf-8"></script>
		<script src="../../../lib/jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>
		<script src="../../../lib/bootstrap.min.js" type="text/javascript" charset="utf-8"></script>
		
		<script src="../../../lib/typeahead.bundle.min.js" type="text/javascript" charset="utf-8"></script>
		

		<script src="../../lib/visualization.js" type="text/javascript" charset="utf-8"></script>

		<link rel="stylesheet" href="https://cdn.knightlab.com/libs/storymapjs/0.8.6/css/storymap.css">
		<script type="text/javascript" src="../../../lib/storymap.js"></script>  
		<link rel="stylesheet" type="text/css" href="../../../lib/timeline.css" />
		<script src="../../../lib/timeline-min.js" type="text/javascript" charset="utf-8"></script>
		
		<link rel="stylesheet" type="text/css" href="../../lib/narrativeVisualization.css" />

	</head>

	<body>
		<div id="menu">
			<div id="titleTable">
			<h1>'. $narrationTitle .'</h1>
			</div>
		
			<div class="otherVisual">
			  <button class="dropbtn">Other Visualizations</button>
			  <div class="otherVisual-content">
				<a href="?visualization=map">Storymap</a>
				<a href="?visualization=timeline">Timeline</a>
			  </div>
			</div>
			
			<a href="../../../Search/?user=">
			<div class="otherNarratives">
			  <button class="dropbtn">Search</button>

			</div>
			</a>
			
		</div>
		
		<div id="mapdiv"></div>

		<!-- <script src="../../lib/LoadJsonSlidesAndBugFixSlide.js" type="text/javascript" charset="utf-8"></script>-->
		
		
	</body>
	</html>
	';

	$fp1 = fopen('stories2Storymap/'.$files[$i].'/index.html', 'w');
	fwrite($fp1, $html);
	fclose($fp1);
	chmod('stories2Storymap/'.$files[$i].'/index.html', 0777);	
	
	
	
	fclose($file);	
	

	// TRIPLIFY
	$results = array("entities"=> $FinalJsonToWriteStorymap["items"], "narra"=> $FinalJsonToWriteStorymap["A1"], "events"=> $FinalJsonToWriteStorymap["events"]);
	$dataJsonToTriplify = json_encode($results);
	$myfile1 = fopen("json/" . $table . ".json", "w+") or die("Unable to open file!");
	fwrite($myfile1, $dataJsonToTriplify);
	fclose($myfile1);
	
	$cmd = "java -jar Triplify/triplify.jar json/" . $table . ".json owl/".$table.".owl 2>&1";
	$output=null;
	$retval=null;
	exec($cmd,$output,$retval);
	print_r($output);
	

	// svuoto l'array SLIDE e items per le nuove storie
	$FinalJsonToWriteStorymap['slides']= array();
	unset($FinalJsonToWriteStorymap["items"]);
	unset($FinalJsonToWriteStorymap["events"]);
	
	
	
	

	


}


// write MAP json
$fp = fopen('stories2Storymap/map.json', 'w');
fwrite($fp, json_encode($mappa));
fclose($fp);
print_r ($mappa);

?>