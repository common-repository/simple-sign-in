/**
 * jQuery brTip plugin
 * This jQuery plugin was inspired and based on various other plugins of tooltip, but this is better =)
 * @name jquery-brtip-1.1.js
 * @author Gabriel Sobrinho - gabriel.sobrinho@gmail.com
 * @version 1.1
 * @date March 04, 2008
 * @category jQuery plugin, User Interface
 * @copyright (c) 2008 Gabriel Sobrinho (gabriel.sobrinho@gmail.com)
 * @license CC Attribution 3.0 Unported - http://creativecommons.org/licenses/by/3.0/deed.en_US
 * @example Visit http://plugins.jquery.com/project/brTip for more informations about this jQuery plugin
 */

(function($)
{
	// Alias to jQuery Object.
	$.fn.brTip = function(opts)
	{
		// Merge default and user options.
		opts = $.extend({
			// Related to animation.
			fadeIn: '',
			fadeOut: '',
			
			// Related to speed.
			toShow: 100,
			toHide: 500,
			
			// Related to box.
			opacity: 0.8,
			top: -1,
			left: 15,
			title: 'Help',
			
			// Don't alter these variables in any way.
			box: null,
			delayToShow: null,
			delayToHide: null,
			txt: ''
		}, opts);
		
		// Clear the timeouts.
		function _clearTimes()
		{
			clearTimeout(opts.delayToShow);
			clearTimeout(opts.delayToHide);
		}
		
		// Create the box of brTip.
		function _create()
		{
			// Clear timeouts.
			_clearTimes();
			
			if (!opts.box)
			{
				// Create the box of brTip.
				opts.box = $('<div class="brTip-box"><div class="brTip-title">&nbsp;</div><div class="brTip-content">&nbsp;</div></div>').appendTo('body');
				opts.box.css('opacity', opts.opacity);	
			}
			
			// Set content.
			opts.box.find('div.brTip-title').html(opts.title);
			opts.box.find('div.brTip-content').html(opts.txt);
			
			// Delay to show.
			opts.delayToShow = setTimeout(function()
			{
				opts.box.fadeIn(opts.fadeIn);
			}, opts.toShow);
			
		}
		
		// Hide the box of brTip.
		function _hide()
		{
			opts.delayToHide = setTimeout(function()
			{
				opts.box.fadeOut(opts.fadeOut);
			}, opts.toHide);
		}
		
		// Set the position of the box of brTip.
		function _setPos(top, left)
		{
			if (opts.box)
			{
				opts.box.css({
					top: top + opts.top,
					left: left + opts.left
				});
			}
			else
			{
				// In no move mouse, check again.
				setTimeout(function()
				{
					_setPos(top, left);
				}, 100);
			}
		}
		
		return this.each(function()
		{
			// Self is alias to jQuery Object of actual element.
			var self = $(this);
			
			// Set events.
			self
				.mouseover(function()
				{
					// Set content.
					opts.txt = self.attr('title');
					self.attr('title', '');
					
					// Create the box.
					_create();
				})
				.mouseout(function()
				{
					// Restore content.
					self.attr('title', opts.txt);
					opts.txt = '';
					
					// Hide the box.
					_hide();
				})
				.mousemove(function(e)
				{
					_setPos(e.pageY, e.pageX);
				})
				.focus(function()
				{
					self.trigger('mouseover');
					
					// Set the pos based on element pos.
					var pos = self.offset();
					_setPos(pos.top + (self.width() / 2), pos.left + (self.height() / 2));
				})
				.blur(function()
				{
					self.trigger('mouseout');
				})
		});
	};
}(jQuery));