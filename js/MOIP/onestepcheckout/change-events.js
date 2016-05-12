(function($) {

SendAddresShipping = function(){
			val_address = jQuery("#ship_to_same_address").val();
			if (val_address == 1) {
				jQuery("#shipping-new-address-form").show();
				jQuery("#shipping_show").show();
				jQuery("#ship_to_same_address").val(0);
			} else {
				jQuery('#shipping_show').hide();
				updateShippingType(jQuery('#billing:postcode').val());
				jQuery("#ship_to_same_address").val(1);
				
			}
}

SendShippingForBilling = function(){
				updateShippingType(jQuery('#billing:postcode').val());
				jQuery('#shipping_show').hide();
				jQuery('shipping\\::same_as_billing').checked = false;
				jQuery("#ship_to_same_address").val(1);
	
}

ObserverClickForm = function(){
		jQuery('#co-billing-form :input').blur(function() {
			jQuery("#billing\\:save_in_address_book").val(1);
			jQuery("#billing\\:save_in_address_book").prop( "checked", true );
		});

}

ChangeEvents = function(){
	ObserverClickForm();
	jQuery('#register_new_account').val(1);
	jQuery("#billing\\:day").attr('maxlength', '2');
	jQuery("#billing\\:day").attr('onkeyup', 'ProxCamp(this)');
	jQuery("#billing\\:month").attr('maxlength', '2');
	jQuery("#billing\\:month").attr('onkeyup', 'ProxCamp(this)');
	jQuery("#billing\\:year").attr('minlength', '4');
	jQuery("#billing\\:year").addClass('validate-length minimum-length-4 validate-custom');
	
	jQuery("#billing\\:year").attr('maxlength', '4');
	jQuery("#billing\\:year").attr('onkeyup', 'ProxCamp(this)');
	jQuery('[id="billing:postcode"]').addClass('required-entry');
	jQuery('[id="shipping:postcode"]').addClass('required-entry');
	jQuery('[id="billing:region_id"]').addClass('validate-select');
	jQuery('[id="shipping:region_id"]').addClass('validate-select');
	
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
			
		}
	});
	

	jQuery("#billing-address-select").change(function(){
		flag=1	;
			if(flag==1){	
					change_select=0;
					if(this.value==""){							
							updateBillingForm(this.value,flag);
					}
					else{
							updateBillingForm(this.value) ;
					}								
				}				
				else{
					updateBillingForm(this.value);
					change_select=1;
				}
	});

	jQuery("#shipping-address-select").change(function(){
		flag=1	;
			if(flag==1){	
					change_select=0;
					if(this.value==""){							
						
						updateShippingForm(this.value,flag);
					}
					else{
						updateShippingForm(this.value);
					}								
				}				
				else{
					updateShippingForm(this.value);
					change_select=1;
				}
	});

	jQuery('#onestep_form :input').blur(function() {
			if(jQuery(this).attr('id') != "billing:day" && jQuery(this).attr('id') != "billing:month"){
				Validation.validate(jQuery(this).attr('id'));
			}
	});

	jQuery('#onestep_form_account :input').blur(function() {
			if(jQuery(this).attr('id') != "billing:day" && jQuery(this).attr('id') != "billing:month"){
				Validation.validate(jQuery(this).attr('id'));
			}
	});
	
	jQuery('#allow_gift_messages').bind({
		click: function() {
			if (jQuery(this).is(':checked')) {
				jQuery('#allow-gift-message-container').show();
				} else {
					jQuery('#allow-gift-message-container').hide();
				}
		}
	});

	jQuery('.btn-checkout').bind("click",function(e){
		e.preventDefault();
		var form = new VarienForm('onestep_form', true);

		if(!form.validator.validate())	{
			var logic= false;
			if(logined()!=1){
				val=jQuery('#billing\\:email').val();
				emailvalidated=Validation.get('IsEmpty').test(val) || /^([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*@([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*\.(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]){2,})$/i.test(val);
				if(val!="" && emailvalidated){
					updateEmailmsg(val);
				}
			}

		} else 	{
			var logic= true;
			if(logined()!=1){
				var msgerror=1;
				val=jQuery('#billing\\:email').val();
				emailvalidated=Validation.get('IsEmpty').test(val) || /^([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*@([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*\.(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]){2,})$/i.test(val);
				if(val!="" && emailvalidated){
					msgerror=updateEmailmsg(val);
				}
				if(msgerror==0){
					return false;
				}

			}
		}
		if(logic){
			jQuery('#loading-mask').show();
			updateOrderMethod();
			return true;
		} else {

			jQuery(".erros_cadastro_valores").html("");
			var erro_dado = [];
			var j = 0;
			jQuery(".validation-failed").each(function() {
				j++;
				temp_erro = jQuery(this).attr("title");
				if(jQuery.inArray(temp_erro, erro_dado) != 1){
					erro_dado[j] = jQuery(this).attr("title");
					jQuery(".erros_cadastro_valores").append('<li>'+erro_dado[j]+'</li>');
				}


			});
			if(jQuery.inArray("Meio de Envio", erro_dado) == 1){
				jQuery('#checkout-shipping-method-loadding').append('<dt><div class="validation-advice" id="advice-required-entry_shipping" style="position:relative;">Você deverá selecionar uma forma de envio.</div></dt>');
			}
			jQuery('#ErrosFinalizacao').modal('toggle');
			jQuery('.validation-advice').delay(3000).fadeOut("slow");
			jQuery(".btn-checkout").removeAttr("disabled");
			jQuery(".btn-checkout").show("slow");
			return false;
		}
	});

}

valide_senha = function(val) {	
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

validaCPF = function(cpf,pType){
		var cpf_filtrado="",valor_1=" ",valor_2=" ",ch="";var valido=false;for(i=0;i<cpf.length;i++){ch=cpf.substring(i,i+1);if(ch>="0"&&ch<="9"){cpf_filtrado=cpf_filtrado.toString()+ch.toString()
		valor_1=valor_2;valor_2=ch;}
		if((valor_1!=" ")&&(!valido))valido=!(valor_1==valor_2);}
		if(!valido)cpf_filtrado="12345678910";if(cpf_filtrado.length<11){for(i=1;i<=(11-cpf_filtrado.length);i++){cpf_filtrado="0"+cpf_filtrado;}}
		if(pType<=1){if((cpf_filtrado.substring(9,11)==checkCPF(cpf_filtrado.substring(0,9)))&&(cpf_filtrado.substring(11,12)=="")){return true;}}
		if((pType==2)||(pType==0)){if(cpf_filtrado.length>=14){if(cpf_filtrado.substring(12,14)==checkCNPJ(cpf_filtrado.substring(0,12))){return true;}}}
	return false;
}

checkCNPJ =function(vCNPJ){
		var mControle="";var aTabCNPJ=new Array(5,4,3,2,9,8,7,6,5,4,3,2);for(i=1;i<=2;i++){mSoma=0;for(j=0;j<vCNPJ.length;j++)
		mSoma=mSoma+(vCNPJ.substring(j,j+1)*aTabCNPJ[j]);if(i==2)mSoma=mSoma+(2*mDigito);mDigito=(mSoma*10)%11;if(mDigito==10)mDigito=0;mControle1=mControle;mControle=mDigito;aTabCNPJ=new Array(6,5,4,3,2,9,8,7,6,5,4,3);}
	return((mControle1*10)+mControle);
}
checkCPF = function(vCPF){
		var mControle=""
		var mContIni=2,mContFim=10,mDigito=0;for(j=1;j<=2;j++){mSoma=0;for(i=mContIni;i<=mContFim;i++)
		mSoma=mSoma+(vCPF.substring((i-j-1),(i-j))*(mContFim+1+j-i));if(j==2)mSoma=mSoma+(2*mDigito);mDigito=(mSoma*10)%11;if(mDigito==10)mDigito=0;mControle1=mControle;mControle=mDigito;mContIni=3;mContFim=11;}
	return((mControle1*10)+mControle);
}

Setmask = function(){
	$('#billing\\:postcode').mask("00000-000", {placeholder: "_____-___"});
	$('#billing\\:fax').mask("(00)0000-0000Z", {placeholder: "(__)____-_____",translation: {'Z': {pattern: /[0-9]/, optional: true}}});
	$('#billing\\:telephone').mask("(00)0000-0000Z", {placeholder: "(__)____-_____",translation: {'Z': {pattern: /[0-9]/, optional: true}}});
	$('#credito_portador_cpf').mask("000.000.000-00", {placeholder: "___.___.___-__"});
	$('#billing\\:taxvat').mask("000.000.000-00", {placeholder: "___.___.___-__"});
}

	
})(jQuery);

