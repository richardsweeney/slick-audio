jQuery(function ($) {

	var sa = {
		thisInput: '',
		urlInput: $('.sa-input-url'),
		titleInput: $('.sa-input-title'),
		list: $('.mp3-list').sortable({
			stop: function(){
				sa.sortMp3s();
			}
		}),
		sortMp3s: function () {
			$.each($('.mp3-list > li'), function (i) {
				var	id = 'sa-' + i,
						titleName = 'mp3[' + i + '][title]',
						urlName = 'mp3[' + i + '][url]';
				$(this)
					.attr('id', id)
					.find('span.number').text(i + 1)
					.find('input.sa-input-title').attr('name', titleName).end()
					.find('input.sa-input-url').attr('name', urlName).end()
					.find('a.sa-delete').attr('data-id', i);
			});
		},
		mp3check: function(input) {
			var	url = input.val(),
					regex = /\.mp3$/,
					$message = '<span class="sa-error-message">Are you sure this is an mp3?</span>';
			input.closest('li').find('.sa-error-message').remove();
			if(regex.test(url)) {
				input.removeClass('sa-error');
			} else {
				input.addClass('sa-error').closest('li').append($message);
			}
		}
	};

	$.each(sa.urlInput, function() {
		if(sa.urlInput.length > 1) {
			sa.mp3check($(this));
		}
	});

	$('.sa-add-new-field').click(function (e) {
		var numFields = $('.mp3-list li').length,
				$newField = $('.first-mp3').clone(),
				titleName = 'mp3[' + numFields + '][title]',
				urlName = 'mp3[' + numFields + '][url]';

		$newField
			.removeClass('first-mp3').attr('id', 'sa-' + numFields)
			.find('input').val('').end()
			.find('span.number').text(numFields + 1).end()
			.find('input.sa-input-title').attr('name', titleName).end()
			.find('input.sa-input-url').attr('name', urlName).end()
			.find('.sa-delete').attr('data-id', numFields).end();

		sa.list.append($newField);
	});

	sa.list.on('click', '.sa-delete', function () {
		var id = +$(this).attr('data-id');
		$('li#sa-' + id).animate({
			height: 0,
			opacity: 0
		}, 300, function () {
			$(this).remove();
			sa.sortMp3s();
		});
		return false;
	}).on('change', '.sa-input-url', function () {
		if ($('#submit-error').length) {
			$('#submit-error').remove();
		}
		sa.mp3check($(this));
	}).on('keypress', '.sa-input-title', function () {
		if ($('#submit-error').length) {
			$('#submit-error').remove();
		}
	}).on('click', '.sa-add-mp3', function (e) {
		sa.thisInput = $(this).prev();
		tb_show('', 'media-upload.php?type=audio&amp;TB_iframe=true');
    var tbframe_interval = setInterval(function () {
       $('#TB_iframeContent').contents().find('form#filter').hide().end().find('#tab-type_url').hide();
    }, 10);
 		e.preventDefault();
	});


	window.send_to_editor = function(html) {
 		sa.thisInput.val($(html).attr('href')).trigger('change');
		tb_remove();
	}

	$('.submit-container input').click(function () {
		var empty = false;
		if ($('#submit-error').length) {
			$('#submit-error').remove();
		}

		$.each(sa.titleInput, function () {
			$(this).removeClass('sa-error');
			if ($(this).val() == '') {
				empty = true;
				$(this).addClass('sa-error');
			}
		});
		$.each(sa.urlInput, function () {
			$(this).removeClass('sa-error');
			if ($(this).val() == '') {
				empty = true;
				$(this).addClass('sa-error');
			}
		});

		if (empty === true) {
			$message = '<span id="submit-error" class="sa-error-message">Make sure you\'ve provided a title and a URL for each track</span>';
			$('p.submit-container').before($message);
			return false;
		}
	});

});