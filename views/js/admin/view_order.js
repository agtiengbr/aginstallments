
$(function(){
	//Cria o botão para gerar o pdf dentro da página de pedido
	function createButtonPdf()
	{
		var tagBtn = '<a class="btn btn-default" href="' + linkPdf + '" target="_blank"><i class="icon-file"></i> Carnê</a>';
		$(".label-inactive").closest(".hidden-print").append(tagBtn);
	}

	createButtonPdf();
});
