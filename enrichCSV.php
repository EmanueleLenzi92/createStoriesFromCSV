<?php

// directory di tutti i csv da modificare
$path    = "C:/xampp/htdocs/Moving400Stories/stories/";
$files = scandir($path);


// scorro tutti i csv da modificare
for($i=2; $i<sizeOf($files); $i++){


	$arrayJson= [];	
	
	$contaRighe=0;
	$file = fopen("C:/xampp/htdocs/Moving400Stories/stories2/".$files[$i], 'r');
		
	//scorro le linee del csv
	while (($line = fgetcsv($file)) !== FALSE) {
		
		//metto la riga in un array row e calcolo la posizione + altri dati a seconda del titolo dell'evento
		$row= array();
		if($contaRighe==0){
			$position= "0";
		} else {
			
			if($line[0] == "Interest on this VC"){
				$position= "7";
			} else if($line[0] == "Mountain landscape and reference chains"){
				$position= "2";
			} else if($line[0] == "Key local assets"){
				$position= "8";
				//delete about the lau
				$line[1]= substr($line[1], 0, strpos($line[1], "About the LAU"));
			} else if($line[0] == "Challenges"){
				$position= "9";
			} else if($line[0] == "Innovation"){
				$position= "10";
				$line[1]= preg_replace("/VC/", "VC (Value Chain)", $line[1], 1);
			} else if($line[0] == "Geography and population"){
				$position= "3";
				$line[1]= preg_replace("/LAU/", "LAU (Local Administrative Unit)", $line[1], 1);
				$line[2]= "population density, altimetry, population";
				$line[3]= "https://www.wikidata.org/wiki/Q22856, https://www.wikidata.org/wiki/Q1309100, https://www.wikidata.org/wiki/Q2625603";
			} else if($line[0] == "Income and gross value added"){
				$position= "4";
				$line[1]= str_replace("NUTS3","Province",$line[1]);
				$line[1]= str_replace("NUTS2","Region",$line[1]);
				$line[2]= "gross value added, tertiary sector of the economy, secondary sector of the economy, primary sector of the economy";
				$line[3]= "https://www.wikidata.org/wiki/Q994873, https://www.wikidata.org/wiki/Q55638, https://www.wikidata.org/wiki/Q55639, https://www.wikidata.org/wiki/Q55640";
			} else if($line[0] == "Tourism"){
				$position= "5";
				$line[1]= preg_replace("/LAU/", "LAU (Local Administrative Unit)", $line[1], 1);
				$line[1]= str_replace("NUTS2","Region",$line[1]);
				$line[2]= "tourism industry";
				$line[3]= "https://www.wikidata.org/wiki/Q9323634";
			} else if($line[0] == "Employment"){
				$position= "6";
				$line[1]= str_replace("NUTS3","Province",$line[1]);
				$line[1]= str_replace("NUTS2","Region",$line[1]);
				$line[2]= "employment, tertiary sector of the economy, secondary sector of the economy, primary sector of the economy";
				$line[3]= "https://www.wikidata.org/wiki/Q656365, https://www.wikidata.org/wiki/Q55638, https://www.wikidata.org/wiki/Q55639, https://www.wikidata.org/wiki/Q55640" ;
			} else if($line[0] == "Concluding remarks"){
				$position= "11";
			} else {
				$position= "1";
				$line[1]= preg_replace("/VC/", "VC (Value Chain)", $line[1], 1);
			}
			 
		
		}
		
		// esclude descrizioni vuote
		if($line[1] != ""){

			
			
			//metto tutto in un array
			array_push($row, $line[0], $line[1] ,$line[2],$line[3],$line[4],$line[5],$line[6],$line[7], $position);
			array_push($arrayJson,$row);
		
		}
		
		
		
		$contaRighe++;
		
	
	}
	
	fclose($file);	
	
	
	
	// ordina l'array per la posizione (colonna8)
 	usort($arrayJson, function ($item1, $item2) {
		return $item1[8] <=> $item2[8];
	}); 
	
	//print_r($arrayJson);
	


	
 	// scrivo il nuovo csv nella nuova cartella
	$fp = fopen("C:/xampp/htdocs/stories2/".$files[$i], 'w');  
	// scrivo tutte le linee tranne l'ultima (position)
	for ($k=0; $k<sizeOf($arrayJson);$k++) {
		unset($arrayJson[$k][sizeOf($arrayJson[$k])-1]);
		fputcsv($fp, $arrayJson[$k]);
	}  
	fclose($fp); 


}

?>