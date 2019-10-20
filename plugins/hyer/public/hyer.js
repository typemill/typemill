const myaxios = axios.create({
    baseURL: document.getElementsByTagName("base")[0].href
});

Vue.component('directory', {
    data: function () {
      return {
          ads: [],
          products: [],
          selectedProduct: "",
          selectedRegion: "",
          token: "",
          message: false,
          messageType: '',
      }
    },
    template:   '<div>' + 
    				'<div>' +
    					'<form action="#">' + 
    						'<fieldset class="bn ma0 pa0">' +
                                '<div class="flex-ns items-end justify-around">' +
                                    '<div class="w-100 mr3-ns">' +
                                        '<label for="country" class="f6 b db mb2 mt4 tl">CMS</label>' +
                                        '<select v-model="selectedProduct" class="input-reset outline-focus ba b--black-20 ph2 pv3 mb2 db w-100" name="location[index][country]" v-model="location.country" required>' +
                                            '<option value="">-</option>' +
                                            '<option v-for="product in products">{{ product }}</option>' +
                                        '</select>' +
                                    '</div>' +
                                    '<div class="w-100 mr3-ns">' +
                                        '<label for="region" class="f6 b db mb2 mt4 tl">Region</label>' +
                                        '<select v-model="selectedRegion" class="input-reset outline-focus ba b--black-20 ph2 pv3 mb2 db w-100" name="location[index][region]" v-model="location.region" required>' +
                                            '<option value="">-</option>' +
                                            '<option>Baden-Würtemberg</option>' +
                                            '<option>Bayern</option>' +
                                            '<option>Berlin</option>' +
                                            '<option>Brandenburg</option>' +
                                            '<option>Bremen</option>' +
                                            '<option>Hamburg</option>' +
                                            '<option>Hessen</option>' +
                                            '<option>Mecklenburg-Vorpommern</option>' +
                                            '<option>Niedersachsen</option>' +
                                            '<option>Nordrhein-Westfalen</option>' +
                                            '<option>Rheinland-Pfalz</option>' +
                                            '<option>Saarland</option>' +
                                            '<option>Sachsen</option>' +
                                            '<option>Sachsen-Anhalt</option>' +
                                            '<option>Schleswig-Holstein</option>' +
                                            '<option>Thüringen</option>' +
                                        '</select>' +
                                	'</div>' +
	                                '<button @click.prevent="searchProducts" class="w-100 w-30-ns link bg-customized-2 custombutton link-white dim bn mb2 pa3 mt3 mt0-ns pointer">Suchen</button>' +
                                '</div>' +
    						'</fieldset>' +
    					'</form>' +
    				'</div>' +
    				'<div v-if="message" class="w-100 pa3 tc mv3 customized-2 ba b--customized-2">{{ message }}</div>' +    				
    				'<div v-for="ad in ads">' + 
	                    '<div class="w-100 bg-customized dark-gray flex dib tl pa0 mv3 f5 relative">' + 
	                        '<div class="w-100 ba b--light-gray pa0 shadow-hover">' +
	                            '<a :href="getLink(ad)" rel="ugc sponsored" target="_blank" class="link dark-gray pa0 mt3 mb0">' + 
	                                '<div v-if="ad.pro" class="absolute-ns tc top-0 right-0 ba b--dark-gray pa1 f6 ma3">Premium</div>' +
                                    '<h3 class="mt4 mb1 pv0 ph3 underline-hover">{{ getTitle(ad) }}</h3>' +
	                                '<small class="pv0 ph3 ma0 underline-hover">{{ getLink(ad) }}</small>' +
	                                '<p class="mt1 mb3 pv0 ph3 underline-hover">{{ getTeaser(ad) }}</p>' +
	                            '</a>' +
	                            '<div class="f5 pv2 ph3 bt dark-gray b--moon-gray w-100 flex">' +
	                                '<div class="tl w-50">' + 
	                                    '<svg class="icon icon-location"><use xlink:href="#icon-location"></use></svg> {{ ad.city }} ' +
	                                    '<span class="pl2"><svg class="icon icon-location"><use xlink:href="#icon-users"></use></svg> {{ ad.size}}</span>' + 
	                                '</div>' +
	                                '<div class="tr w-50">Web:' +
	                                    '<a class="link dark-gray ph2" rel="ugc sponsored" :href="ad.company_link" target="_blank"><svg class="icon icon-location"><use xlink:href="#icon-home"></use></svg></a>' +
	                                    '<a class="link dark-gray ph2" rel="ugc sponsored" v-if="ad.sm_link_1" :href="ad.sm_link_1" target="_blank"><svg :class="getSocialIcon(ad.sm_link_1)" class="icon"><use :xlink:href="getSocialIcon(ad.sm_link_1, true)"></use></svg></a>' +
	                                    '<a class="link dark-gray ph2" rel="ugc sponsored" v-if="ad.sm_link_2" :href="ad.sm_link_2" target="_blank"><svg :class="getSocialIcon(ad.sm_link_2)" class="icon"><use :xlink:href="getSocialIcon(ad.sm_link_2, true)"></use></svg></a>' +  
	                                '</div>' +                             
	                            '</div>' +
	                        '</div>' +
	                    '</div>' +
                	'</div>' +
	                '<div class="w-100 bg-white dark-gray dib tl pa0 mv3 f5">' + 
	                    '<div class="w-100 ba b--light-gray pa0 tc">' +
	                        '<h3 class="mt4 mb0 pv0 ph3">Web-Agentur oder Freelancer?</h3>' +
	                        '<p class="mt3 mb3 pv0 ph3">Dann ist genau hier Ihre Expertise gefragt!<br/> Mit einem Dienstleister-Eintrag auf cmsstash.de</p>' +
	                        '<a href="https://service.cmsstash.de" class="link bg-customized-2 link-white dim ba1 b--red mt0 mb4 ph3 pv2 dib br1">Eintrag erstellen</a>' +
	                    '</div>' +
	                '</div>' +
                '</div>',
    mounted: function(){

    	var products 	= document.getElementById("hyerapp").dataset.products;
    	this.products 	= products.split(',');

    	this.token		= document.getElementById("hyerapp").dataset.token;

        self = this;

    	var access 		= document.getElementById("hyerapp").dataset.access;

        myaxios.get('/latestadsfe30s8edw4wdkp?access='+access)
        .then(function (response) {

            self.ads = response.data.ads;
            /* if matomo is on, check dom for new links */
            if(_paq)
            {
                Vue.nextTick(function () {
                    _paq.push(['enableLinkTracking']);
                })
            }
        })
        .catch(function (error) {});
    },
    methods: {
    	searchProducts: function()
    	{
            self = this; 

            myaxios.get('/searchadsfe30s8edw4wdkp',{
                params: {
                    token: this.token,
                    product: this.selectedProduct,
                    region: this.selectedRegion
                }
            })
	        .then(function (response) {
	            self.ads = response.data.ads;
                /* if matomo is on, check dom for new links */
                if(_paq)
                {
                    Vue.nextTick(function () {
                        _paq.push(['enableLinkTracking']);
                    })
                }
	        })
	        .catch(function (error)
            {
                if(error.response)
                {
                    self.message = error.response.data.message;
                    self.messageType = 'error';
                }
	        });
    	},
    	getTitle: function(product)
    	{
    		if(product.ad_title)
    		{
    			return product.ad_title;
    		}
    		return product.company_name;
    	},
    	getTeaser: function(product)
    	{
    		if(product.ad_teaser)
    		{
    			return product.ad_teaser;
    		}
    		return product.company_description;
    	},
    	getLink: function(product)
    	{
    		if(product.ad_link)
    		{
    			return product.ad_link;
    		}
    		return product.company_link;
    	},
        getSocialIcon: function(icon,id)
        {
            var prefix = id ? '#' : '';
            if(icon.indexOf("twitter") > -1){ return prefix+'icon-twitter' }
            else if(icon.indexOf("facebook") > -1){ return prefix+'icon-facebook' }
            else if(icon.indexOf("xing") > -1){ return prefix+'icon-xing2' }
            else if(icon.indexOf("linkedin") > -1){ return prefix+'icon-linkedin2' }
            return prefix+'icon-link';
        },    	
    }
});


Vue.component('premiumads', {
    data: function () {
      return {
          ads: [],
          fill: [],
      }
    },
    template:   '<div>' +
    				'<div v-for="ad in ads">' + 
	                    '<div class="w-100 bg-customized dark-gray flex dib tl pa0 mv3 f5 relative">' + 
	                        '<div class="w-100 ba b--light-gray pa0 shadow-hover">' +
	                            '<a rel="ugc sponsored" :href="getLink(ad)" target="_blank" class="link dark-gray pa0 mt3 mb0">' + 
                                    '<div v-if="ad.pro" class="absolute-ns tc top-0 right-0 ba b--dark-gray pa1 f6 ma3">Premium</div>' +
	                                '<h3 class="mt4 mb1 pv0 ph3 underline-hover">{{ getTitle(ad) }}</h3>' +
	                                '<small class="pv0 ph3 ma0 underline-hover">{{ getLink(ad) }}</small>' +
	                                '<p class="mt1 mb3 pv0 ph3 underline-hover">{{ getTeaser(ad) }}</p>' +
	                            '</a>' +
	                            '<div class="f5 pv2 ph3 bt dark-gray b--moon-gray w-100 flex">' +
	                                '<div class="tl w-50">' + 
	                                    '<svg class="icon icon-location"><use xlink:href="#icon-location"></use></svg> {{ ad.city }} ' +
	                                    '<span class="pl2"><svg class="icon icon-location"><use xlink:href="#icon-users"></use></svg> {{ ad.size}}</span>' + 
	                                '</div>' +
	                                '<div class="tr w-50">Web:' +
	                                    '<a class="link dark-gray ph2" rel="ugc sponsored" :href="ad.company_link" target="_blank"><svg class="icon icon-location"><use xlink:href="#icon-home"></use></svg></a>' +
	                                    '<a class="link dark-gray ph2" rel="ugc sponsored" v-if="ad.sm_link_1" :href="ad.sm_link_1" target="_blank"><svg :class="getSocialIcon(ad.sm_link_1)" class="icon"><use :xlink:href="getSocialIcon(ad.sm_link_1, true)"></use></svg></a>' +
	                                    '<a class="link dark-gray ph2" rel="ugc sponsored" v-if="ad.sm_link_2" :href="ad.sm_link_2" target="_blank"><svg :class="getSocialIcon(ad.sm_link_2)" class="icon"><use :xlink:href="getSocialIcon(ad.sm_link_2, true)"></use></svg></a>' +  
	                                '</div>' +
	                            '</div>' +
	                        '</div>' +
	                    '</div>' +
                	'</div>' +
    				'<div v-if="ads.length < 3">' + 
	                    '<div class="w-100 bg-white dark-gray dib tl pa0 mv3 f5">' + 
	                        '<div class="w-100 ba b--light-gray pa0 tc">' +
	                            '<h3 class="mt4 mb0 pv0 ph3">Web-Agentur oder Freelancer?</h3>' +
	                            '<p class="mt3 mb3 pv0 ph3">Dann ist genau hier Ihre Expertise gefragt!<br/> Mit einem Dienstleister-Eintrag auf cmsstash.de</p>' +
	                            '<a href="https://service.cmsstash.de" class="link bg-customized-2 link-white dim ba1 b--red mt0 mb4 ph3 pv2 dib br1">Eintrag erstellen</a>' +
	                        '</div>' +
	                    '</div>' +
                	'</div>' + 	
                '</div>',
    mounted: function(){

        self = this;
    	var access = document.getElementById("hyerapp").dataset.access;

        myaxios.get('/proadsfe30s8edw4wdkp?access='+access)
        .then(function (response) {
            self.ads = response.data.ads;
            var restads = 3 - self.ads.length;
            for(var i = 0; i < restads; i++)
            {
            	self.fill.push(1);
            }
            /* if matomo is on, check dom for new links */
            if(_paq)
            {
                Vue.nextTick(function () {
                   _paq.push(['enableLinkTracking']);
                })
            }
        })
        .catch(function (error) {
        });
    },
    methods: {
    	getTitle: function(product)
    	{
    		if(product.ad_title)
    		{
    			return product.ad_title;
    		}
    		return product.company_name;
    	},
    	getTeaser: function(product)
    	{
    		if(product.ad_teaser)
    		{
    			return product.ad_teaser;
    		}
    		return product.company_description;
    	},
    	getLink: function(product)
    	{
    		if(product.ad_link)
    		{
    			return product.ad_link;
    		}
    		return product.company_link;
    	},
        getSocialIcon: function(icon,id)
        {
            var prefix = id ? '#' : '';
            if(icon.indexOf("twitter") > -1){ return prefix+'icon-twitter' }
            else if(icon.indexOf("facebook") > -1){ return prefix+'icon-facebook' }
            else if(icon.indexOf("xing") > -1){ return prefix+'icon-xing2' }
            else if(icon.indexOf("linkedin") > -1){ return prefix+'icon-linkedin2' }
            return prefix+'icon-link';
        },    	
    }
});

var app = new Vue({
    el: "#hyerapp",
    data: {
        disabled: false,
        message: '',
        messageType: '',
    },
});