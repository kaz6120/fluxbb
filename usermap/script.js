/**
 * Copyright (C) 2010 Justgizzmo.com
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

/**
 * The base map object
 */
(function(undefined){

	// Shorthand stuff!!
	var g = google.maps;

	/**
	 * The Core Map
	 */
	function UserMap(options)
	{
		// Put the options onto the object
		this.options = options || {};

		// Extend the default options with the provided options
		var opts = $.extend({}, UserMap.defaults, this.options);

		// The map canvas
		var canvas = $('#user_map_canvas');

		// Make the map canvas the correct height
		canvas.height(opts.height);

		this.theMap = new g.Map(canvas[0], {
			center: new g.LatLng(opts.center[0],opts.center[1]),
			zoom: opts.zoom,
			mapTypeId: g.MapTypeId.ROADMAP,
			scrollwheel: opts.scrollwheel
		});

		if ( opts.contextMenu !== false )
		{
			// try to create the menu, incase it wasnt included
			try {

				// Add the context menu
				var menu = new contextMenu({map:this.theMap});

				// Add some default items to the menu
				menu.addItem('Zoom In', function(map, latLng){
					map.setZoom( map.getZoom() + 1);
					map.panTo( latLng );
				});

				menu.addItem('Zoom Out', function(map, latLng){
					map.setZoom( map.getZoom() - 1 );
					map.panTo( latLng );
				});

				menu.addSep();

				menu.addItem('Center Here', function(map, latLng){
					map.panTo( latLng );
				});

				// Add the menu to the object
				this.contextMenu = menu;
			}
			catch(e)
			{
				if (console)
					console.log(e);
			}
		}
	}

	// Set up only thinsg that effect the Main Map
	UserMap.prototype.main = function()
	{
		var self = this;

		// Make the user list scroll
		$('#punusermap #usermap_userlist .box').css('max-height', UserMap.defaults.height);

		// Admin 'save location' menu item
		if (this.options.saveLoc)
		{
			this.contextMenu.addSep();
			this.contextMenu.addItem(this.options.saveLoc[0], function()
			{
				var map = self.theMap,
					lat =  map.getCenter().lat(),
					lng =  map.getCenter().lng(),
					zoom = map.getZoom();

				window.location = self.options.saveLoc[1]+'/admin_loader.php?plugin=AP_Usermap_Settings.php&lat='+lat+'&lng='+lng+'&z='+zoom;
			});
		}

		this.infoWindowCache = [];

		var bounds = new g.LatLngBounds(),
			infoWindow = new g.InfoWindow();

		// close the infowindow when a few things happen
		$.each('click rightclick zoom_changed maptypeid_changed'.split(' '), function(i,name){
			g.event.addListener(self.theMap, name, function(){
				$('#usermap_userlist li').removeClass('isactive');
				infoWindow.close();
			});
		});

		// The userlist ul
		var ul = $(document.createElement('ul'));

		// grab the userlist json
		$.getJSON('usermap/list.php', function(data)
		{
			// check for errors
			if (data.error)
				return console.log(data.error);

			// look though the markers
			$.each(data, function(i,item)
			{
				var point = new g.LatLng(item.point[0],item.point[1]);

				// extend the bounds
				bounds.extend(point);

				// make the marker
				var marker = new g.Marker({
					map: self.theMap,
					position: point,
					icon: UserMap.makeIcon('icons/'+item.icon),
					shadow: UserMap.makeShadow(),
					title: item.name
				});

				// info window listener
				g.event.addListener(marker, 'click', function()
				{
					// if the info window hasnt been opened before
					if (self.infoWindowCache[item.id] === undefined)
					{
						// request the info window data
						$.getJSON('usermap/list.php?id='+item.id, function(data)
						{
							self.infoWindowCache[item.id] = '<div id="infoWindow">'+data[0].html+'</div>';
							infoWindow.setContent(self.infoWindowCache[item.id]);
							infoWindow.open(self.theMap, marker);
						});
					}
					else
					{
						infoWindow.setContent(self.infoWindowCache[item.id]);
						infoWindow.open(self.theMap, marker);
					}

					// add active state to the userlist entry
					$('#usermap_userlist li').removeClass('isactive')
						.filter('#u'+item.id).addClass('isactive');
				});

				// create the item html for the userlist
				var li = $(document.createElement('li'))
					.attr('id', 'u'+item.id)
					.appendTo(ul);

				$(document.createElement('a'))
					.attr('href', '#u'+item.id).html(item.name)
					.appendTo(li)

					// set the user icon as the link background
					.css('background-image', 'url(usermap/img/icons/'+item.icon+')')

					// Add some nice hover effects
					.hover(function() {
						$(this).parent().toggleClass('hover');
					})

					// Set the click event
					.click(function(){

						// trigger the click event on the marker
						g.event.trigger(marker,'click');

						// make sure the click doesnt take us anywhere
						return false;
					});
			});

			// if there is a list of users create the list
			if (ul.find('li').length)
				$('#usermap_userlist .inbox').html(ul);

			// set the map to fit the bounds?
			if (data.length != 0 && UserMap.defaults.fitmap)
				self.theMap.fitBounds(bounds);

			// if a id was provided, open its infowindow
			if (self.options.id)
				window.setTimeout(function() {$('#u'+self.options.id+' a').click();}, 200);

		});
	}

	// Set up only things that effect the Profile Map
	UserMap.prototype.profile = function()
	{
		var self = this;

		// Start the marker with the basics info
		this.marker = new g.Marker({
			icon: UserMap.makeIcon('marker.png',	[35,35],[11,33]),
			shadow: UserMap.makeShadow(),
			draggable: true,
		});

		// Attach some events to the marker
		g.event.addListener(this.marker, 'click', function()
		{
			// update the inputs
			$('#um_lat').val('');
			$('#um_lng').val('');

			// remove the marker from the map
			self.marker.setMap();
		});

		g.event.addListener(this.marker, 'dragend', function(event)
		{
			// update the inputs
			$('#um_lat').val(event.latLng.lat());
			$('#um_lng').val(event.latLng.lng());

			// pan to the new marker location
			window.setTimeout(function() {self.theMap.panTo(event.latLng);}, 50);
		});


		// If possible, set the marker location and attach it to the map
		if (this.options.center !== undefined)
		{
			this.marker.setMap(this.theMap);
			this.marker.setPosition(new g.LatLng(
				this.options.center[0],
				this.options.center[1]
			));
		}

		g.event.addListener(this.theMap, 'click', function(event)
		{
			// if the marker isnt on the map, put it there
			if (self.marker.getMap() === undefined)
				self.marker.setMap(self.theMap);

			// set the position
			self.marker.setPosition(event.latLng);

			// update the inputs
			$('#um_lat').val(event.latLng.lat());
			$('#um_lng').val(event.latLng.lng());

			// pan to the new marker location
			window.setTimeout(function() {self.theMap.panTo(event.latLng);}, 50);
		});

		// make the mouse wheel option checkbox live action
		$('#mouse_zoom').change(function(){
			console.log('it changed!');
			self.theMap.setOptions({scrollwheel:$(this).is(':checked')});
		});

		// add click event for the findLocation function
		$('#findLocation').click(function()
		{
			$.getScript('http://www.google.com/jsapi', function()
			{
				var client = google.loader.ClientLocation;

				if (client && client.latitude && client.longitude)
				{
					var client_latlng = new g.LatLng(client.latitude,client.longitude);

					// if the marker isnt on the map, put it there
					if (self.marker.getMap() === undefined)
						self.marker.setMap(self.theMap);

					// set the position
					self.marker.setPosition(client_latlng);
					self.theMap.setZoom(6);

					window.setTimeout(function() {self.theMap.panTo(client_latlng);}, 200);

					$('#um_lat').val(client_latlng.lat());
					$('#um_lng').val(client_latlng.lng());
				}
			});

		});
	}

	// Expose UserMap to the global object
	window.UserMap = UserMap;

	// Add extra functions to the UserMap object
	$.extend(UserMap, {

		/**
		 * Default options
		 */
		defaults: {
			center: [0,0],
			zoom: 0,
			mapType: 'ROADMAP'
		},

		/**
		 * Create an array of g.MarkerImage from one sprite image
		 */
		makeSprite: function(img,array)
		{
			// loop though each
			$.each(array, function(i, data)
			{
				// names
				var loc = data[0],
					size = data[1],
					anchor = data[2];

				// Reassign the current image data into a g.MarkerImage instance
				array[i] = new g.MarkerImage(
					'img/'+img,
					new g.Size(size[0],size[1]),
					new g.Point(loc[0],loc[1]),
					new g.Point(anchor[0],anchor[1])
				);
			});

			return array;
		},

		/**
		 * Create a single icon out of a image.
		 */
		makeIcon: function(img,size,anchor)
		{
			// defaults
			var img = img || 'icons/white.png',
				size = size || [20, 20],
				anchor = anchor || [4, 20];

			// return the 'MarkerImage'
			return new g.MarkerImage(
				'usermap/img/'+img,
				new g.Size(size[0],size[1]),
				new g.Point(0,0),
				new g.Point(anchor[0],anchor[1])
			);
		},

		/**
		 * Short hand to create the shadow for a marker.
		 */
		makeShadow: function()
		{
			return this.makeIcon('shadow.png', [36,24],[7,21]);
		}
	});
})();