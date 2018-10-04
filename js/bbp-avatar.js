jQuery(document).ready(function($){

  var file_frame, wp_media_post_id = wp.media.model.settings.post.id, set_to_post_id = bbpavatar_vars.post_id,
      avatarBtn = $('.stag-metabox-table .button');

  avatarBtn.css( {
    color: 'rgb(34, 34, 34)',
    'background-color': 'rgb(249, 249, 249)'
  } );

  avatarBtn.on('click', function( event ){

    event.preventDefault();

    var button = $(this);
    var id = button.attr('id').replace('_button', '');

    // If the media frame already exists, reopen it.
    if ( file_frame ) {
      // Set the post ID to what we want
      file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
      // Open frame
      file_frame.open();
      return;
    } else {
      // Set the wp.media post id so the uploader grabs the ID we want when initialised
      wp.media.model.settings.post.id = set_to_post_id;
    }

    // Create the media frame.
    file_frame = wp.media.frames.file_frame = wp.media({
      title: $( this ).data( 'uploader_title' ),
      library: {
          type: 'image',
        },
      button: {
        text: $( this ).data( 'uploader_button_text' ),
      },
      multiple: false  // Set to true to allow multiple files to be selected
    });

    // When an image is selected, run a callback.
    file_frame.on( 'select', function() {
      // We set multiple to false so only get one image from the uploader
      attachment = file_frame.state().get('selection').first().toJSON();

      // Do something with attachment.id and/or attachment.url here
      $( "#"+id ).val( attachment.id );

      if( typeof( attachment.sizes.thumbnail ) != "undefined" ) {
        url = attachment.sizes.thumbnail.url;
        width = attachment.sizes.thumbnail.width;
        height = attachment.sizes.thumbnail.height;
      } else {
        url = attachment.sizes.full.url;
        width = attachment.sizes.full.width;
        height = attachment.sizes.full.height;
      }

      $( "#"+id+"_thumbnail" ).val( url );

      $("#preview").html( '<img src="' + url + '" width="'+width+'px" height="'+height+'px">' );

      // Restore the main post ID
      wp.media.model.settings.post.id = wp_media_post_id;
    });

    // Finally, open the modal
    file_frame.open();
  });

  // Restore the main ID when the add media button is pressed
  $('a.add_media').on('click', function() {
    wp.media.model.settings.post.id = wp_media_post_id;
  });
});
