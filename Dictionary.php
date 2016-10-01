<?php
	/**
	 * Class Dictionary
	 *
	 * Cria lê e armazena as palavras num dicionário
	 */
	class Dictionary {
		# Caracters que serão ignorados
		private $removeChars = array('(', ')', '»', '!', '?', ' es ', ' não ', ' és ', ' tu ', '.', ',', ' - ', '—', ' a ', ' e ', ' i ', ' o ', ' u ', ' de ', ' em ', ' por ', ' os ', ' na ', ' no ', ' não ', ' do ', ' dos ', ' nos ', ' pelo ', ' pelos ', ' à ', ' às ', ' as ', ' aí ', ' é ', ' eles ', ' são ', ' para ', ' da ', ' das ', ' na ', ' nas ', ' pela ', ' pelas ', ' um ', ' uns ', ' dum ', ' duns ', ' num ', ' nuns ', ' uma ', ' umas ', ' duma ', ' dumas ', ' numa ', ' numas ', ' que ', ' eu ', ' você ', ' nós ', ' nos ', ' com ', ' se ', ' já ', ' há ', ' foi ', ' me ', ' meu ', ' seu ', ' nosso ', ' tinha ', ' minha ', ' mais ', ' era ', ' mas ', ' sua ', ' se ', ' mim ', ' ser ', ' ou ', '"');
		private $documentsHaveWord = array();
		private $listOfFrequency = array();
		private $dictionaryTFIDF = array();
		private $dictionaryTFIDFSearch = array();
		private $similarityDegree = array();
		private $maxDocuments;

		public function __construct($maxDocuments) {
			$this->maxDocuments = $maxDocuments;
		}

		/**
		 * Constrói dicionário de palavras
		 *
		 * @return void;
		 */
		public function buildDictionary() {
			$dictionaryTFIDF = array();
			$allWords = $this->readTXT();
			foreach ($allWords as $word) {
				for ($i = 1; $i <= $this->maxDocuments; $i++) {
					$doc = 'Doc' . $i;
					$allWordsOfThisDoc = array();
					$file = fopen('textos/file' . $i . '.txt', 'r');
					while (!feof($file)) {
						$linha = strtolower(fgets($file, 4096));
						$linha = $this->cleanString($linha);
						$textAux = explode(' ', $linha);

						# Concatena as palavras das linhas num array
						foreach ($textAux as $value) {
							if (strlen($value) > 2) { # descarta artigos ou letras que sobraram com 2 ou 1 letra
								$allWordsOfThisDoc[] = $value;
							}
						}
					}
					$frequencyOfWord = array_count_values($allWordsOfThisDoc);
					if (in_array($word, $allWordsOfThisDoc)) {
						# TF = (1 + log(frequencia do termo naquele doc))
						$tf = (1 + (log($frequencyOfWord[$word], 2)));

						# IDF = log(Número de documentos/número de docs que o termo aparece)
						$idf = log(($this->maxDocuments / count($this->documentsHaveWord[$word])), 2);
						$dictionaryTFIDF[$doc][$word] = $tf * $idf;
						$this->listOfFrequency[$doc][$word] = $frequencyOfWord[$word];
					} else {
						$dictionaryTFIDF[$doc][$word] = 0;
						$this->listOfFrequency[$doc][$word] = 0;
					}
				}
			}
			$this->dictionaryTFIDF = $dictionaryTFIDF;
			$_SESSION['dictionaryTFIDF'] = $dictionaryTFIDF;
		}

		/**
		 * Lê vários txts no formato file . $i . txt
		 *
		 * @return array $allWords
		 */
		private function readTXT() {
			$allWords = array();
			for ($i = 1; $i <= $this->maxDocuments; $i++) {
				$doc = 'Doc' . $i;
				$file = fopen('textos/file' . $i . '.txt', 'r');
				while (!feof($file)) {
					$linha = strtolower(fgets($file, 4096));
					$linha = str_replace($this->removeChars, ' ', $linha);
					$linha = $this->cleanString($linha);
					$textAux = explode(' ', $linha);

					# concatena as palavras das linhas num array
					foreach ($textAux as $value) {
						if (strlen($value) > 2) { # descarta artigos ou letras que sobraram com 2 ou 1 letra
							$allWords[] = $value;
							$this->documentsHaveWord[$value][$doc] = 1;
						}
					}
				}
			}
			$_SESSION['documentsHaveWord'] = $this->documentsHaveWord;
			$allWords = array_unique($allWords);
			return $allWords;
		}

		/**
		 * Limpa a string de caracteres sujos (acentos, hifens, etc)
		 *
		 * @param $string
		 *
		 * @return string
		 */
		public function cleanString($string) {
			$string = str_replace(array('[\', \']'), '', $string);
			$string = preg_replace('/[0-9]+/', '', $string);
			$string = preg_replace('/\[.*\]/U', '', $string);
			$string = preg_replace('/&(amp;)?#?[a-z0-9]+;/i', '-', $string);
			$string = htmlentities($string, ENT_COMPAT, 'utf-8');
			$string = preg_replace('/&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);/i', '\\1', $string);
			return trim($string);
		}

		/**
		 * Calculate the similarity of terms and documents
		 *
		 * @param $search
		 *
		 * @return string
		 */
		public function calculateSimilarity($search) {
			$this->dictionaryTFIDF = @$_SESSION['dictionaryTFIDF'];
			$this->dictionaryTFIDFSearch = @$_SESSION['dictionaryTFIDFSearch'];
			$this->documentsHaveWord = @$_SESSION['documentsHaveWord'];
			$this->similarityDegree = array();
			$result = '';
			$search = explode(' ', $search);
			$search = array_diff($search, ['and']);
			$search = array_diff($search, ['or']);
			$search = array_diff($search, ['not']);
			$search = array_values($search);

			if (count($search) == 1) {
				$tfidfSearch = $this->calculateTFIDFSearch($search[0]);
				for ($i = 1; $i <= $this->maxDocuments; $i++) {
					$doc = 'Doc' . $i;
					if (isset($this->dictionaryTFIDF[$doc][$search[0]])) {
						$numerador = ($this->dictionaryTFIDF[$doc][$search[0]] * $tfidfSearch);
					} else {
						$numerador = 0;
					}
					$denominador = 0;
					foreach ($this->dictionaryTFIDF[$doc] as $d) {
						$denominador += pow($d, 2);
					}
					$denominador = sqrt($denominador);
					$denominador *= sqrt(pow($tfidfSearch, 2));
					if ($numerador == 0) {
						$similarity = 0;
					} else {
						$similarity = ($numerador / $denominador) >= 0 ? ($numerador / $denominador) : 0;
					}
					$this->similarityDegree[$doc] = $similarity;
				}
			} else if (count($search) == 2) {
				$tfidfSearch = $this->calculateTFIDFSearch($search);
				for ($i = 1; $i <= $this->maxDocuments; $i++) {
					$doc = 'Doc' . $i;
					if (count($tfidfSearch) == 1) {
						$numerador = ($this->dictionaryTFIDF[$doc][$search[0]] * $tfidfSearch[0]);
						$numerador += ($this->dictionaryTFIDF[$doc][$search[0]] * $tfidfSearch[0]);
						$denominador = 0;
						foreach ($this->dictionaryTFIDF[$doc] as $d) {
							$denominador += pow($d, 2);
						}
						$denominador = sqrt($denominador);
						$denominador *= (sqrt(pow($tfidfSearch[0], 2) * 2));
						if ($numerador == 0) {
							$similarity = 0;
						} else {
							$similarity = ($numerador / $denominador) >= 0 ? ($numerador / $denominador) : 0;
						}
						$this->similarityDegree[$doc] = $similarity;
					} else if (count($tfidfSearch) == 2) {
						if (isset($this->dictionaryTFIDF[$doc][$search[0]])) {
							$numerador = ($this->dictionaryTFIDF[$doc][$search[0]] * $tfidfSearch[0]);
						} else {
							$numerador = 0;
						}
						if (isset($this->dictionaryTFIDF[$doc][$search[1]])) {
							$numerador += ($this->dictionaryTFIDF[$doc][$search[1]] * $tfidfSearch[1]);
						} else {
							$numerador += 0;
						}
						$denominador = 0;
						foreach ($this->dictionaryTFIDF[$doc] as $d) {
							$denominador += pow($d, 2);
						}
						$denominador = sqrt($denominador);
						$denominador *= (sqrt(pow($tfidfSearch[0], 2) + pow($tfidfSearch[1], 2)));
						if ($numerador == 0) {
							$similarity = 0;
						} else {
							$similarity = ($numerador / $denominador) >= 0 ? ($numerador / $denominador) : 0;
						}
						$this->similarityDegree[$doc] = $similarity;
					}
				}
			} else if (count($search) > 2) {
				unset($_SESSION['similarityDegree']);
				unset($_SESSION['dictionaryTFIDFSearch']);
				return 'No máximo 2 termos por busca';
			}
			$_SESSION['similarityDegree'] = $this->similarityDegree;
			return $result;
		}

		/**
		 * Calculate TF-IDF for search terms
		 *
		 * @param $search
		 *
		 * @return mixed array or string
		 */
		public function calculateTFIDFSearch($search) {
			if (count($search) == 1) {
				# TF = (1 + log(frequencia do termo naquele doc))
				$tf = (1 + (log(1, 2)));
				
				# IDF = log(Número de documentos/número de docs que o termo aparece)
				if (isset($this->documentsHaveWord[$search])) {
					$idf = log(($this->maxDocuments / count($this->documentsHaveWord[$search])), 2);
				} else {
					$idf = 0;
				}
				$this->dictionaryTFIDFSearch = json_encode(array($search => $tf * $idf));
				$_SESSION['dictionaryTFIDFSearch'] = $this->dictionaryTFIDFSearch;
				return $tf * $idf;
			} else if (count($search) == 2) {
				# TF = (1 + log(frequencia do termo naquele doc))
				# IDF = log(Número de documentos/número de docs que o termo aparece)
				if ($search[0] != $search[1]) {
					$tf = (1 + (log(1, 2)));
					if (isset($this->documentsHaveWord[$search[0]])) {
						$idf1 = log(($this->maxDocuments / count($this->documentsHaveWord[$search[0]])), 2);
					} else {
						$idf1 = 0;
					}
					if (isset($this->documentsHaveWord[$search[1]])) {
						$idf2 = log(($this->maxDocuments / count($this->documentsHaveWord[$search[1]])), 2);
					} else {
						$idf2 = 0;
					}
					$this->dictionaryTFIDFSearch = json_encode(array($search[0] => $tf * $idf1, $search[1] => $tf * $idf2));
					$_SESSION['dictionaryTFIDFSearch'] = $this->dictionaryTFIDFSearch;
					return array($tf * $idf1, $tf * $idf2);
				} else {
					$tf = (1 + (log(2, 2)));
					$idf = log(($this->maxDocuments / count($this->documentsHaveWord[$search[0]])), 2);
					$this->dictionaryTFIDFSearch = json_encode(array($search => $tf * $idf));
					$_SESSION['dictionaryTFIDFSearch'] = $this->dictionaryTFIDFSearch;
					return $tf * $idf;
				}
			} else {
				echo "Pesquisa inválida";
				exit;
			}
		}
	}