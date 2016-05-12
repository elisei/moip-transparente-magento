jQuery(function() {
	

	

	jQuery('#billing\\:email').focusout(function() {
		jQuery('#advice-validate-email-billing\\:email').html("");
		jQuery('.email_invalido').hide();		
		val=jQuery('#billing\\:email').val();
		if(val!=""){
		var testEmail = /^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)+[A-Z]{2,4}$/i;
		if (testEmail.test(val)){
		jQuery('#advice-validate-email-billing\\:email').css({display:"none"});
		updateEmailmsg(val);
		}
		else{

		}
	}
	});
	

});

function valide_senha(val) {	
		var val_pass_count = jQuery('#billing\\:customer_password').val().length;
		var val_pass2_count =jQuery('#billing\\:confirm_password').val().length +1;
		if(val_pass_count == val_pass2_count){
			var val_pass=jQuery('#billing\\:customer_password').val();
			var val_pass2=jQuery('#billing\\:confirm_password').val();
			if(val_pass != val_pass2){
				jQuery("#senha_invalida").html('');
				jQuery(".senha_invalida").removeClass('validation-advice');
			}
		}
}

function TestaCPF(strCPF) {
	jQuery("#advice-validar_cpf").html('');
	jQuery(".advice-validar_cpf").removeClass('validation-advice');
    var Soma;
    var Resto;
    Soma = 0;
    if (strCPF == "00000000000" || strCPF == "11111111111" || strCPF == "22222222222" || strCPF == "33333333333" || strCPF == "44444444444" || strCPF == "55555555555" || strCPF == "66666666666"  || strCPF == "77777777777" ||  strCPF == "88888888888" ||  strCPF == "99999999999"){
			jQuery("#advice-validar_cpf").html('');
			jQuery(".advice-validar_cpf").removeClass('validation-advice');
			jQuery('#billing\\:taxvat').after('<div class="validation-advice advice-validar_cpf" id="advice-validar_cpf" style="display: block;">CPF inválido, o seu cpf será mantido em sigílo, porém é necessário para a emissão da Nota Fiscal.</div>');
	}
    for (i=1; i<=9; i++){
		Soma = Soma + parseInt(strCPF.substring(i-1, i)) * (11 - i); 
	}
    Resto = (Soma * 10) % 11;
    if ((Resto == 10) || (Resto == 11)){
		Resto = 0;
	}
    if (Resto != parseInt(strCPF.substring(9, 10)) ){
    	jQuery("#advice-validar_cpf").html('');
			jQuery(".advice-validar_cpf").removeClass('validation-advice');
			jQuery('#billing\\:taxvat').after('<div class="validation-advice advice-validar_cpf" id="advice-validar_cpf" style="display: block;">CPF inválido, o seu cpf será mantido em sigílo, porém é necessário para a emissão da Nota Fiscal.</div>');
    }
	Soma = 0;
    for (i = 1; i <= 10; i++){
       	Soma = Soma + parseInt(strCPF.substring(i-1, i)) * (12 - i);
    }
    Resto = (Soma * 10) % 11;
    if ((Resto == 10) || (Resto == 11)){
		Resto = 0;
	}
    if (Resto != parseInt(strCPF.substring(10, 11) ) ){
    	jQuery("#advice-validar_cpf").html('');
			jQuery(".advice-validar_cpf").removeClass('validation-advice');
			jQuery('#billing\\:taxvat').after('<div class="validation-advice advice-validar_cpf" id="advice-validar_cpf" style="display: block;position:absolute;">CPF inválido, o seu cpf será mantido em sigílo, porém é necessário para a emissão da Nota Fiscal.</div>');
    }
    return true;
}
