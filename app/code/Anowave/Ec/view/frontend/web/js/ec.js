/**
 * Anowave Magento 2 Google Tag Manager Enhanced Ecommerce (UA) Tracking
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2018 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

if ('undefined' === typeof log)
{
	var log = function (message) 
	{
	   	window.console && console.log ? console.log(message) : null;
	};
}

var AEC = (function()
{
	return {
		debug: false,
		eventCallback: true,
		/**
		 * Track "Add to cart" from detail page
		 * 
		 * @param (domelement) context
		 * @param (object) dataLayer
		 * @return boolean
		 */
		ajax:function(context,dataLayer)
		{
			var element = jQuery(context), qty = jQuery(':radio[name=qty]:checked, [name=qty]').eq(0).val(), variant = [], variant_attribute_option = [], products = [];

			/**
		     * Validate "Add to cart" before firing an event
		     */
			var form = jQuery(context).closest('form');

			if (form.length)
			{
				if (!form.valid())
				{
					return true;
				}
			}
		
			if (!AEC.gtm())
			{
				/**
				 * Invoke original click event(s)
				 */
				if (element.data('click'))
				{
					/**
					 * Track time 
					 */
					AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));
					
					eval(element.data('click'));
				}
				
				return true;
			}

			if(element.data('configurable'))
			{
				var attributes = jQuery('[name^="super_attribute"]'), variants = [];

				jQuery.each(attributes, function(index, attribute)
				{
					if (jQuery(attribute).is('select'))
					{
						var name = jQuery(attribute).attr('name'), id = name.substring(name.indexOf('[') + 1, name.lastIndexOf(']'));

						var option = jQuery(attribute).find('option:selected');

						if (0 < parseInt(option.val()))
						{
							variants.push(
							{
								id: 	id,
								option: option.val(),
								text: 	option.text()
							});
						}
					}
				});

				/**
				 * Colour Swatch support
				 */
				if (!variants.length)
				{
					jQuery.each(AEC.SUPER, function(index, attribute)
					{
						var swatch = jQuery('div[attribute-code="' + attribute.code + '"]');

						if (swatch.length)
						{
							var variant = 
							{
								id: 	attribute.id,
								text:	'',
								option: null,
							};
							
							var select = swatch.find('select');

							if (select.length)
							{
								var option = swatch.find('select').find(':selected');

								if (option.length)
								{
									variant.text 	= option.text();
									variant.option 	= option.val();
								}
							}
							else 
							{
								var span = swatch.find('span.swatch-attribute-selected-option');

								if (span.length)
								{
									variant.text 	= span.text();
									variant.option 	= span.parent().attr('option-selected');
								}
							}

							variants.push(variant);
						}
					});
				}

				var SUPER_SELECTED = [];

				
				if (true)
				{
					for (i = 0, l = variants.length; i < l; i++)
					{
						for (a = 0, b = AEC.SUPER.length; a < b; a++)
						{
							if (AEC.SUPER[a].id == variants[i].id)
							{
								var text = variants[i].text;

								if (AEC.useDefaultValues)
								{
									jQuery.each(AEC.SUPER[a].options, function(index, option)
									{
										if (parseInt(option.value_index) == parseInt(variants[i].option))
										{
											text = option.admin_label;
										}
									});
								}
								
								variant.push([AEC.SUPER[a].label,text].join(AEC.Const.VARIANT_DELIMITER_ATT));

								/**
								 * Push selected options
								 */
								variant_attribute_option.push(
								{
									attribute: 	variants[i].id,
									option: 	variants[i].option
								})
							}
						}
					}
				}

				if (!variant.length)
				{
					/**
					 * Invoke original click event(s)
					 */
					if (element.data('click'))
					{
						/**
						 * Track time 
						 */
						AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));
						
						eval(element.data('click'));
					}
					
					return true;
				}
			}

			if (element.data('grouped'))
			{
				for (u = 0, y = window.G.length; u < y; u++)
				{
					var qty = Math.abs(jQuery('[name="super_group[' + window.G[u].id + ']"]').val());

					if (qty)
					{
						products.push(
						{
							'name': 		window.G[u].name,
							'id': 		    window.G[u].sku,
							'price': 		window.G[u].price,
							'category': 	window.G[u].category,
							'brand':		window.G[u].brand,
							'quantity': 	qty
						});
					}
				}
			}
			else
			{
				products.push(
				{
					'name': 		element.data('name'),
					'id': 		    element.data('id'),
					'price': 		element.data('price'),
					'category': 	element.data('category'),
					'brand':		element.data('brand'),
					'variant':		variant.join(AEC.Const.VARIANT_DELIMITER),
					'quantity': 	qty
				});
			}
			
			/**
			 * Affiliation attributes
			 */
			for (i = 0, l = products.length; i < l; i++)
			{
				(function(product)
				{
					jQuery.each(AEC.parseJSON(element.data('attributes')), function(key, value)
					{
						product[key] = value;
					});
				})(products[i]);
				
			}
			
			var data = 
			{
				'event': 'addToCart',
				'eventLabel': element.data('name'),
				'ecommerce': 
				{
					'currencyCode': AEC.currencyCode,
					'add': 
					{
						'actionField': 
						{
							'list': element.data('list')
						},
						'products': products
					},
					'options': variant_attribute_option
				},
				'eventCallback': function() 
				{
					if (AEC.eventCallback)
					{
						if (element.data('event'))
						{
							element.trigger(element.data('event'));
						}
					}
		     	},
				'currentStore': element.data('currentstore')
			};

			if (AEC.useDefaultValues)
			{
				data['currentstore'] = AEC.storeName;
			}
			
			/**
			 * Track event
			 */
			AEC.Cookie.add(data).push(dataLayer);

			/**
			 * Save backreference
			 */
			if (AEC.localStorage)
			{
				(function(products)
				{
					for (var i = 0, l = products.length; i < l; i++)
					{
						AEC.Storage.reference().set(
						{
							id: products[i].id,
							category: products[i].category
						});
					}
				})(products);
			}
			
			/**
			 * Track time 
			 */
			AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));

			if (AEC.facebook)
			{
				if ("undefined" !== typeof fbq)
				{
					(function(product, products, fbq)
					{
						var content_ids = [], price = 0;
						
						for (i = 0, l = products.length; i < l; i++)
						{
							content_ids.push(products[i].id);
			
							price += parseFloat(products[i].price);
						}

						fbq('track', 'AddToCart', 
						{
							content_name: 	product,
							content_ids: 	content_ids,
							content_type: 	'product',
							value: 			price,
							currency: 		AEC.currencyCode
						});

					})(element.data('name'), products, fbq);
				}
			}
			
			/**
			 * Invoke original click event(s)
			 */
			if (element.data('click'))
			{
				eval(element.data('click'));
			}

			return true;
		},
		/**
		 * Track "Add to cart" from listings page
		 * 
		 * @param (domelement) context
		 * @param (object) dataLayer
		 * @return boolean
		 */
		ajaxList:function(context,dataLayer)
		{
			var element = jQuery(context), products = [];
		
			if (!AEC.gtm())
			{
				/**
				 * Invoke original click event(s)
				 */
				if (element.data('click'))
				{
					/**
					 * Track time 
					 */
					AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));
					
					eval(element.data('click'));
				}
				
				return true;
			}

			products.push(
			{
				'name': 		element.data('name'),
				'id': 		    element.data('id'),
				'price': 		element.data('price'),
				'category': 	element.data('category'),
				'brand':		element.data('brand'),
				'position':		element.data('position'),
				'quantity': 	1
			});
			
			/**
			 * Affiliation attributes
			 */
			for (i = 0, l = products.length; i < l; i++)
			{
				(function(product)
				{
					jQuery.each(AEC.parseJSON(element.data('attributes')), function(key, value)
					{
						product[key] = value;
					});
				})(products[i]);				
			}

			var data = 
			{
				'event': 'addToCart',
				'eventLabel': element.data('name'),
				'ecommerce': 
				{
					'currencyCode': AEC.currencyCode,
					'add': 
					{
						'actionField': 
						{
							'list': element.data('list')
						},
						'products': products
					}
				},
				'eventCallback': function() 
				{
					if (AEC.eventCallback)
					{
						if (element.data('event'))
						{
							element.trigger(element.data('event'));
						}
					}
		     	},
				'currentStore': element.data('store')
			};

			/**
			 * Track event
			 */
			AEC.Cookie.add(data).push(dataLayer);

			/**
			 * Save backreference
			 */
			if (AEC.localStorage)
			{
				(function(products)
				{
					for (var i = 0, l = products.length; i < l; i++)
					{
						AEC.Storage.reference().set(
						{
							id: products[i].id,
							category: products[i].category
						});
					}
				})(products);
			}

			/**
			 * Track time 
			 */
			AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_ADD_TO_CART, element.data('name'), element.data('category'));
			
			/**
			 * Invoke original click event(s)
			 */
			if (element.data('click'))
			{
				eval(element.data('click'));
			}

			return true;
		},
		/**
		 * Track "Product click" event
		 *
		 * @param (domelement) context
		 * @param (object) dataLayer
		 * @return boolean
		 */
		click: function(context,dataLayer)
		{
			var element = jQuery(context);

			if (!AEC.gtm())
			{
				/**
				 * Track time 
				 */
				AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_PRODUCT_CLICK, element.data('name'), element.data('category'));
				
				return true;
			}

			var item = 
			{
				'name': 		element.data('name'),
				'id': 			element.data('id'),
				'price': 		element.data('price'),
				'category': 	element.data('category'),
				'brand':		element.data('brand'),
				'quantity': 	element.data('quantity'),
				'position':		element.data('position')
			};
				
			jQuery.each(AEC.parseJSON(element.data('attributes')), function(key, value)
			{
				item[key] = value;
			});
			
			var data = 
			{
				'event': 'productClick',
				'eventLabel': element.data('name'),
				'ecommerce': 
				{
					'click': 
					{
						'actionField': 
						{
							'list': element.data('list')
						},
						'products': 
						[
							item
						]
					}
				},
				'eventCallback': function() 
				{
					if (AEC.eventCallback)
					{
						if (element.data('event'))
						{
							element.trigger(element.data('event'));
						}
						
						if (element.data('click'))
						{
							eval(element.data('click'));
						}
						else if (element.is('a'))
						{
							document.location = element.attr('href');
						}
						else if (element.is('img') && element.parent().is('a'))
						{
							document.location = element.parent().attr('href');
						}
						else 
						{
							return true;
						}
					}
		     	},
		     	'eventTarget': (function(element)
    	     	{
    	     		/**
    	     		 * Default target
    	     		 */
    	     		var target = 'Default';
    	     		
    	     		/**
    	     		 * Check if element is anchor
    	     		 */
    	     		if (element.is('a'))
    	     		{
    	     			target = 'Link';
    	     			
    	     			if (element.find('img').length > 0)
    	     			{
    	     				target = 'Image';
    	     			}
    	     		}
    	     		
    	     		if (element.is('button'))
    	     		{
    	     			target = 'Button';
    	     		}
    	     		
    	     		return target;
    	     		
    	     	})(element),
		     	'currentStore': element.data('store')	
			};

			/**
			 * Push data
			 */
			AEC.Cookie.click(data).push(dataLayer);
			
			/**
			 * Track time 
			 */
			AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_PRODUCT_CLICK, element.data('name'), element.data('category'));

			if (element.data('click'))
			{
				eval(element.data('click'));
			}
			
			if (AEC.eventCallback)
			{
				return false;
			}
			
			return true;
		},
		/**
		 * Track "Remove From Cart" event
		 *
		 * @param (domelement) context
		 * @param (object) dataLayer
		 * @return boolean
		 */
		remove: function(context, dataLayer)
		{
			var element = jQuery(context);

			if (!AEC.gtm())
			{
				/**
				 * Track time 
				 */
				AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_REMOVE_FROM_CART, element.data('name'), element.data('category'));
				
				return true;
			}

			var item = 
			{
				'name': 		element.data('name'),
				'id': 			element.data('id'),
				'price': 		element.data('price'),
				'category': 	element.data('category'),
				'brand':		element.data('brand'),
				'quantity': 	element.data('quantity')	
			};
			
			
			jQuery.each(AEC.parseJSON(element.data('attributes')), function(key, value)
			{
				item[key] = value;
			});
			
			var data = 
			{
				'event': 'removeFromCart',
				'eventLabel': element.data('name'),
				'ecommerce': 
				{
					'remove': 
					{   
						'actionField': 
						{
							'list': element.data('list')
						},
						'products': 
						[
							item
						]
					}
				}
			};
			
			if (AEC.Message.confirm)
			{
				(function($)
				{
					require(['Magento_Ui/js/modal/confirm'], function(confirmation) 
					{
					    confirmation(
					    {
					        title: AEC.Message.confirmRemoveTitle,
					        content: AEC.Message.confirmRemove,
					        actions: 
					        {
					            confirm: function()
					            {
					            	/**
									 * Track event
									 */
									AEC.Cookie.remove(data).push(dataLayer);

									/**
									 * Track time 
									 */
									AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_REMOVE_FROM_CART, element.data('name'));
									
									/**
									 * Execute standard data-post
									 */
					            	var params = $(element).data('post-action');
					            	
					            	$(document).dataPost('postData', params);
					            },
					            cancel: function()
					            {
					            	return false;
					            },
					            always: function()
					            {
					            	return false;
					            }
					        }
					    });
					});
	
				})(jQuery);
			}
			else 
			{
				/**
				 * Track event
				 */
				AEC.Cookie.remove(data).push(dataLayer);

				/**
				 * Track time 
				 */
				AEC.Time.track(dataLayer, AEC.Const.TIMING_CATEGORY_REMOVE_FROM_CART, element.data('name'));
			}
			
			return false;
		},
		Time: (function()
		{
			var T = 
			{
				event: 			'trackTime',
				timingCategory:	'',
				timingVar:		'',
				timingValue:	-1,
				timingLabel:	''
			};

			var time = new Date().getTime();
			
			return {
				track: function(dataLayer, category, variable, label)
				{
					T.timingValue = (new Date().getTime()) - time;
					
					if (category)
					{
						T.timingCategory = category;
					}

					if (variable)
					{
						T.timingVar = variable;
					}

					if (label)
					{
						T.timingLabel = label;
					}
					
					/**
					 * Track time
					 */
					dataLayer.push(T);
				},
				trackContinue: function(dataLayer, category, variable, label)
				{
					this.track(dataLayer, category, variable, label);

					/**
					 * Reset time
					 */
					time = new Date().getTime();
				}
			}
		})(),
		Persist:(function()
		{
			var DATA_KEY = 'persist'; 

			var proto = 'undefined' != typeof Storage ? 
			{
				push: function(key, entity)
				{
					/**
					 * Get data
					 */
					var data = this.data();

					/**
					 * Push data
					 */
					data[key] = entity;

					/**
					 * Save to local storage
					 */
					localStorage.setItem(DATA_KEY, JSON.stringify(data));

					return this;
				},
				data: function()
				{
					var data = localStorage.getItem(DATA_KEY);
					
					if (null !== data)
					{
						return JSON.parse(data);
					}

					return {};
				},
				merge: function()
				{
					var data = this.data();
					var push = 
					{
						persist: {}
					}

					for (var i in data)
					{
						push.persist[i] = data[i];
					}

					dataLayer.push(push);

					return this;
				},
				clear: function()
				{
					/**
					 * Reset private local storage
					 */
					localStorage.setItem(DATA_KEY,JSON.stringify({}));

					return this;
				}
			} : {
				push: 	function(){}, 
				merge: 	function(){},
				clear: 	function(){}
			}

			/**
			 * Constants
			 */
			proto.CONST_KEY_PROMOTION = 'persist_promotion';

			return proto;
			
		})(),
		Checkout: (function()
		{
			return {
				data: {},
				tracked: {},
				step: function(previous, current, currentCode)
				{
					if (this.data && this.data.hasOwnProperty('ecommerce'))
					{	
						this.data.ecommerce.checkout.actionField.step = ++current;

						dataLayer.push(this.data);
						
						if (AEC.debug)
						{
							log('Checkout step:' + this.data.ecommerce.checkout.actionField.step);
						}
					}
					
					return this;
				},
				stepOption: function(step, option)
				{
					if (!option)
					{
						return this;
					}
					
					if (!option.toString().length)
					{
						return this;
					}
					
					dataLayer.push(
	    			{
	    				'event': 'checkoutOption',
	    				'ecommerce': 
	    				{
	    					'checkout_option': 
	    					{
	    						'actionField': 
	    						{
	    							'step': step,
	    							'option': option
	    						}
	    					}
	    				}
	        		});
					
					if (AEC.debug)
					{
						log('Checkout step:' + step + ' Option:' + option);
					}
					
					return this;
				}
			}
		})(),
		Cookie: (function() //This is an experimental feature to overcome FPC (Full Page Cache) related issues (beta)
		{
			return {
				data: null,
				privateData: null,
				push: function(dataLayer)
				{
					if (this.data)
					{
						/**
						 * Push data
						 */
						dataLayer.push(this.data);

						/**
						 * Reset data to prevent further push
						 */
						this.data = null;
					}
					
					return this;
				},
				pushPrivate: function()
				{
					var data = this.getPrivateData();
					
					if (data)
					{
						dataLayer.push(
						{
							privateData: data
						});
					}
					
					return this;
				},
				/**
				 * Augment products array [] and map category with localStorage reference
				 */
				augment: function(products)
				{
					/**
					 * Parse data & apply local reference
					 */
					var reference = AEC.Storage.reference().get();
					
					if (reference)
					{
						for (var i = 0, l = products.length; i < l; i++)
						{
							for (var a = 0, b = reference.length; a < b; a++)
							{
								if (products[i].id.toString().toLowerCase() === reference[a].id.toString().toLowerCase())
								{
									products[i].category = reference[a].category;
								}
							}
						}
					}
					
					return products;
				},
				click: function(data)
				{
					this.data = data;
					
					return this;
				},
				add: function(data)
				{
					this.data = data;
					
					return this;
				},
				remove: function(data)
				{
					this.data = data;
					
					if (AEC.localStorage)
					{
						this.data.ecommerce.remove.products = this.augment(this.data.ecommerce.remove.products);
					}

					return this;
				},
				visitor: function(data)
				{
					this.data = data;
					
					return this;
				},
				detail: function(data)
				{
					this.data = data;
					
					return this;
				},
				purchase: function(data)
				{
					this.data = data;
					
					if (AEC.localStorage)
					{
						this.data.ecommerce.purchase.products = this.augment(this.data.ecommerce.purchase.products);
					}
					
					return this;
				},
				impressions: function(data)
				{
					this.data = data;
					
					return this;
				},
				checkout: function(data)
				{
					this.data = data;
					
					if (AEC.localStorage)
					{
						this.data.ecommerce.checkout.products = this.augment(this.data.ecommerce.checkout.products);
					}
					
					return this;
				},
				checkoutOption: function(data)
				{
					this.data = data;
					
					return this;
				},
				promotion: function(data)
				{
					this.data = data;
					
					return this;
				},
				promotionClick: function(data)
				{
					this.data = data;
					
					return this;
				},
				getPrivateData: function()
				{
					if (!this.privateData)
					{
						var cookie = this.get('privateData');
						
						if (cookie)
						{
							this.privateData = this.parse(cookie);
						}
					}
					
					return this.privateData;
				},
				get: function(name)
				{
					var start = document.cookie.indexOf(name + "="), len = start + name.length + 1;
					
					if ((!start) && (name != document.cookie.substring(0, name.length))) 
					{
					    return null;
					}
					
					if (start == -1) 
					{
						return null;
					}
										
					var end = document.cookie.indexOf(String.fromCharCode(59), len);
										
					if (end == -1) 
					{
						end = document.cookie.length;
					}
					
					return decodeURIComponent(document.cookie.substring(len, end));
				},
				unset: function(name) 
				{   
	                document.cookie = name + "=" + "; path=/; expires=" + (new Date(0)).toUTCString();
	                
	                return this;
	            },
				parse: function(json)
				{
					var json = decodeURIComponent(json.replace(/\+/g, ' '));
					
	                return JSON.parse(json);
				}
			}
		})(),
		/**
		 * localStorage cache
		 */
		Storage: (function(api)
		{
			return {
				set: function(property, value)
				{
					if ('undefined' !== typeof(Storage))
					{
						localStorage.setItem(property, JSON.stringify(value));
					}
					
					return this;
					
				},
				get: function(property)
				{
					if ('undefined' !== typeof(Storage))
					{
						return JSON.parse(localStorage.getItem(property));
					}
					
					return null;
				},
				reference: function()
				{
					return (function(storage)
					{
						return {
							set: function(reference)
							{
								var current = storage.get('category.add') || [];
								
								var exists = (function(current, reference)
								{
									for (i = 0, l = current.length; i < l; i++)
									{
										if (current[i].id.toLowerCase() === reference.id.toLowerCase())
										{
											/**
											 * Update category
											 */
											current[i].category = reference.category;
											
											return true;
										}
									}
									
									return false;
									
								})(current, reference);
								
								if (!exists)
								{
									current.push(reference);
								}
								
								storage.set('category.add', current);
								
								return this;
							},
							get: function()
							{
								return storage.get('category.add');
							}
						}
					})(this);
				}
			}
		})(),
		/**
		 * Check if GTM snippet is available on page.
		 *
		 * @param void
		 * @return boolean
		 */
		gtm: function()
		{
			if ("undefined" === typeof google_tag_manager)
			{
				/**
				 * Log error to console
				 */
				log('Unable to detect Google Tag Manager. Please verify if GTM install snippet is available.');
				
				return false;
			}

			return true;
		},
		parseJSON: function(content)
		{
			if ('object' === typeof content)
			{
				return content;
			}
			
			if ('string' === typeof content)
			{
				try 
				{
					return JSON.parse(content);
				}
				catch (e){}
			}
			
			return {};
		}
	}
})();