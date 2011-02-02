(function() {

	tinymce.create('tinymce.plugins.ftButtons', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			if ( typeof TSButtonClick == 'undefined' ) return;
			ed.addButton('ftbutton', {
				title : 'flamingtext',
				image : url + '/../../buttons/flamingtext.png',
				onclick : function() {
					TSButtonClick('flaming-text');
				}
			});

		},

		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) { 
			switch (n) {
		        	case 'ftlistbox':
       		         		var mlb = cm.createListBox('ftlistbox', {
       		              			title : 'FlamingText list box',
       		             			onselect : function(v) {
       	         	         			tinyMCE.activeEditor.windowManager.alert('Value selected:' + v);
       		        	      		}
                			});

                			// Add some values to the list box
                			mlb.add('Some item 1', 'val1');
                			mlb.add('some item 2', 'val2');
       			         	mlb.add('some item 3', 'val3');

			                // Return the new listbox instance
			                return mlb;

			        case 'ftsplitbutton':
			                var c = cm.createSplitButton('ftsplitbutton', {
						title : $splitbuttonname, 
						image : location.protocol+'//'+location.hostname+'/wp-content/plugins/flamingtext/buttons/flamingtext.png',
                    				onclick : function() {
                    					if ( typeof TSButtonClick == 'undefined' ) return;
			    				else TSButtonClick('flaming-text');
                    				}
                			});

                			c.onRenderMenu.add(function(c, m) {
                    				m.add({title : $presetslistname, 'class' : 'mceMenuItemTitle'}).setDisabled(1);

						for (i=0; i<$presets.length; i+=2) {
							var $name=$presets[i];
							 m.add({id : $name, title : $name, onclick : function(ev) {
                                          			if (!tinymce.isIE) spButtonClick(ev.textContent);
								else spButtonClick(ev.innerText);
							}});
						}
/*
						m.addSeparator();
						m.add({title : 'Revert to text', onclick : function() {
							RevertImage();
						}});
*/
						m.addSeparator();
						m.add({title : $customlogoname, onclick : function() {
							if ( typeof TSButtonClick == 'undefined' ) return;
                                                        else TSButtonClick('flaming-text');
						}});
					});	
                		// Return the new splitbutton instance
                		return c;
        		}
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : "Flaming text logo creater",
				author : 'Raymond Zhao',
				authorurl : 'http://www.flamingtext.com/',
				infourl : 'http://wordpress.libero.flamingtext.com/',
				version : "0.3"
			};
		}
	});
	loadPresets();

	// Register plugin
	tinymce.PluginManager.add('flamingtext', tinymce.plugins.ftButtons); //add buttton
})();
