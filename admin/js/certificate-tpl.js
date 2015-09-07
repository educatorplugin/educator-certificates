(function($) {
	'use strict';

	/**
	 * Set certificate template background image.
	 */
	function setCertificateTplImage() {
		var attachment = featuredImage.state().get('selection').first().toJSON();
		var imageContainer = document.querySelector('#edr-crt-template > .image > div');
		var image = imageContainer.querySelector('img');

		if (!image) {
			image = document.createElement('img');
			imageContainer.appendChild(image);
		}

		var orientation = $('select.change-orientation');

		if (attachment.width > attachment.height) {
			orientation.val('L');
		} else {
			orientation.val('P');
		}

		orientation.trigger('change');

		image.src = attachment.url;
	}

	/**
	 * Remove certificate template background image.
	 */
	function removeCertificateTplImage() {
		var image = document.querySelector('#edr-crt-template > .image > div > img');

		if (image) {
			image.parentNode.removeChild(image);
		}
	}

	/**
	 * Model: TextBlock.
	 */
	var TextBlock = Backbone.Model.extend({
		defaults: {
			name: '',
			content: '',
			x: 0,
			y: 0,
			width: 200,
			height: 40,
			font_size: 12,
			align: 'L'
		}
	});

	/**
	 * Collection: TextBlocks.
	 */
	var TextBlocks = Backbone.Collection.extend({
		model: TextBlock
	});

	/**
	 * View: TextBlockView.
	 */
	var TextBlockView = Backbone.View.extend({
		tagName: 'li',
		template: _.template($('#edr-text-block-tpl').html()),
		boxEl: null,
		className: 'edr-toggle',
		events: {
			'keyup .field-name': 'updateName',
			'click .header': 'toggle',
			'click .remove': 'removeBlock'
		},

		/**
		 * Initialize.
		 */
		initialize: function() {
			this.listenTo(this.model, 'change:name', this.updateBoxName);
			this.listenTo(this.model, 'change', this.onAttrChange);
			this.listenTo(this.model, 'destroy', this.remove);
		},

		/**
		 * Render this view.
		 */
		render: function() {
			if (this.model.get('name') === '') {
				this.$el.addClass('open');
			}

			// Render the model.
			this.$el.html(this.template(this.model.toJSON()));

			// Set the align value.
			this.$el.find('.field-align').val(this.model.get('align'));

			this.drawBox();
		},

		/**
		 * Render this block on the certificate background.
		 */
		drawBox: function() {
			var that = this;

			var onMouseEnter = function() {
				that.toggleFeedback('on');
			};

			var onMouseLeave = function() {
				that.toggleFeedback('off');
			};

			this.boxEl = $('<div><span class="name"></span><span class="feedback"></span></div>');
			this.boxEl.addClass('box');
			this.boxEl.attr('style', 'position: absolute;');

			this.boxEl.hover(onMouseEnter, onMouseLeave);
			
			this.boxEl.draggable({
				containment: 'parent',
				stack: '.box',
				start: function() {
					that.boxEl.off('mouseenter', onMouseEnter);
					that.boxEl.off('mouseleave', onMouseLeave);
				},
				stop: function(event, ui) {
					that.model.set('x', ui.position.left);
					that.model.set('y', ui.position.top);
					that.boxEl.hover(onMouseEnter, onMouseLeave);
				},
				drag: function(event, ui) {
					that.updateFeedback(ui.position.left, ui.position.top,
						that.model.attributes.width, that.model.attributes.height);
				}
			});

			this.boxEl.resizable({
				containment: 'parent',
				start: function() {
					that.boxEl.off('mouseenter', onMouseEnter);
					that.boxEl.off('mouseleave', onMouseLeave);
				},
				stop: function(event, ui) {
					that.model.set('width', ui.size.width + 4);
					that.model.set('height', ui.size.height + 4);
					that.boxEl.hover(onMouseEnter, onMouseLeave);
				},
				resize: function(event, ui) {
					that.updateFeedback(that.model.attributes.x, that.model.attributes.y,
						ui.size.width + 4, ui.size.height + 4);
				}
			});
			
			$('#edr-crt-template > .image > div').append(this.boxEl);

			// Set initial dimensions.
			this.boxEl.css('left', this.model.attributes.x + 'px');
			this.boxEl.css('top', this.model.attributes.y + 'px');
			this.boxEl.css('width', this.model.attributes.width + 'px');
			this.boxEl.css('height', this.model.attributes.height + 'px');

			this.updateBoxName();
			this.updateFeedback(this.model.get('x'), this.model.get('y'),
				this.model.get('width'), this.model.get('height'));
		},

		/**
		 * Update block name.
		 *
		 * @param {Object} e
		 */
		updateName: function(e) {
			this.model.set('name', e.target.value);
		},

		/**
		 * Update the block name in the UI.
		 */
		updateBoxName: function() {
			this.boxEl.find('> .name').text(this.model.get('name'));
			this.$el.find('> .header > .text').text(this.model.get('name'));
		},

		/**
		 * Render the block attributes when they change.
		 *
		 * @param {TextBlock} textBlock
		 */
		onAttrChange: function(textBlock) {
			this.$el.find('.field-x').val(textBlock.get('x'));
			this.$el.find('.field-y').val(textBlock.get('y'));
			this.$el.find('.field-width').val(textBlock.get('width'));
			this.$el.find('.field-height').val(textBlock.get('height'));

			this.updateFeedback(textBlock.get('x'), textBlock.get('y'),
				textBlock.get('width'), textBlock.get('height'));
		},

		/**
		 * Update the visual feedback string.
		 *
		 * @param {number} x
		 * @param {number} y
		 * @param {number} width
		 * @param {number} height
		 */
		updateFeedback: function(x, y, width, height) {
			this.boxEl.find('> .feedback').text('x' + x + ' y' + y + ' w' + width + ' h' + height);
		},

		/**
		 * Show/hide the feedback string.
		 *
		 * @param {string} state
		 */
		toggleFeedback: function(state) {
			var feedback = this.boxEl.find('> .feedback');

			if (state == 'on') {
				feedback.css('opacity', 1);
			} else {
				feedback.css('opacity', 0);
			}
		},

		/**
		 * Toggle the edit block state.
		 *
		 * @param {Object} e
		 */
		toggle: function(e) {
			e.preventDefault();

			this.$el.toggleClass('open');
		},

		/**
		 * Remove block from UI.
		 *
		 * @param {Object} e
		 */
		removeBlock: function(e) {
			this.boxEl.draggable('destroy');
			this.boxEl.resizable('destroy');
			this.boxEl.remove();
			this.model.destroy();

			e.preventDefault();
		}
	});

	/**
	 * View: MainView.
	 */
	var MainView = Backbone.View.extend({
		el: $('#edr-crt-template'),
		events: {
			'click .add-block': 'addBlock',
			'change .change-orientation': 'changeOrientation'
		},

		/**
		 * Initialize.
		 */
		initialize: function() {
			this.collection = new TextBlocks();
			this.listenTo(this.collection, 'add', this.renderBlock);

			// Add and render existing blocks.
			this.collection.set(edrCertBlocks);
		},

		/**
		 * Render a block.
		 *
		 * @param {TextBlock} textBlock
		 */
		renderBlock: function(textBlock) {
			var view = new TextBlockView({
				model: textBlock
			});

			view.render();

			this.$el.find('.blocks').append(view.el);
		},

		/**
		 * Add new block.
		 *
		 * @param {Object} e
		 */
		addBlock: function(e) {
			var textBlock = new TextBlock();

			this.renderBlock(textBlock);

			e.preventDefault();
		},

		/**
		 * Set template orientation (Portrait or Landscape).
		 *
		 * @param {string} orientation
		 */
		setOrientation: function(orientation) {
			if (orientation === 'P') {
				this.$el.removeClass('landscape').addClass('portrait');
			} else {
				this.$el.removeClass('portrait').addClass('landscape');
			}
		},

		/**
		 * Change orientation based on the value of the orientation select box.
		 *
		 * @param {Object} e
		 */
		changeOrientation: function(e) {
			this.setOrientation(e.target.value);
		}
	});

	// Initialize the main view.
	var mainView = new MainView();

	mainView.render();

	// Set the certificate image background in the UI when
	// the featured image is added/changed.
	var featuredImage = wp.media.featuredImage.frame();

	featuredImage.on( 'select', function() {
		setCertificateTplImage(featuredImage);
	});

	// Remove the certificate background image in the UI when
	// the featured image is removed.
	var removePostThumb = document.getElementById('remove-post-thumbnail');

	document.body.addEventListener( 'click', function(e) {
		if (e.target.getAttribute('id') === 'remove-post-thumbnail') {
			removeCertificateTplImage();
		}
	}, true );
})(jQuery);
