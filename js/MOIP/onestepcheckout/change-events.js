(function($) {
SendAddresShipping = function(val){

			jQuery("#NewAddress-type").modal("toggle");
			if (val == 1) {
				CleanFormBilling();
				jQuery("#NewAddress-billing").modal({backdrop: 'static', keyboard: false});
				jQuery("#ship_to_same_address").val(1);
				jQuery("input[name='ship_to_same_address']").val(1);
			} else {
				CleanFormShipping();
				jQuery("#NewAddress-shipping").modal({backdrop: 'static', keyboard: false});
				jQuery("#ship_to_same_address").val(0);
				jQuery("input[name='ship_to_same_address']").val(0);
			}
};
SendShippingForBilling = function(){
				jQuery('shipping\\::same_as_billing').checked = false;
				jQuery("#ship_to_same_address").val(1);
				jQuery("#shipping_show").hide();
				updateShippingType(jQuery('#billing:postcode').val());
	
};
ObserverClickForm = function(){
		jQuery('#co-billing-form :input').blur(function() {
			jQuery("#billing\\:save_in_address_book").prop( "checked", true );
		});
};
ValideteNewAddressBilling = function(){
	erro = 1;
	var validate_address = Validation.validate(document.getElementById("billing:firstname"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("billing:lastname"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("billing:telephone"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("billing:postcode"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("billing:street1"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("billing:street2"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("billing:street3"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("billing:region_id"));
	if(validate_address == false){
		erro = 0;
	}
	if(erro == 0){
		return;
	} else {
		jQuery("#NewAddress-billing").modal('toggle');
	}
};
ValideteNewAddressShipping = function(){
	erro = 1;
	var validate_address = Validation.validate(document.getElementById("shipping:firstname"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("shipping:lastname"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("shipping:telephone"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("shipping:postcode"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("shipping:street1"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("shipping:street2"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("shipping:street3"));
	if(validate_address == false){
		erro = 0;
	}
	var validate_address = Validation.validate(document.getElementById("shipping:region_id"));
	if(validate_address == false){
		erro = 0;
	}
	if(erro == 0){
		return;
	} else {
		jQuery("#NewAddress-shipping").modal('toggle');
	}
};
CleanFormShipping = function(){
	jQuery("#shipping\\:firstname").val("");
	jQuery("#shipping\\:lastname").val("");
	jQuery("#shipping\\:telephone").val("");
	jQuery("#shipping\\:fax").val("");
	jQuery("#shipping\\:postcode").val("");
	jQuery("#shipping\\:street2").val("");
};
CleanFormBilling = function(){
	jQuery("#billing\\:firstname").val("");
	jQuery("#billing\\:lastname").val("");
	jQuery("#billing\\:telephone").val("");
	jQuery("#billing\\:fax").val("");
	jQuery("#billing\\:postcode").val("");
	jQuery("#billing\\:street2").val("");
};


FixedButtonPlace = function(){
	var top_header = jQuery("#checkout-moip-header").outerHeight(true);
	var meio_pg_altura = jQuery("#meio-de-pagamento").outerHeight(true);
	var meio_pg_distancia = jQuery("#meio-de-pagamento").offset().top;
	var bottom_for_affix = meio_pg_altura + meio_pg_distancia;
	var review_itens_top = jQuery("#review").offset().top;
	var review_itens_altura = jQuery("#review").outerHeight(true);
	var altura_button = jQuery(".actions-fixed").outerHeight(true);
	var footer_bootom = jQuery("#footer-inbootom").offset().top;
	var stop = footer_bootom;
	var start = parseInt(review_itens_top) + parseInt(review_itens_altura);
	
	if(review_itens_altura < meio_pg_altura){
		jQuery(".actions-fixed").affix({offset: {top: start, bottom: 240} });	
	}
	
}
nextPasso = function(action){
	// jQuery(".address-buttons").addClass("alert-success");
	
	if(action == "payment"){
		jQuery('html,body').animate({ scrollTop: jQuery("#meio-de-pagamento").offset().top - 20},'slow');	
	} else {
		jQuery('html,body').animate({ scrollTop: jQuery("#meio-de-envio").offset().top - 20},'slow');
	}
	
}
NewAddressCancel = function (){
	jQuery("#NewAddress-cadastrar").modal('toggle');
	window.location.reload();
};
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
};
validaCPF = function(cpf,pType){
		var cpf_filtrado="",valor_1=" ",valor_2=" ",ch="";var valido=false;for(i=0;i<cpf.length;i++){ch=cpf.substring(i,i+1);if(ch>="0"&&ch<="9"){cpf_filtrado=cpf_filtrado.toString()+ch.toString()
		valor_1=valor_2;valor_2=ch;}
		if((valor_1!=" ")&&(!valido))valido=!(valor_1==valor_2);}
		if(!valido)cpf_filtrado="12345678910";if(cpf_filtrado.length<11){for(i=1;i<=(11-cpf_filtrado.length);i++){cpf_filtrado="0"+cpf_filtrado;}}
		if(pType<=1){if((cpf_filtrado.substring(9,11)==checkCPF(cpf_filtrado.substring(0,9)))&&(cpf_filtrado.substring(11,12)=="")){return true;}}
		if((pType==2)||(pType==0)){if(cpf_filtrado.length>=14){if(cpf_filtrado.substring(12,14)==checkCNPJ(cpf_filtrado.substring(0,12))){return true;}}}
	return false;
};
checkCPF = function(vCPF){
		var mControle=""
		var mContIni=2,mContFim=10,mDigito=0;for(j=1;j<=2;j++){mSoma=0;for(i=mContIni;i<=mContFim;i++)
		mSoma=mSoma+(vCPF.substring((i-j-1),(i-j))*(mContFim+1+j-i));if(j==2)mSoma=mSoma+(2*mDigito);mDigito=(mSoma*10)%11;if(mDigito==10)mDigito=0;mControle1=mControle;mControle=mDigito;mContIni=3;mContFim=11;}
	return((mControle1*10)+mControle);
};
checkCNPJ =function(vCNPJ){
		var mControle="";var aTabCNPJ=new Array(5,4,3,2,9,8,7,6,5,4,3,2);for(i=1;i<=2;i++){mSoma=0;for(j=0;j<vCNPJ.length;j++)
		mSoma=mSoma+(vCNPJ.substring(j,j+1)*aTabCNPJ[j]);if(i==2)mSoma=mSoma+(2*mDigito);mDigito=(mSoma*10)%11;if(mDigito==10)mDigito=0;mControle1=mControle;mControle=mDigito;aTabCNPJ=new Array(6,5,4,3,2,9,8,7,6,5,4,3);}
	return((mControle1*10)+mControle);
};
Setmask = function(){
	$('#moip_cc_number').mask("0000 0000 0000 0000 0000");
	$('#moip_cc_cid').mask("000Z", {translation: {'Z': {pattern: /[0-9]/, optional: true}}});
	$('#billing\\:postcode').mask("00000-000", {placeholder: "_____-___"});
	$('#shipping\\:postcode').mask("00000-000", {placeholder: "_____-___"});

	$('#billing\\:fax').mask("(00)0000-0000Z", {placeholder: "(__)____-_____",translation: {'Z': {pattern: /[0-9]/, optional: true}}});
	$('#shipping\\:fax').mask("(00)0000-0000Z", {placeholder: "(__)____-_____",translation: {'Z': {pattern: /[0-9]/, optional: true}}});
	$('#billing\\:telephone').mask("(00)0000-0000Z", {placeholder: "(__)____-_____",translation: {'Z': {pattern: /[0-9]/, optional: true}}});
	$('#shipping\\:telephone').mask("(00)0000-0000Z", {placeholder: "(__)____-_____",translation: {'Z': {pattern: /[0-9]/, optional: true}}});
	$('#credito_portador_cpf').mask("000.000.000-00", {placeholder: "___.___.___-__"});
	$('#billing\\:taxvat').mask("000.000.000-00", {placeholder: "___.___.___-__"});
};
payment_form_ative = function(){
	jQuery("input[name='payment[method]']").change(function(){
		code_method = jQuery(this).val();
		select_method = "#payment_form_"+jQuery(this).val();
		payment.switchMethod(code_method);
		jQuery(select_method).show();
	});
};
removeProductId = function(product_id){
	if(confirm("Tem certeza que deseja remover o produto?")){
		removeProduct(product_id);
	}
};
ProxCamp = function(fields) {
	fields.value=soNumeros(fields.value)
	if (fields.value.length == fields.maxLength) {
			for (var i = 0; i < fields.form.length; i++) {
				if (fields.form[i] == fields && fields.form[(i + 1)] && fields.form[(i + 1)].type != "hidden") {
					fields.form[(i + 1)].focus();
				break;
			}
		}
	}
};
soNumeros = function(v){
    return v.replace(/\D/g,"")
};
visibilyloading  = function(process){
	if(process != 'end'){
		jQuery("#modal-loading-process").modal({backdrop: 'static', keyboard: false});	
	} else {
		jQuery("#modal-loading-process").modal("toggle");
	}
};

getFormHash = function(hash){
	getHash = hash.split("[");

	if(getHash.length != 1){
		return getHash[0];
	} else {
		return hash;
	}
}
getFormInvalidData = function(){
	jQuery(".erros_cadastro_valores").html("");
	value_hash_session =  new Array()
	value_field_title =  new Array();
	errors = 1;
	b = 1;
	s = 1;
	p = 1;
	sm = 1;
	o = 1;
	erross_to_shipping_method = new Array();
	erross_to_general = new Array();
	html = new Array();
	
	analyzed = ('billing','shipping','payment','shipping_method','general');

	jQuery(".validation-failed").each(function() {
		name = jQuery(this).attr("name");
		title_field = jQuery(this).attr("title");
		if(name != 'undefined'){
				hash_session = getFormHash(name);
				
	         	if(hash_session){
	         		value_hash_session.push(hash_session);
	         		value_field_title.push(title_field);

				}
		} else {
			
		}
	});

			
	for (var i = 0; i <= value_hash_session.length; i++) {

		if(value_hash_session[i] === 'billing'){
			var address_error = jQuery("input[name='billing_address_id']:checked").val();
			var url_edit = editaddressurl + 'EditAddress/id/' + address_error;
			window.location.href = url_edit;
			jQuery(".erros_cadastro_valores").append("<h4>Em endereço de cobrança</h4>");
			jQuery(".erros_cadastro_valores").append('<li>' + value_field_title[i] + '</li>');
			
		} 
		if(value_hash_session[i] === 'shipping'){
			window.location.href = url_edit;
			jQuery(".erros_cadastro_valores").append("<h4>Em endereço de entrega</h4>");
			jQuery(".erros_cadastro_valores").append('<li>' + value_field_title[i] + '</li>');
		}
		if(value_hash_session[i] === 'payment'){
			
			var count_erros_payment = p++;
			if(count_erros_payment === 1){
				jQuery(".erros_cadastro_valores").append("<h4>Método de Pagamento</h4>");
			}
			jQuery(".erros_cadastro_valores").append('<li>'+ value_field_title[i] + '</li>');
			errors = 1;

		}
		if(value_hash_session[i] === 'shipping_method'){
			
			var count_erros_sm = sm++;
			if(count_erros_sm === 1){
				jQuery(".erros_cadastro_valores").append("<h4>Método de Envio</h4>");
				jQuery(".erros_cadastro_valores").append('<li>'+ value_field_title[i] + '</li>');
			}
			errors = 1;
			
		}
		if(value_hash_session[i] === 'general'){
			jQuery(".erros_cadastro_valores").append("<h4>Campos</h4>");
			jQuery(".erros_cadastro_valores").append('<li>'+ value_field_title[i] + '</li>');
		}
		errors = 1;
	}
	return errors;
};

UpdataQtyReview = function(){
	jQuery('.btn-number').click(function(e){
	    e.preventDefault();
	    var fieldName = jQuery(this).attr('data-field');
	   
	    var type      = jQuery(this).attr('data-type');
	   
	    var input = jQuery("input[name='"+fieldName+"']");
	   
	    var currentVal = parseInt(jQuery("input[name='"+fieldName+"']").val());
	  
	    var oldValue = parseInt(jQuery("input[name='"+fieldName+"']").attr('data-oldValue'));
	  
	    if (!isNaN(currentVal)) {

	        if(type == 'minus') {
	            
	            if(currentVal > input.attr('min')) {
	            	if(parseInt(oldValue-1) == parseInt(currentVal - 1)){
	            		input.val(currentVal - 1).change();
	            	}
	                
	            } 
	            if(parseInt(input.val()) == input.attr('min')) {
	                jQuery(this).attr('disabled', true);
	            }

	        } else if(type == 'plus') {

	            if(currentVal < input.attr('max')) {
	            	if(parseInt(oldValue+1)  == parseInt(currentVal + 1)){
	                	input.val(currentVal + 1).change();
	                }
	            }
	            if(parseInt(input.val()) == input.attr('max')) {
	                jQuery(this).attr('disabled', true);
	            }

	        }
	    } else {
	        input.val(0);
	    }
	});
	jQuery('.input-number').focusin(function(){
	   jQuery(this).data('oldValue', jQuery(this).val());
	});
	jQuery('.input-number').change(function() {
	    
	    minValue =  parseInt(jQuery(this).attr('min'));
	    maxValue =  parseInt(jQuery(this).attr('max'));
	    valueCurrent = parseInt(jQuery(this).val());
	    
	    name = jQuery(this).attr('name');
	    if(valueCurrent >= minValue) {
	        jQuery(".btn-number[data-type='minus'][data-field='"+name+"']").removeAttr('disabled')
	    } else {
	        jQuery(this).val(jQuery(this).data('oldValue'));
	    }
	    if(valueCurrent <= maxValue) {
	        jQuery(".btn-number[data-type='plus'][data-field='"+name+"']").removeAttr('disabled')
	    } else {
	        
	        jQuery(this).val(jQuery(this).data('oldValue'));
	    }
	    setTimeout(updateQty(), 2000);
	    
	});
	jQuery(".input-number").keydown(function (e) {
	        // Allow: backspace, delete, tab, escape, enter and .
	        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
	             // Allow: Ctrl+A
	            (e.keyCode == 65 && e.ctrlKey === true) || 
	             // Allow: home, end, left, right
	            (e.keyCode >= 35 && e.keyCode <= 39)) {
	                 // let it happen, don't do anything
	                 return;
	        }
	        // Ensure that it is a number and stop the keypress
	        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
	            e.preventDefault();
	        }
	});

}
setTypePersona = function(typepersona, method){
	if(method == "new-fileds"){
		if(typepersona == 1){
			jQuery('.dados-pj').hide();
			jQuery('.dados-pj input:not([type=checkbox])').removeClass('required-entry validation-failed');
			jQuery('#cnpj').removeClass('validar_cnpj');
		} else {
			jQuery('.dados-pj').show();
			jQuery('.dados-pj input:not([type=checkbox])').addClass('required-entry').removeClass('validation-passed validation-failed');
			jQuery('#cnpj').addClass('validar_cnpj');
		}
	}
	
};
ChangeEvents = function(){
	UpdataQtyReview();
	typeselect = jQuery("input[name='billing[tipopessoa]']:checked").val();
	setTypePersona(typeselect, 'new-fileds');
	jQuery("input[name='billing[tipopessoa]']").change(function(){
			console.log(typeselect);
			typeselect = jQuery("input[name='billing[tipopessoa]']:checked").val();
			setTypePersona(typeselect, 'new-fileds');
			
	});
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
	jQuery("#shipping-address-select").change(function(){
		updateShippingForm(this.value);
	});
	jQuery('#onestep_form :input').blur(function() {
		if (jQuery(this).attr('id') != "billing:day" && jQuery(this).attr('id') != "billing:month") {
			Validation.validate(jQuery(this).attr('id'));
		};
	});
	jQuery('#onestep_form_account :input').blur(function() {
		if (jQuery(this).attr('id') != "billing:day" && jQuery(this).attr('id') != "billing:month") {
			Validation.validate(jQuery(this).attr('id'));
		};
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
	jQuery('.moip-place-order').bind("click",function(e){
		e.preventDefault();
		visibilyloading();
		jQuery(".moip-place-order").hide();
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
			updateOrderMethod();
			return true;
		} else {
			visibilyloading('end');
			view_erro_modal = getFormInvalidData();
			if(view_erro_modal){
				jQuery('#ErrosFinalizacao').modal();
				jQuery('.validation-advice').delay(5000).fadeOut("slow");
			}
			jQuery(".moip-place-order").show("slow");
			return false;
		}
	});
};
has_default_billing = function()
{
	var addrres_select = jQuery("input[name='billing_address_id']").val();
	if(address_status){
		return 1;
	} else{
		jQuery("#save_address_primary_modal").modal({backdrop: 'static', keyboard: false});
		return 0;
	}
};
buscarEndereco = function(whatform) {
	jQuery(".hide-zip-code").addClass("show-zip-code");
	if (whatform == "billing" || whatform == "billing-registrer") {
			postcode 	= jQuery('#billing\\:postcode').val();
			street_1 	= jQuery('#billing\\:street1');
			street_2 	= jQuery('#billing\\:street2');
			street_4 	= jQuery('#billing\\:street4');
			city 		= jQuery('#billing\\:city');
			region 		= jQuery('#billing\\:region_id');
	}
	if (whatform == "shipping") {
			postcode 	= jQuery('#shipping\\:postcode').val();
			street_1 	= jQuery('#shipping\\:street1');
			street_2 	= jQuery('#shipping\\:street2');
			street_4 	= jQuery('#shipping\\:street4');
			city 		= jQuery('#shipping\\:city');
			region 		= jQuery('#shipping\\:region_id');
	}
	if (whatform == "register") {
			postcode 	= jQuery('#postcode').val();
			street_1 	= jQuery('#street_1');
			street_2 	= jQuery('#street_1');
			street_4 	= jQuery('#street_4');
			city 		= jQuery('#city');
			region 		= jQuery('#region_id');
	}
	jQuery.ajax({
		type:'GET',
		url:  buscacepurl+'meio/cep/cep/' + postcode.replace(/[^\d\.]/g, ''),
		beforeSend: function(data){
			street_1.attr('placeholder', 'Buscando Endereço');
			street_4.attr('placeholder', 'Buscando Endereço');
			city.attr('placeholder', 'Buscando Endereço');
		},
		success: function(data){
			if(data){
				var addressData = jQuery.parseJSON(data);
				if(addressData.logradouro != "undefined"){
					street_1.attr('placeholder', 'Rua');
					street_2.attr('placeholder', 'N.º');
					street_4.attr('placeholder', 'Bairro');
					city.attr('placeholder', 'Cidade');
					street_1.val(addressData.logradouro);
					street_4.val(addressData.bairro);
					city.val(addressData.cidade);
					region.val(addressData.ufid);
					street_1.focus();
					street_4.focus();
					city.focus();
					region.focus();
				}else {
					street_1.val('');
					street_4.val('');
					city.val('');
					street_1.attr('placeholder', 'Rua');
					street_2.attr('placeholder', 'N.º');
					street_4.attr('placeholder', 'Bairro');
					city.attr('placeholder', 'Cidade');
				}
				if (postcode != "" && postcode != "." && whatform !=  "billing-registrer")
				{
					updateShippingType(postcode);
				};
				
			} else {
				street_1.attr('placeholder', 'Rua');
				street_2.attr('placeholder', 'N.º');
				street_4.attr('placeholder', 'Bairro');
				city.attr('placeholder', 'Cidade');
				if (postcode != "" && postcode != "."  && whatform !=  "billing-registrer")
				{
					updateShippingType(postcode);
				};
			}
			street_2.focus();
		},
		error: function(){
			jQuery(street_1).val("");
			jQuery(street_4).val("");
			jQuery(city).val("");
			jQuery(region).val("");
			if (postcode != "" && postcode != "."  && whatform !=  "billing-registrer")
			{
				updateShippingType(postcode);
			};
		},
	});
};
updateShippingMethod = function() {
	if(jQuery('billing:postcode') != "" && jQuery('billing:postcode') != "." ){
		jQuery.ajax({
			type: "POST",
			url: updateshippingmethodurl,
			data: jQuery("#onestep_form").serialize(),
			beforeSend: function(){
				jQuery('#payment-progress').show();
				jQuery('#checkout-payment-method-load').hide();
			},
			success: function(msg) {
				jQuery('#checkout-review-table').replaceWith(msg);
				updatePaymentAssociated();
				nextPasso('payment');
				UpdataQtyReview();
			},
			fail:function() {
				updateShippingMethod();

			}
		});
	}
};
updateOrderMethod = function() {
	jQuery.ajax({
		type: "POST",
		url: updateordermethodurl,
		data: jQuery("#onestep_form").serialize(),			
		success: function(msg) {
			var result = jQuery.parseJSON(msg);
			if(result.erros == 1){
				jQuery(".erros_cadastro_valores").append('<li> - '+result.msg_error+'</li>');
				visibilyloading('end');
				jQuery('#ErrosFinalizacao').modal('toggle');
			} else{
				window.location.href = result.msg_success;
			}
		},
		fail:function() {
			location.reload();
		},
	});
};
updateShippingType = function(){
	if(!is_virtual){
		jQuery('#shipping-method').hide();
		jQuery.ajax({
			type: "POST",
			url: updateshippingtypeurl,
			data:jQuery("#onestep_form").serialize(),
			beforeSend: function(){
				jQuery('#shipping-progress').show();
			},
			success: function(msg){
					  
					jQuery('#shipping-method').replaceWith(msg);
					shippingChange();
					jQuery('#shipping-progress').hide();
					jQuery('#shipping-method').show();
			},
			error: function(){
			 	jQuery('#shipping-method').replaceWith("Não foi possível cotar o seu frete, por favor recarrege a página ou entre em contato com nossa loja.");
			},
		});
	}
};
updateClear = function() {
	jQuery.ajax({
		type: "POST",
		url: updateshippingtypeurl,
		data:jQuery("#onestep_form").serialize(),
		success: function(msg){
				jQuery('#checkout-shipping-method-load').replaceWith(msg);
		},
		error: function(){
		 	jQuery('#checkout-shipping-method-load').replaceWith("Não foi possível cotar o seu frete, por favor recarrege a página ou entre em contato com nossa loja.");
		},
	});
};
updatePaymentAssociated = function() {
	jQuery.ajax({
		type: "POST",
		url: updatepaymenttypeurl,
		data: jQuery("#onestep_form").serialize(),
		success: function(msg) {
			jQuery('#payment-progress').hide();
			jQuery('#checkout-payment-method-load').show();
			jQuery('#checkout-payment-method-load').replaceWith(msg);
			UpdataQtyReview();
		},
	});
};
updatePaymentMethod = function() {
	jQuery.ajax({
		url: updatepaymentmethodurl,
		type: "POST",
		data: jQuery("#onestep_form").serialize(),
		success: function(msg) {
			jQuery('#checkout-review-table').replaceWith(msg);
			UpdataQtyReview();
		},
	});
};
updateCoupon = function() {
		jQuery(".cupom_tem").hide();
		visibilyloading();
		jQuery.ajax({
			url: updatecouponurl,
			type: "POST",
			data: jQuery("#onestep_form").serialize(),
		})
		.done(function(msg) {
			jQuery.ajax({
				url: updatepaymenttypeurl,
				type: "POST",
				data: jQuery("#onestep_form").serialize(),
			});
			location.reload();

		})
		.fail(function() {
			location.reload();
		})
		.always(function() {
			location.reload();
		});
};
removeProduct = function(id) {
	hasgift = (typeof(jQuery('#allow-gift-message-container')) != 'undefined') ? 1 : 0;
	jQuery.ajax({
		type: "POST",
		url: removeproducturl,
		data: "id=" + id + '&hasgiftbox=' + hasgift,
		beforeSend: function(){
				visibilyloading();
		},
		success: function(msg) {
			jQuery.ajax({
				type: "POST",
				url: updatepaymenttypeurl,
				data: jQuery("#onestep_form").serialize(),
				success: function(msg) {
					 location.reload();
				}
			});
			
		}
	});
};
logined = function()
{
	return customer_status;
};
hasaddress = function(){
	if(address_status){
		return 1;	
	} else {
		return 0;
	}
};
checkedLoginStep = function(val){
	jQuery.ajax({
			// async:false,
			type: "POST",
			url: updateemailmsg,
			data:"email="+val,
			success: function(msg){
					if(msg==0){
						jQuery('.pre-step').hide();
						jQuery('#login-checked').show();
						jQuery('#checked-login-email').val(val);
					}else{
						jQuery('.pre-step').hide();
						jQuery('#billing\\:email').val(val);
						jQuery('#create-checked').show();
					}
			}
		});
};
updateEmailmsg = function(val){
	jQuery.ajax({
			// async:false,
			type: "POST",
			url: updateemailmsg,
			data:"email="+val,
			success: function(msg){
				val=jQuery('#billing\\:email').val();
				emailvalidated=Validation.get('IsEmpty').test(val) || /^([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*@([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*\.(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]){2,})$/i.test(val);
				
				var error="<div id=\"message-box\"><div  class=\"validation-advice\" style=\"position:relative;\">Email já cadastrado, por favor <a href=\"#\" data-toggle=\"modal\" data-target=\"#loginModel\"  id=\"loginbox\">Clique aqui e faça o login</a></div></div>";
				if(msg==0){
					jQuery('#billing\\:email').after(error);
					jQuery('#message-box').replaceWith(error);
					return 0;

				}else{
					if(logined()!=1){
						jQuery('#message-box').replaceWith('');
					}
					jQuery('#billing\\:email').removeClass('validation-failed');
					return 1;
				}
			}
		});
};
updateQty = function(){
			visibilyloading();
            jQuery.ajax({
                url: updateqtyurl,
                data:  jQuery("#onestep_form").serialize(),
                    success: function(replaceWith){
                        	location.reload();
                    }
            });
};
shippingChange = function(){
	jQuery("input[name='shipping_method']").on('change', function () {
		updateShippingMethod();
	});
};
PayamentChange = function(){
	var payment = new Payment('co-payment-form', set_payment_form);
       	payment.init();
        payment_form_ative();
	jQuery("input[name='payment[method]']").on('change', function () {
		updatePaymentMethod();
	});
	jQuery("#credito_parcelamento").on('change', function () {
            		updatePaymentMethod();
    });
    jQuery('[data-toggle="popover"]').popover();
	
};
updateShippingForm = function(id_address){
	if(id_address){
		jQuery.ajax({
                type: "POST",
                url:  updateshippingformurl,
                data: jQuery("#onestep_form").serialize(),
                success: function(msg) {
                    jQuery('#moip_onstepcheckout_shipping_form').replaceWith(msg);
                    updateShippingType();

                }
            });

	} else {
		CleanFormShipping();
	}
};
newaddress = function(){
        jQuery("#newaddres").removeClass('hidden');
        jQuery('html,body').animate({ scrollTop: jQuery("#newaddres").offset().top + 150},'slow');  
};
updateBillingForm = function(id) {

	if(id != 0){
		jQuery.ajax({
	        type: "POST",
	        url: updatebillingformurl,
	        data: jQuery("#onestep_form").serialize(),
	        success: function(msg) {
	            jQuery('#moip_onstepcheckout_billing_form').replaceWith(msg);
	            updateShippingType();
	        }
	    });
	} else {
		jQuery("#NewAddress-type").modal({backdrop: 'static', keyboard: false});
	}
};
gobackemail = function(){
	 var target = jQuery("#identifique");  
	 jQuery(target).css('left','-'+jQuery(window).width()+'px');   
	 var right = jQuery(target).offset().right;
	 jQuery(target).css({left:right}).animate({"left":"0px"}, "10");
	 jQuery("#create-loggin").removeClass('active', 'in');
	 jQuery("#identifique").addClass('active in');
};
})(jQuery);