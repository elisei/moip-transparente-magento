(function($) {

validateOnBlur = function(form){
	jQuery('#'+form+' :input:not([type="checkbox"]):not([type="submit"]):not([type="button"])').blur(function() {
		if(jQuery(this).attr('id')) {
			if (jQuery(this).attr('id') != "day" && jQuery(this).attr('id') != "month") {
	        	Validation.validate(jQuery(this).attr('id'));
	      	};		
		}
  	});
}

SetUniquePassword = function() {
	jQuery('#confirmation').val(jQuery('#password').val());
}

gobackemail = function(){
	location.href = url_clear_identify;
}

IdentifyUser = function(){
	var identify = new VarienForm('onestep_form_identify', true);
    if (identify.validator.validate()) 
	{
		jQuery.ajax({
			url: url_indetify,
			evalScripts:true,
			type: "POST",
			data: jQuery("#onestep_form_identify").serialize(),
			success: function(result) {
				if(result.is_user){

					jQuery('#isCustomer').html(result.html);
					var target = jQuery("#create-loggin");  
					jQuery(target).css('right','-'+jQuery(window).width()+'px');   
					var right = jQuery(target).offset().right;
					jQuery(target).css({right:right}).animate({"right":"0px"}, "10");
					jQuery("#create-loggin").addClass('active in');
					jQuery('#identifique').removeClass('active', 'in');
					jQuery('#login-email').val(jQuery('#checked-login').val());
					jQuery('#login-password').password();
					
				} else {
					jQuery('#not-isCustomer').html(result.html);
					var target = jQuery("#create-account");  
					jQuery(target).css('right','-'+jQuery(window).width()+'px');   
					var right = jQuery(target).offset().right;
					jQuery(target).css({right:right}).animate({"right":"0px"}, "10");
					jQuery("#create-account").addClass('active in');
					jQuery('#identifique').removeClass('active', 'in');
					jQuery('#password').password();
				}
			},
		});
	}
}

Authenticate = function(){
	var identify = new VarienForm('form-loggin', true);
    if (identify.validator.validate()) 
	{
		jQuery.ajax({
			url: url_authenticate,
			type: "POST",
			data: jQuery("#form-loggin").serialize(),
			success: function(result) {
				if(result.success){
					location.href = encodeURI(result.redirect);
				} else {
					jQuery('.error-login').html(result.error);
				}
			},
		});
	}
}

getErroDescription = function(){
	error = null;
	jQuery(".erros_cadastro_valores").html('');
	jQuery(".validation-failed").each(function() {
		name = jQuery(this).attr("name");
		temp = jQuery(this).attr("title");
		if(name != 'undefined'){
			if(error != temp){
				error = temp;
				jQuery(".erros_cadastro_valores").append('<li>'+ error + '</li>');
			}
		} 
	});
	return this;
}

visibilyloading  = function(process){
	if(process != 'end'){
		jQuery("#modal-loading-process").modal({backdrop: 'static', keyboard: false});	
	} else {
		jQuery("#modal-loading-process").modal("toggle");
	}
}

savePaymentMethod = function() {
	jQuery.ajax({
		url: url_save_payment_metthod,
		evalScripts:true,
		type: "POST",
		data: jQuery("#onestep_form").serialize(),
		success: function(result) {
			if(result.success){
				jQuery('#totals').html(result.html);	
			} else {
				alert(result.error);
			}
			
			
		},
	});
	return this;
}

saveShippingMethod = function(){
	jQuery.ajax({
		type: "POST",
		url: url_save_shipping_method,
		async: true,
		evalScripts:true,
		data: jQuery("#onestep_form").serialize(),
		success: function(result){
			if(result.success){
				jQuery("#payment-method-available").html(result.html);
				jQuery('html, body').animate({
					scrollTop: jQuery("#meio-de-pagamento").offset().top
				}, 2000);
			}
			if(result.totals){
				jQuery('#totals').html(result.totals);
			}
		}
	})
	return this;
}

saveShipping = function(){
	jQuery("#shipping-progress").removeClass('hidden-it');
	jQuery.ajax({
		type: "POST",
		url: url_save_shipping,
		data: jQuery("#onestep_form").serialize(),
		evalScripts:true,
		success: function(result){
			if(result.success){
				jQuery("#shipping-progress").addClass('hidden-it');
				jQuery("#shipping-method-available").html(result.html);
				jQuery('html, body').animate({
					scrollTop: jQuery("#meio-de-envio").offset().top
				}, 2000);
			}
		}
	})
	return this;
}

viewAddressBilling = function(value){
	if(value == false){
		jQuery(".address-billing-view").removeClass("no-display");
	} else {
		jQuery(".address-billing-view").addClass("no-display");
	}
	return this;
}

SaveAddress = function(id, context){
    var save = new VarienForm('form-new-address', true);
    if (save.validator.validate()) 
	{	
		if(id != 0) {
			//edit address existente update...
			if(context == "billing" || context == 'shipping'){
				visibilyloading();
				jQuery.ajax({
					type: "POST",
					url: form_post_address+'id/'+id,
					data: jQuery("#form-new-address").serialize(),
					evalScripts:true,
					dataType: "json",
					success: function(result) {
						if(result.success)
						{    
							if(result.update == "billing"){
								jQuery('#endereco-de-cobranca').html(result.html);
							} else{
								jQuery('#endereco-de-envio').html(result.html);
								saveShipping();
							}
							visibilyloading('end');
							jQuery("#new-address").modal('hide');
							
						} else {
							visibilyloading('end');
							window.location.reload();
						}
					}
				});
			} else if(context == "edit") {
				visibilyloading();
				jQuery.ajax({
					type: "POST",
					url: form_post_address+'id/'+id,
					data: jQuery("#form-new-address").serialize(),
					evalScripts:true,
					dataType: "json",
					success: function(result) {
						visibilyloading('end');
						if(result.success)
						{    
							if(!result.update){
								window.location.href = encodeURI(result.redirect);
							} 
							
							
						} else {
							visibilyloading('end');
							window.location.reload();
						}
					}
				});
			
			}

		} else {
			if(context == "billing" || context == 'shipping'){			
				visibilyloading();
				jQuery.ajax({
					type: "POST",
					url: form_post_address,
					data: jQuery("#form-new-address").serialize(),
					evalScripts:true,
					dataType: "json",
					success: function(result) {
						if(result.success)
						{    
							if(result.update == "billing"){
								jQuery('#endereco-de-cobranca').html(result.html);
							} else{
								jQuery('#endereco-de-envio').html(result.html);
								saveShipping();
							}
							visibilyloading('end');
							jQuery("#new-address").modal('hide');
							
						} else {
							
							visibilyloading('end');
							window.location.reload();
						}
					}
				});
			} else if(context == "edit"){
				//edição de endereço
				alert("olarrrrr");
				jQuery("#form-new-address").submit();
			} else {
				//criação da conta
				jQuery("#form-new-address").submit();
			}
		}
		
	}
	return this;
}

EditAddress = function(isEdit, context) {
	if(isEdit != 0){
		if(context == "shipping"){
			jQuery.ajax({
		       	type: "POST",
		        url: url_save_shipping,
		        data: jQuery("#onestep_form").serialize(),
		        evalScripts:true,
		        dataType: "json",
		        success: function(result) {
		        	if(result.success)
					{    
						if(result.update == "billing"){
							jQuery('#payment-method-available').html(result.html);
						} else{
							jQuery('#shipping-method-available').html(result.html);
						}
						if(result.totals){
							
							jQuery('#totals').html(result.totals);
						}
					} else {
						alert(result.error);
						window.location.reload();
			        }
		        }
		    });
		} else if (context == "billing"){
			jQuery.ajax({
		       	type: "POST",
		        url: url_save_billing,
		        data: jQuery("#onestep_form").serialize(),
		        evalScripts:true,
		        dataType: "json",
		        success: function(result) {
		        	if(result.success)
					{    
						if(result.update == "billing"){
							jQuery('#payment-method-available').html(result.html);
						} else{
							jQuery('#shipping-method-available').html(result.html)
						}
						if(result.totals){
							jQuery('#totals').html(result.totals);
						}
					} else {
						alert(result.error);
						window.location.reload();
			        }
		        }
		    });
		} else {
			
			jQuery.ajax({
		       	type: "POST",
		        url: url_new_address,
		        data: jQuery("#onestep_form").serialize(),
		        evalScripts:true,
		        dataType: "json",
		        success: function(result) {
		        	if(result.success)
					{    
						alert(resul);
					} else {
						alert(resul);
			        }
		        }
		    });
		}
		
	}  else {
		
		jQuery.ajax({
	        type: 'POST',
	        url: url_new_address,
	        data: {id: isEdit, typeform: context},
	        evalScripts:true,
        	dataType: 'json',
	        success: function(result) {
	           	jQuery("#new-address").modal({backdrop: 'static', keyboard: false});
	           	jQuery("#form-edit-address").html(result.html);
	        }
	    });
	}
}

checkEmailExists = function(v){
	jQuery.ajax({
        type: "POST",
        data: "email="+v+"&form_key="+jQuery("input[name='form_key']").val(),
        url: url_indetify,
        success: function (result) {
            if(result.is_user){
            	customerExists = false;
            } else {
            	customerExists = true;
            }
            return customerExists;
        },
        complete: function(result) {
        	if(result.is_user){
            	customerExists = false;
            } else {
            	customerExists = true;
            }
             return customerExists;
        }
    });
   
}

setTypePersona = function(type){
	var type = jQuery(type).attr('data-typepersona');
	if(type == "Jurídica"){
		jQuery('.dados-pj').show();
		jQuery('#cnpj').addClass('validate-cnpj');
		jQuery('.dados-pj input:not([type=radio])').addClass('required-entry').removeClass('validation-passed validation-failed');
	} else {
		jQuery('.dados-pj').hide();
		jQuery('#cnpj').removeClass('validate-cnpj');
		jQuery('.dados-pj input:not([type=radio])').removeClass('required-entry validation-passed validation-failed');
	}
}

validateTypeDocument = function(cpf,pType){
		var cpf_filtrado="",valor_1=" ",valor_2=" ",ch="";var valido=false;for(i=0;i<cpf.length;i++){ch=cpf.substring(i,i+1);if(ch>="0"&&ch<="9"){cpf_filtrado=cpf_filtrado.toString()+ch.toString()
		valor_1=valor_2;valor_2=ch;}
		if((valor_1!=" ")&&(!valido))valido=!(valor_1==valor_2);}
		if(!valido)cpf_filtrado="12345678910";if(cpf_filtrado.length<11){for(i=1;i<=(11-cpf_filtrado.length);i++){cpf_filtrado="0"+cpf_filtrado;}}
		if(pType==1){if((cpf_filtrado.substring(9,11)==checkCPF(cpf_filtrado.substring(0,9)))&&(cpf_filtrado.substring(11,12)=="")){return true;}}
		if(pType==2){if(cpf_filtrado.length>=14){if(cpf_filtrado.substring(12,14)==checkCNPJ(cpf_filtrado.substring(0,12))){return true;}}}
	return false;
}

checkCPF = function(vCPF){
		var mControle=""
		var mContIni=2,mContFim=10,mDigito=0;for(j=1;j<=2;j++){mSoma=0;for(i=mContIni;i<=mContFim;i++)
		mSoma=mSoma+(vCPF.substring((i-j-1),(i-j))*(mContFim+1+j-i));if(j==2)mSoma=mSoma+(2*mDigito);mDigito=(mSoma*10)%11;if(mDigito==10)mDigito=0;mControle1=mControle;mControle=mDigito;mContIni=3;mContFim=11;}
	return((mControle1*10)+mControle);
}

checkCNPJ =function(vCNPJ){
		var mControle="";var aTabCNPJ=new Array(5,4,3,2,9,8,7,6,5,4,3,2);for(i=1;i<=2;i++){mSoma=0;for(j=0;j<vCNPJ.length;j++)
		mSoma=mSoma+(vCNPJ.substring(j,j+1)*aTabCNPJ[j]);if(i==2)mSoma=mSoma+(2*mDigito);mDigito=(mSoma*10)%11;if(mDigito==10)mDigito=0;mControle1=mControle;mControle=mDigito;aTabCNPJ=new Array(6,5,4,3,2,9,8,7,6,5,4,3);}
	return((mControle1*10)+mControle);
}

Setmask = function(){
	jQuery('#moip_cc_number').mask("0000 0000 0000 0000 0000");
	jQuery('#moip_cc_cid').mask("000Z", {translation: {'Z': {pattern: /[0-9]/, optional: true}}});
	jQuery('#postcode').mask("00000-000", {placeholder: "_____-___"});
	jQuery('#fax').mask("(00)00000-0000", {placeholder: "(__)____-_____",translation: {'Z': {pattern: /[0-9]/, optional: true}}});
	jQuery('#telephone').mask("(00)00000-0000", {placeholder: "(__)____-_____",translation: {'Z': {pattern: /[0-9]/, optional: true}}});
	jQuery('#credito_portador_cpf').mask("000.000.000-00", {placeholder: "___.___.___-__"});
	jQuery('#taxvat').mask("000.000.000-00", {placeholder: "___.___.___-__"});
	jQuery('#cnpj').mask("00.000.000/0000-00", {placeholder: "__.___.___/____-__"});
}

AddValidateFormCreate = function(){
	Validation.add('validate-cpf', 'O CPF informado é inválido', function(v){
            return validateTypeDocument(v,1);
    });
    Validation.add('validate-cnpj', 'O CNPJ informado é inválido', function(v){
            return validateTypeDocument(v,2);
    });
    Validation.add('validate-tel-brazil', 'Entre com telefone válido: (99)9999-9999 ou caso tenha o 9º digito (99)9999-99999', function(v) {
            return Validation.get('IsEmpty').test(v) || /^([()])([0-9]){2}([)])([0-9]){4,5}([-])([0-9]){3,4}$/.test(v);
        });
   
    Validation.add('validate-zip-br', 'Entre com um cep válido: 99999-999', function(v) {
        return Validation.get('IsEmpty').test(v) || /^([0-9]){5}([-])([0-9]){3}$/.test(v);
    });

    Validation.add('validate-if-email-exist', '', function(v) {
                var isNewCustomer = false;
                new Ajax.Request(url_indetify, {
                    method: 'post',
                    asynchronous: false,
                    parameters: {email: v, form_key: jQuery("input[name='form_key']").val()},
 					requestHeaders: {Accept: 'application/json'},
                    	onSuccess: function(response) {
                        	if(response.responseJSON.is_user){
									isNewCustomer = false;
									Validation.get('validate-if-email-exist').error = 'Email já cadastrado';
									jQuery("#loginModel").modal();
								} else {
									isNewCustomer = true;
								}
                        }
                    });
                return isNewCustomer;
        });
}

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


PaymentFormActive = function(){
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
	        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
	            (e.keyCode == 65 && e.ctrlKey === true) || 
	            (e.keyCode >= 35 && e.keyCode <= 39)) {
	            return;
	        }
	        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
	            e.preventDefault();
	        }
	});
}

updateOrderMethod = function() {
	jQuery.ajax({
		type: "POST",
		url: updateordermethodurl,
		data: jQuery("#onestep_form").serialize(),			
		success: function(result) {
			jQuery(".moip-place-order").show();
			if(result.error == 1){
				jQuery(".erros_cadastro_valores").append('<li> - '+result.error_messages+'</li>');
				visibilyloading('end');
				jQuery('#ErrosFinalizacao').modal();
				return this;
			} 
			if (result.redirect) {
                location.href = encodeURI(result.redirect);
                return this;
            } else{
				window.location.href = encodeURI(checkoutsuccess);
			}
		},
		fail:function() {
			location.reload();
		},
	});
};
ChangeEvents = function(){
	UpdataQtyReview();
	saveShipping();
	validateOnBlur("onestep_form");
	
	jQuery("#onestep_form_identify").submit(function(e) {
		e.preventDefault();
		IdentifyUser();
	});

	jQuery("#form-loggin").submit(function(e) {
		e.preventDefault();
		Authenticate();
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
		if(form.validator.validate())	{
			updateOrderMethod();
			return true;
		} else {
			visibilyloading('end');
			getErroDescription();
			jQuery('#ErrosFinalizacao').modal();
			jQuery(".moip-place-order").show();
			jQuery('.validation-advice').delay(5000).fadeOut("slow");
			return false;
		}
	});
};

buscarEndereco = function(whatform) {
	jQuery(".hide-zip-code").addClass("show-zip-code");

	postcode 	= jQuery('#postcode').val();
	street_1 	= jQuery('#street1');
	street_2 	= jQuery('#street2');
	street_4 	= jQuery('#street4');
	city 		= jQuery('#city');
	region 		= jQuery('#region_id');

	jQuery.ajax({
		type:'GET',
		url:  url_busca_cep+'meio/cep/cep/' + postcode.replace(/[^\d\.]/g, ''),
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
				
			} else {
				street_1.attr('placeholder', 'Rua');
				street_2.attr('placeholder', 'N.º');
				street_4.attr('placeholder', 'Bairro');
				city.attr('placeholder', 'Cidade');
			}
			street_2.focus();
		},
		error: function(){
			jQuery(street_1).val("");
			jQuery(street_4).val("");
			jQuery(city).val("");
			jQuery(region).val("");
		},
	});
}

RemoveCupom = function(){
	jQuery.ajax({
			type: "POST",
			url: updatecouponurl,
			data: jQuery("#onestep_form").serialize(),			
			success: function(result) {
				location.reload();
			},
			fail:function() {
				location.reload();
			},
		});
}

updateCoupon = function() {
	visibilyloading();
	jQuery.ajax({
		type: "POST",
		url: updatecouponurl,
		data: jQuery("#onestep_form").serialize(),			
		success: function(result) {
			location.reload();
		},
		fail:function() {
			location.reload();
		},
	});
};

removeProduct = function(id) {
	jQuery.ajax({
		type: "POST",
		url: removeproducturl,
		data: "id=" + id,
		beforeSend: function(){
				visibilyloading();
		},
		success: function(msg) {
			location.reload();
		}
	});
}

logined = function()
{
	return customer_status;
}

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
}

updateQty = function(){
			visibilyloading();
            jQuery.ajax({
                url: updateqtyurl,
                data:  jQuery("#onestep_form").serialize(),
                    success: function(){
                        	location.reload();
                    }
            });
}

PayamentChange = function(){
	var payment = new Payment('co-payment-form', set_payment_form);
       	payment.init();
        PaymentFormActive();
	jQuery("input[name='payment[method]']").on('change', function () {
		savePaymentMethod();
	});
	jQuery("#credito_parcelamento").on('change', function () {
        savePaymentMethod();
    });
    jQuery("#moip_cc_count_cofre").on('change', function () {
        savePaymentMethod();
    });
    jQuery('[data-toggle="popover"]').popover();
}

startTimeShipping = function(){
	
}



})(jQuery);