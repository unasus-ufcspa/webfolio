<?php
	define("HOST","<< Endereço do Servidor de Banco de Dados >>");
	define("PORT","<< Porta do Banco de Dados >>");
	define("BDAD","<< Nome do Banco de Dados >>");
	define("USER","<< Usuário do Banco de Dados >>");
	define("PSWD","<< Senha >>");

	$this->db = pg_connect("host=".HOST." port=".PORT." dbname=".BDAD." user=".USER." password=".PSWD);
?>
