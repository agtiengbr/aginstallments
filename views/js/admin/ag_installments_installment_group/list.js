document.addEventListener('DOMContentLoaded', function(){
	var form = document.getElementById('form-aginstallments_installment_group');
	var btn_add = form.querySelector('#desc-aginstallments_installment_group-new');

	btn_add.parentNode.removeChild(btn_add);
});