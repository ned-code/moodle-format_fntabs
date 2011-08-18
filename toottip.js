// setup namespace
	YAHOO.namespace("fn.course.nav");

    //
    var handleSuccess = function(o) {

    	// if response is empty, do not create panel
    	if (!o.responseText) {
    		return false;
    	}
    	
    	// grab id number for this panel
	    var i = o.argument.arg_i;
	    			
		// set vars
		var name = 'fn-nav-' + i;
		
    	// grab tab and spot to anchor
    	var parent_tab_id = 'fnweeklynav'+i;
    	var parent_tab = document.getElementById(parent_tab_id);
    	
    	if (YAHOO.env.ua.ie && (document.documentMode == 8 && YAHOO.env.ua.ie == 7)) {
			YAHOO.util.Selector.attrAliases['class'] = 'class';
		}

		// grab anchor for rendering
    	var parent_anchor = document.getElementById('page');
		
		// Instantiate a Panel from script
		var top_y=75;
		YAHOO.fn.course.nav.panels[name] = new YAHOO.widget.Panel(name, { zIndex:12, underlay:'matte', visible:false, draggable:false, close:true , y:top_y} );
		YAHOO.fn.course.nav.panels[name].setHeader("Week "+i);
		
		if (o.responseText) {
			YAHOO.fn.course.nav.panels[name].setBody(o.responseText);
		}
		
		YAHOO.fn.course.nav.panels[name].setFooter("");
		
		// render
		YAHOO.fn.course.nav.panels[name].render(parent_anchor);
				
        // attach display event to parent tab
		YAHOO.util.Event.addListener(parent_tab, "mouseover", fn_tabs_show, YAHOO.fn.course.nav.panels[name], true);
		
		// close panel on mouseout
		panel = YAHOO.fn.course.nav.panels[name];
		YAHOO.util.Event.addListener(name, "mouseout", fn_panel_mouseout, panel);
      	
      	// suggestion from Mark Johnson
		//YAHOO.util.Event.on(name, 'mouseout', fn_panel_mouseout);
		
		// on hide, set availability to true
		YAHOO.fn.course.nav.panels[name].hideEvent.subscribe(fn_set_available_true);
		
		// shift panel to middle of screen
        fn_center_panel(YAHOO.fn.course.nav.panels[name], name);
	}
	
	function fn_center_panel(panel, id) {
	
        // find top of tabs
        var parent_anchor = document.getElementById('page');
        var tabs = YAHOO.util.Dom.getElementsByClassName('fnweeklynav', 'table');
        var tab = tabs[0];
        var tab_top = YAHOO.util.Dom.getY(tab);
        
        // panels are 175px high        
	    var top_y = tab_top-175;
	    
        var client_width = YAHOO.util.Dom.getClientWidth();
        var client_mid_x = Math.round(client_width/2);
        
	    // shift panel middle to middle of screen
        var panel_width = fn_calculate_panel_width(id);
        if (panel_width != 0) {
          var panel_mid_x = Math.round(panel_width/2);
          var panel_left = client_mid_x - panel_mid_x;
        }
        
        // Set the x position
        panel.cfg.setProperty("x", panel_left);
        // Set the y position
        panel.cfg.setProperty("y", top_y);
        // Set the x and y positions simultaneously
        //panel.cfg.setProperty("xy", [100,200]);
 
	}
	
	//
	var handleFailure = function(o) {

	}
		
	// 
	function fn_setup_tabs() {
	
		// setup vars
		var sUrl = 'http://dev.moodlefn.com/brianj-19/course/format/fn/ajax_view.php?id=2';
		
		// var content
		var content = 'Loading....';
		
		//
		YAHOO.fn.course.nav.panels = new Array();
		
		// iterate over tabs
		for (i=1; i<=10; i++) {

			// skip selected tab (this 'week')
			if (i == 1) {
				continue;
			}
			
			// set vars
			var name = 'fn-nav-' + i;

		    // load panel content
		    var request = YAHOO.util.Connect.asyncRequest('GET', sUrl + '&selected_week=' + i, { success:handleSuccess, failure:handleFailure, argument: { arg_i:i}, timeout:5000 }, null);
		
		}
		
		// this causes all sorts of problems with the current styles
		//document.body.className += ' yui-skin-sam';
	}

	function fn_tabs_show(e, obj) {

		if (YAHOO.fn.course.nav.available) {
			obj.cfg.setProperty('visible', true);
			YAHOO.fn.course.nav.available = false;
		}
	}

	// event is passed in
	// this should be panel element that experienced mouseout?
	function fn_panel_mouseout(e, panel) {
	
	    // this will stop this event from bubbling to elements up the DOM tree
	    YAHOO.util.Event.stopPropagation(e);
	   
	    // Determine if this is a real mouseout or internal to the panel
        // From http://www.quirksmode.org/js/events_mouse.html
       
        // target: place the mouse moved out of
        // relatedTarget: place the mouse moved to   
        var tg = YAHOO.util.Event.getTarget(e, true);
        
        // if tg is not panel
        // ??'s first child div, skip!?
        if (tg != this) { //YAHOO.util.Dom.getFirstChild(panel)) {
            return;
        }
        
        //
        if ((tg.nodeName != 'DIV') || !YAHOO.util.Dom.hasClass(this, 'yui-panel')) { // Did mouse move out of a non-div?
            return;  // spurious mouseout
        }
    	
    	// Is the relatedTarget (moved to place) a descendent of the panel?
        var reltg = YAHOO.util.Event.getRelatedTarget(e);
       
        // travel up document tree from reltg to see if it is a subelement of tg
        // in other words, moving *into* the panel        
        while (reltg != tg && reltg.nodeName != 'BODY') {
            reltg = reltg.parentNode;
            if (reltg == tg) return; // moving into panel
        }
        
        // if we made it this far, the proper mouseout took place
        panel.hide();
        
        //
        YAHOO.fn.course.nav.available = true;
	}

	// function to set available flag
	function fn_set_available_true(e, obj) {
        
        YAHOO.fn.course.nav.available = true;
    }
    
	function fn_tabs_hide(e, obj) {
    	
		YAHOO.fn.course.nav.available = true;
	}
	
	// calculate how wide panel will be based on classes present
	function fn_calculate_panel_width(name) {
	   
	   var columns = YAHOO.util.Dom.getElementsByClassName('fn_ajax_column_list', 'ul', name);

	   // these values grabbed from firebug
	   if(columns.length == 1) {
	       return 303;
	   } else if (columns.length == 2) {
	       return 627;
       } else if (columns.length == 3) {
	       return 916;
	   } else {
	       return 0;
	   }
	}
	

	YAHOO.fn.course.nav.available = true;
	//YAHOO.util.Event.onDOMReady(fn_setup_tabs);
	YAHOO.util.Event.addListener(window, "load", fn_setup_tabs);
	
	//]]>	
