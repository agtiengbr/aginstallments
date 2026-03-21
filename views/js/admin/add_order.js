/**
 * Trata da página de criação do pedido pelo BackOffice da loja
 */
$(function(){
	var value_cart;
	var cookie = 0;
	var verificaPreco;

	var resposta;
	var valor_total = $("#total_products").text();
	var tem_esconder = $("#id_order_state");
	var qty_installments;
	
	document.addEventListener('change', function(e){
		var tipo_pagamento = $("#payment_module_name").val();

		if (e.target.id != 'payment_module_name') {
			return;
		}

		if (tipo_pagamento == 'aginstallments') {
			$(tem_esconder).closest('.form-group').hide();
			pegaDados();

			$(".selectQtyInstallments").show();
			$("#selectQtyInstallments_text").show();

			value_cart = $("#total_with_taxes").text();
			window.setInterval(verificaPreco,300);
			$("#id_order_state").val(1);

		} else {
			$(tem_esconder).closest('.form-group').show();
			$(".selectQtyInstallments").remove();
			$("#selectQtyInstallments_text").remove();
		}
	});
// Verifica se o preço está igual ao do total do carnê se não estiver ele puxa a função de calcular o valor das parcelas
	var verificaPreco = function()
	{
		if (value_cart != $("#total_with_taxes").text()) {
			pegaDados();
			value_cart = $("#total_with_taxes").text();
		}
	}

// Pega os dados do pedido e envia para a página php que calcula o valor de cada parcela e insere no pedido
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
		        controller : 'AdminAgInstallmentsInstallmentGroup',
		        action : 'CalcInstallments',
		        token : token_AdminAginstallmentsIntallmentGroup,
		        id_cart: $("#id_cart").val(),

		        //demais dados a serem utilizados pelo controller
		        foo : 'bar',
		        contentType: 'application/json',
		    },
		})
		.done(function(d)
		{
			$(".selectQtyInstallments").remove();
			$("#selectQtyInstallments_text").remove();
			selectQtyInstallments(d.resposta);
		})
		.fail(function(f){});		
	}

//Função que gera o valor das quantidades de parcela que deve ter um carnê de acordo com o valor do pedido
	var selectQtyInstallments = function(resposta)
	{
		var i = 0;

		if ($(".selectQtyInstallments").length == 0) {
			var tagSelect = "<select class='selectQtyInstallments'></select>";

			$('#payment_module_name').closest('.col-lg-9').append(tagSelect);	
		}
		
		while (i < resposta.length) {
			var is = i + 1;

			if($("#qtty_installments"+i).length == 0) {
				var tagOption = "<option value='" + is + "' id='qtty_installments" + i + "' class='option'> Em " + is + " vezes de " + resposta[i]['installment_value'] + "</option>";
				$(".selectQtyInstallments").append(tagOption);
			}

			i++;
		}			
	}	

	$("#order_submit_btn").click(function()
	{
		document.cookie='qtyIstallments='+$(".selectQtyInstallments").val();
		$(tem_esconder).val();

	});	
});