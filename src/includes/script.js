jQuery(document).ready(function ($) {
  $('.autotagwp-button').on('click', function () {
    var post_id = $(this).data('postid');
    var countries = $('input[name="autotagwp-countries"]').prop('checked');
    var cities = $('input[name="autotagwp-cities"]').prop('checked');
    var people = $('input[name="autotagwp-people"]').prop('checked');
    var ent = $('input[name="autotagwp-ent"]').prop('checked');
    var data = {
      action: 'autotagwp_autotag_post',
      post_id: post_id,
      countries: countries,
      cities: cities,
      people: people,
      enterprise: ent,
      _wpnonce: $(this).data('nonceid')
    };
    $.post(ajaxurl, data, function (response) {
      if (response.success) {
        alert(response.data);
      } else {
        alert(response.data);
      }
    });
  });
});
