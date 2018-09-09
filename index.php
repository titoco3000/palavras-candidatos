<?php

// testes de inclusão do pdf
include 'pdfparser-master/vendor/autoload.php';


function retornaResumo($pos , $texto){
  $pos_ini = ($pos-300 < 0) ? 0 : $pos-300 ;
  $trecho = substr($texto ,  $pos_ini , 600 );
  return $trecho;
}

function localiza($chave='' , $texto='')
{

  $html = strtolower($texto);
  $needle = ' '.$chave.' ';
  $lastPos = 0;
  $positions = array();

while (($lastPos = strpos($html, $needle, $lastPos))!== false) {
    $positions[] = $lastPos;
    $lastPos = $lastPos + strlen($needle);
}

// Displays 3 and 10
// foreach ($positions as $value) {
//     echo $value ."<br />";
// }
$array_resultados = $positions;
  return $array_resultados;
}

function tirarAcentos($string){
    return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
}


// get_txt_from_globo();

function get_txt_from_globo(){
	// pega o html do site
	$html = file_get_contents('https://oglobo.globo.com/brasil/leia-as-entrevistas-dos-presidenciaveis-ao-globo-22941226');
	// instancia a classe DOMDocument
	$doc = new DOMDocument('1.0', 'UTF-8');
	// carrega o html no DOMDocument
	libxml_use_internal_errors(true);

	$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
	// pega todas as divs do html
	$divs = $doc->getElementsByTagName('div');
	foreach($divs as $div) {
		// para cada div (foreach) verifica se ela tem as classes 'capituloPage corpo novo large-16 columns' .
		// Estamos fazendo essa verificação porque vimos que cada entrevista esta dentro de uma div com essas classes
		if ($div->getAttribute('class') == 'capituloPage corpo novo large-16 columns') {

			// pega o conteudo da tag <h2> do html
			$nome = $div->getElementsByTagName('h2');

			// separa o primeiro do segundo nome em um array
			$nome = explode(' ', $nome[0]->nodeValue);
			$nome_com_acento = $nome[0] . " " . $nome[1];

			// usa strtolower para deixar minusculo e a função tirar acentos, isso mesmo, para tirar os acentos
			$pasta = strtolower(tirarAcentos($nome[0]).'-'.tirarAcentos($nome[1]));

			// remove quebra de linha que existia na string $pasta
			$pasta = preg_replace( "/\r|\n/", "", $pasta );

			// cria o diretorio com o nome do político
			if (!file_exists('candidatos/'.$pasta)) {
			    mkdir('candidatos/'.$pasta, 0777, true);
			}
			// pega os paragrafos e adiciona na variavel $content
			$paragrafos = $div->getElementsByTagName('p');
			$content = "";
			foreach ($paragrafos as $paragrafo) {
				$perguntas = $paragrafo->getElementsByTagName('strong');

				//////////////////////////////////////////////////////////////////////
				//    Queremos remover as perguntas? se sim é só descomentar abaixo	//
				//     foreach ($perguntas as $pergunta) {							//
				// 	     $pergunta->parentNode->removeChild($pergunta);			 	//
				// 	   }															//
				// 																	//
				//////////////////////////////////////////////////////////////////////
				$content .= $paragrafo->nodeValue . "\n\n";
			}
			// Salva o conteudo no arquivo entrevista-globo.txt
			$fp = fopen('candidatos/'.$pasta."/entrevista-globo.txt","wb");
			fwrite($fp,$content);
			fclose($fp);

			// Salva o nome do candidato no arquivo nome.txt
			$fp = fopen('candidatos/'.$pasta."/nome.txt","wb");
			fwrite($fp,$nome_com_acento);
			fclose($fp);
		}
	}
}

 ?>
 <!DOCTYPE html>
 <html lang="pb" dir="ltr">
   <head>
     <meta charset="utf-8">
     <title>Busca Palavras de Candidatos</title>
   </head>
   <body>
     <form class="" action="index.php" method="get">
       <input type="text" name="busca" value="">
       <label for="busca">Busca</label>
       <input type="submit" name="" value="só dar enter">
     </form>
	 <div id="resultados-globo" class="resultados">
		 <h2>Resultados</h2>
		 <?php if(isset( $_GET["busca"]) &&  $_GET["busca"] != ""){
		   $palavraChave =  $_GET["busca"];
		   //print_r(localiza($palavraChave , $entrevista));

		   $dirs = array_filter(glob('candidatos/*'), 'is_dir');
		   // para cada candidato
		   foreach ($dirs as $dir) {
			   ?>
			   <div class="cada-candidato">
				   <?php
			 	  // pega o nome
			 	  $nome = file_get_contents($dir.'/nome.txt');
			 	  // pega entrevista
			 	  if (file_exists($dir.'/entrevista-globo.txt')) {
					  $entrevista = file_get_contents($dir.'/entrevista-globo.txt');
					  // pega palara chave na entrevista
    			 	  $palavras_encontradas_entrevista = localiza($palavraChave , $entrevista);
    			 	  // para cada palavra chave encontrada pega o resumo
    				  ?>
    				  	<h3>Candidato: <?php echo $nome; ?></h3>

    				  <?php
					  if (count($palavras_encontradas_entrevista)>0) {
						  ?>
						  <h4>Entrevista O Globo:</h4>
						  <?php
						  foreach ( $palavras_encontradas_entrevista as $key => $value) {
	    					  ?>
	    					  <div class="cada-resultado">
	    						  <?php

	        			 		$resumo = retornaResumo($value , $entrevista)."<br>";
	        			 	  	// a função str_replace esta substituindo a palavra chave por <b> palava chave</b>
	        			 		$resumo_negrito = str_replace($palavraChave,'<b>'.$palavraChave.'</b>',$resumo);
	        			 	  	echo $resumo_negrito;
	    						?>
	    					  </div>
	    					  <?php
	        			  }
					  }
			 	  }

				  ?>
					<?php
					$parser = new \Smalot\PdfParser\Parser();
					$pdf = $parser->parseFile($dir.'/programa.pdf');
					$plano_texto = $pdf->getText();
					$palavras_encontradas_plano = localiza($palavraChave , $plano_texto);
					// print_r($palavras_encontradas_plano);
					if (count($palavras_encontradas_plano)>0) {
						?>
						<h4>Programa de governo:</h4>
						<?php
						foreach ( $palavras_encontradas_plano as $key => $value) {
						 ?>
						 <div class="cada-resultado">
							 <?php
						   $resumo = retornaResumo($value , $plano_texto)."<br>";
						   // a função str_replace esta substituindo a palavra chave por <b> palava chave</b>
						   $resumo_negrito = str_replace($palavraChave,'<b>'.$palavraChave.'</b>',$resumo);
						   echo $resumo_negrito;
						   ?>
						 </div>
						 <?php
						}
					}
					?>
			   </div>
			   <?php
		   }
		 }
		 ?>
	 </div>
   </body>
 </html>
