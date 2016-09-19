var DF;

$(function() {
    DF = new DashboardFilter();
});

function DashboardFilter() {
    this.appListBox = null;
    this.trackerListBox = null;
    this.adsPlatformListBox = null;
    this.dateListBox = null;
    
    this.dateFromBox = null;
    this.dateToBox = null;
    
    this.activeFilter = {
    	'app_id' 		: null,
    	'tracker'		: null,
    	'platforms'  	: null,
    	'date_start'	: null,
    	'date_stop'		: null
    };
    
    this.init = function() {
    	this.reload();
    	this.initDatepicker();
    };

	this.reload = function() {
    	this.accountBox = $('#sidebar_header .account');
        this.appListBox = $('#sidebar_applist');
    	this.trackerListBox = $('.filter_box.tracker');
    	this.adsPlatformListBox = $('.filter_box.ads');
    	this.dateListBox = $('.calendarfilter_box ul');
    	
    	this.bindEvents();
    };

	this.bindEvents = function() {
		var self = this;
		
		this.appListBox.find('li').off('click').on('click', function(event) {
			var el = ($(event.target).is('li')) ? $(event.target) : $(event.target).closest('li');
			if (el.is('.add_app')) return true;
			
			self.appListBox.find('li').removeClass('active');
			el.addClass('active');
			
			var app_id = el.attr('data-id');
			self.checkAppType(app_id);
			self.activeFilter.app_id = (self.isAllValueFilter(app_id)) ? null : app_id;
			
			self.showDashboard();

			return false;
		});
		
		this.trackerListBox.find('li').off('click').on('click', function(event) {
			var el = ($(event.target).is('li')) ? $(event.target) : $(event.target).closest('li');
			self.trackerListBox.find('li').removeClass('active');
			el.addClass('active');
			
			self.activeFilter.tracker = (el.attr('data-id') === undefined) ? null : el.attr('data-id');
			self.showDashboard();

			return false;
		});
		
		this.bindPlatformEvents();
		
		this.accountBox.off('click').on('click', function(event) {
			if ($(event.target).is('.logout')) return true;
			var el = ($(event.target).is('h3')) ? $(event.target) : $(event.target).closest('h3');
			self.accountBox.toggleClass('active');
			return false;
		});
	};
	
	this.bindPlatformEvents = function() {
		var self = this;
		this.adsPlatformListBox.find('li').off('click').on('click', function(event) {
			var el = ($(event.target).is('li')) ? $(event.target) : $(event.target).closest('li');
			self.adsPlatformListBox.find('li').removeClass('active');
			el.addClass('active');
			
			var platform = el.attr('data-id');
			$('#ads_platform_keys_box').find('.ads_platform_keys_show_box')
					.addClass('hidden')
					.removeClass('edit')
					.find('.key').each(function(ind, keyBox){
						$(keyBox)
							.attr({'contenteditable' : false})
							.text($(keyBox).attr('data-oldvalue'));
					});
			if(el.is('.disabled')) {
				$('#ads_platform_keys_box').find('.ads_platform_keys_show_box.' + platform).removeClass('hidden');
			} else {
				self.activeFilter.platforms = (platform === undefined) ? null  : platform;
				self.showDashboard();
			}

			return false;
		});  
		
		$('#ads_platform_keys_box .ads_platform_keys_show_box').off('click').on('click', function(event) {
				if ($(this).is('.edit')) return false;
				$(this).addClass('edit');
				$(this).find('.key').each(function(ind, keyBox){
					$(keyBox).attr({
						'contenteditable' : true,
						'data-oldvalue' : $(keyBox).text()
					});
				});	
		});
		
		$('#ads_platform_keys_box .ads_platform_keys_show_box .save_keys').off('click').on('click', function(event) {
				var closestBox = $(this).closest('.ads_platform_keys_show_box');
				if (!closestBox.is('.edit')) return false;
				$(this).addClass('edit');
				var keys = {};
				var platform = closestBox.attr('data-type');
				var isEmpty = false;
				closestBox.find('.key').each(function(ind, keyBox){
					$(keyBox).attr({'data-oldvalue' : $(keyBox).text() });
					keys[$(keyBox).attr('data-type')] = jQuery.trim($(keyBox).text());
					if (!$(keyBox).text()) isEmpty=true;
				});
				
				if (isEmpty) {
					var platformLi = self.adsPlatformListBox.find('li.all');
				} else {
					var platformLi = self.adsPlatformListBox.find('li[data-id="'+platform+'"]');
					platformLi
							.removeClass('disabled')
							.find('.date').each(function(ind, dateBox){
								($(dateBox).is('.off')) ?
									 $(dateBox).addClass('hidden') :
									 $(dateBox).removeClass('hidden');
							});
				}
				self.savePlatformKeys(platform, keys);
				
				platformLi.trigger('click');
		});
	};
	
	this.savePlatformKeys = function(platform, keys) {
		var self = this;
		var app_id = this.activeFilter.app_id;
		if (!app_id || !platform) return false;
		var data = {}; data['keys'] = {}; data['keys'][app_id] = {}; data['keys'][app_id][platform] = keys;

		$.ajax({
			url : 'site/ajax-save-adkey',
			type: 'post',
		    data: data,
		    success: function (response) {
		    	if (response && response.status == 'ok') {
		    		return true;
		    	} else {
		    		self.showError(response.error);
		    	}
		    },
		    error : function() {
		    	self.showError('Произошла ошибка, попробуйте позже');
		    }
		});
	};
	
	this.checkAppType = function(appId) {
		if ((jQuery.inArray(appId, ['ios','android']) != -1) || this.isAllValueFilter(appId)) {
			this.trackerListBox.hide();
			this.adsPlatformListBox.hide();
		} else {
			this.trackerListBox.show();
			this.adsPlatformListBox.show();
		}
	};
	
	this.showDashboard = function() {
		var self = this;

		for (var i in this.activeFilter) {
			if (this.activeFilter[i] === null || this.activeFilter[i] === undefined) {
				delete this.activeFilter[i];
			}
		}
		this.showLoader();
		$.ajax({
			url : 'site/ajax-index',
			type: 'post',
		    data: this.activeFilter,
		    cache: false,
		    success: function (response) {
		    	if (response && response.status == 'ok' && response.html) {
		    		$('#appinfo').html(response.html);
		    		self.reload();
		    		self.hideLoader();
		    	} else {
		    		self.showError(response.error);
		    	}
		    },
		    error : function(response) {
		    	self.showError('Произошла ошибка, попробуйте позже');
		    }
		});
	};
	
	this.showError = function(message) {
		$('#error').html(message).removeClass('hidden');
		setTimeout(function(){
			$('#error').addClass('hidden');
		}, 2000);
		this.hideLoader();
	};
	
	this.isAllValueFilter = function(value) {
		return (!value || value == 'all') ? true : false;
	};
	
	this.showLoader = function() {
		var self = this;
		$('#loading').addClass('active');
	};
	
	this.hideLoader = function() {
		$('#loading').removeClass('active');
	};
	
	this.initDatepicker = function(){
		var self = this;
		var dateFormat = "d MM yy";
		var options = {
				defaultDate : "+1w",
				dateFormat : dateFormat,
				numberOfMonths : 1,
				regional : ['ru', 'en']};
		this.dateFromBox = $("#date_from").datepicker(options).on("change", function() {
			self.afterSelectDatepicker(true);
		});
		this.dateToBox = $("#date_to").datepicker(options).on("change", function() {
			self.afterSelectDatepicker(true);
		});

		function getDate(element) {
			var date;
			try {
				date = $.datepicker.parseDate(dateFormat, element.value);
			} catch( error ) {
				date = null;
			}
			return date;
		};
		
		var reload = false;
		             
		this.dateListBox.find('li').off('click').on('click', function(ev, notReload) {
			var el = $(this);
			self.dateListBox.find('li').removeClass('active');
			el.addClass('active');
			
			var from = new Date();
			var to = new Date();
			
			switch (el.attr('data-type')) {
				case 'yesterday' : {
					to.setDate(to.getDate()-1);
		            from.setDate(from.getDate()-1);
		            break;
				}
				case 'week' : {
					to.setDate(to.getDate());
		            from.setDate(from.getDate()-7);
		             break;
				}
				case 'month' : {
					to.setDate(to.getDate());
		            from.setMonth(from.getMonth()-1);
		            break;
				}
				case 'all' : {
					from = 0;
		            to = 0;
		            break;
				}
				default: break;  
		    }
		    
		    self.dateFromBox.datepicker('setDate', from);
		    self.dateToBox.datepicker('setDate', to);
		    self.afterSelectDatepicker(!notReload);
			return false;
		});
		
		this.dateListBox.find('li').eq(0).trigger('click', 1);
		reload = true;
	};
	
	this.afterSelectDatepicker = function(reload) {
        var dateFrom = this.dateFromBox.datepicker('getDate');
        var dateTo = this.dateToBox.datepicker('getDate');
        
        this.dateToBox.datepicker("option", "minDate", dateFrom);
        this.dateFromBox.datepicker("option", "maxDate", dateTo);
       	
       	this.activeFilter['date_start'] = (dateFrom) ? ($.datepicker.formatDate('@', dateFrom)/1000) : null;
       	this.activeFilter['date_stop'] = (dateTo) ? ($.datepicker.formatDate('@', dateTo)/1000 + 86400 - 1) : null;
       	
        if (reload) this.showDashboard();
    };
    
	this.init();
}


