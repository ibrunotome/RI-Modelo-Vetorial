<?php
	session_start();
	set_time_limit(400);
	$maxDocuments = 80;
	require_once('Dictionary.php');
	$dictionary = new Dictionary($maxDocuments);
	if (!isset($_SESSION['dictionaryTFIDF'])) {
		$dictionary->buildDictionary();
		$dictionaryWords = $_SESSION['dictionaryTFIDF'];
	} else {
		$dictionaryWords = $_SESSION['dictionaryTFIDF'];
	}
	
	# Lista a tabela de frequências se for requisitado
	if (isset($_GET['listTFIDF']) && $_GET['listTFIDF'] == 'true') {
		$json = json_encode($dictionaryWords);
		echo '<a href="./"><< VOLTAR</a>';
		echo '<pre>TF-IDF<br><br>';
		print_r(json_decode($json, TRUE));
		echo '</pre>';
		exit;
	}

	# Se receber um post do formulário, ativa a busca
	if (isset($_POST['search'])) {
		$searchTerm = strtolower($_POST['search']);
		$searchTerm = $dictionary->cleanString($searchTerm);
		echo $dictionary->calculateSimilarity($searchTerm);
	}
?>

<html>
	<head>
		<meta charset="utf-8">
		<title>Trabalho II - Modelo Vetorial</title>
	</head>
	<body style="text-align: center">
		<br>
		<form method="post">
			<input type="text"
			       name="search"
			       autocomplete="off"
			       autofocus
			       placeholder="Pesquisar...">
			<input type="submit"
			       value="Buscar">
		</form>
		<br>
		<a href="./index.php?listTFIDF=true">Atividade 1: Calcular TF-IDF</a>
		<br><br>
		Atividade 2: Realize uma busca para imprimir o TF-IDF dos termos pesquisados (No máximo 2 termos)
		<br><br>
		<?= @$_SESSION['dictionaryTFIDFSearch'] ?>
		<br><br>
		Atividade 3: Realize uma busca e a similaridade será impressa na tela
		<br><br>
		<pre><?php
				if (isset($_SESSION['similarityDegree'])) {
					arsort($_SESSION['similarityDegree']);
					$json = json_encode($_SESSION['similarityDegree']);
					print_r(json_decode($json, TRUE));
				}
			?></pre>
	</body>
</html>