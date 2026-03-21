document.addEventListener('DOMContentLoaded', function(){
	let modalRedirectLink;
	let idParcela;

	$("#btnAlteraDias").hide();

	var valorAntigo = 0;

	var num = $("#diasParcela").val();
	var interval;

	//Pega o valor de dias digitado e espera a pessoa para de digitar para calcular o valor dos juros e multa
	document.getElementById('diasParcela').oninput = function(){

		if ($("#diasParcela").val() > valorAntigo || $("#diasParcela") < 0) {
			$("#diasParcela").val(valorAntigo);
		}

		if ($("#diasParcela").val() != "") {
			$("#diasParcela").val($("#diasParcela").val().match(/\d+/g).join(""));
			clearTimeout(interval);	
			interval = setTimeout(alteraDias,3000);					
		}
		
		
		if ($("#diasParcela").val() != 0 && $("#diasParcela").val() != 1 && $("#diasParcela").val() != 2 && $("#diasParcela").val() != 3 && $("#diasParcela").val() != 4 && $("#diasParcela").val() != 5 && $("#diasParcela").val() != 6 && $("#diasParcela").val() != 7 && $("#diasParcela").val() != 8 && $("#diasParcela").val() != 9) {
			return false;
		}

	}

	$("#payModalButton").click(function(){
		// alteraJuros();
		location.href = modalRedirectLink;
		return false; 
	})

	//Função que Pega os Dados do formulário e cria os valores dos modais
	var pegaDados = function()
	{			
		$.ajax({
		    type : 'POST',
		    dataType : 'json',
		    url : 'ajax-tab.php',
		    data : 
		    {
		        //parâmetros obrigatórios
		        ajax : true,
		        controller : 'AdminAgInstallmentsInstallment',
		        action : 'MandaDadosParcela',
		        token : tokenInstallments,
		        idParcela:idParcela,

		        //demais dados a serem utilizados pelo controller
		        foo : 'bar',
		        contentType: 'application/json',
		    },
		})
		.done(function(d)
		{
			$("#vencParcela").html("\t"+d.resposta['vencimento']);
			$("#valorParcela").html("\t"+d.resposta['valor']);
			$("#jurosParcela").html("\t"+d.resposta['juros']);
			$("#multaParcela").html("\t"+d.resposta['multa']);
			$("#totalParcela").html("\t"+d.resposta['total']);
			$("#diasParcela").attr("min",0);
			$("#diasParcela").attr("max",d.resposta['dias']);
			$("#diasParcela").val(d.resposta['dias']);
			valorAntigo = d.resposta['dias'];
			

		})
		.fail(function(f){
			
		});		
	}


	//Função responsável por mudar os dias de atraso
	var alteraDias = function()
	{
		$dias = $("#diasParcela").val();
		

		$.ajax({
		    type : 'POST',
		    dataType : 'json',
		    url : 'ajax-tab.php',
		    data : 
		    {
		        //parâmetros obrigatórios
		        ajax : true,
		        controller : 'AdminAgInstallmentsInstallment',
		        action : 'AlteraDias',
		        token : tokenInstallments,
		        idParcela:localStorage.getItem("idParcela"),
		        dias:$dias,

		        //demais dados a serem utilizados pelo controller
		        foo : 'bar',
		        contentType: 'application/json',
		    },
		})
		.done(function(d)
		{			
			
			$("#multaParcela").html("\t"+d.resposta['multa']);
			$("#jurosParcela").html("\t"+d.resposta['juros']);
			$("#totalParcela").html("\t"+d.resposta['total']);
			$("#diasParcela").val(d.resposta['dias']);
			// $("#payModalButton").attr("href",h);
		})
		.fail(function(f){
			
		});	

	}

	

	//Função responsável por alterar o valor e a taxa de juros de acordo com a quantidade de dias de atraso
	var alteraJuros = function()
	{
		$juross = $("#jurosParcela").text();
		

		$.ajax({
		    type : 'POST',
		    dataType : 'json',
		    url : 'ajax-tab.php',
		    data : 
		    {
		        //parâmetros obrigatórios
		        ajax : true,
		        controller : 'AdminAgInstallmentsInstallment',
		        action : 'AlteraJuros',
		        token : tokenInstallments,
		        idParcela:localStorage.getItem("idParcela"),
		        valorJuros:$juross,

		        //demais dados a serem utilizados pelo controller
		        foo : 'bar',
		        contentType: 'application/json',
		    },
		})
		.then(function(){

		});
	}

	
    $(".payButton").click(function(){    	    
		modalRedirectLink = $(this).prop('href');
		idParcela = $($(this).closest('tr').find('td')[0]).text().trim();

		$('#modalParcela').modal();
	    pegaDados();	   
	});
})
