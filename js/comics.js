function filter_allcomics()
{
  $('#comic_list .reg').show();
  $('#comic_list .added').show();
  $('#comic_list .new').show();
}

function filter_newcomics()
{
  $('#comic_list .reg').hide();
  $('#comic_list .added').hide();
  $('#comic_list .new').show();
}

function filter_addedcomics()
{
  $('#comic_list .reg').hide();
  $('#comic_list .added').show();
}

function filter_unaddedcomics()
{
  $('#comic_list .reg').show();
  $('#comic_list .added').hide();
}

var curComic;
var numUnread;
var onlyUnread = 0;

function loadComic(obj)
{
  if (curComic == obj.id)
    return;

  $(obj).addClass('working');
  $('#'+curComic).removeClass('active');
  curComic = obj.id;

  $.ajax('/get.php?id='+obj.id.substring(9)+'&h='+($('#comic_info').height()-15)+'&r=comic_info')
    .done(function( response ) {
      $('#comic_info').html(response);
      $(obj).removeClass('working');
      $(obj).addClass('active');
    });
}

function load(id, t, h, respDiv)
{
  $.ajax('/get.php?id='+id+'&t='+t+'&h='+h+'&r='+respDiv)
    .done(function( response ) {
      $('#'+respDiv).html(response);
    });
}

function toggleSubscribe(obj)
{
  var state = ($(obj).hasClass("added") ? 0 : 1);
  var id = obj.id.substring(9);
  var count = $('#num_subscribed').html();

  $(obj).addClass('working');

 // hide the unread icon if it's there.
  if (state == 0)
    $('#unread_li_'+id).hide();

  $.ajax('/toggle.php?id='+obj.id.substring(9)+'&s='+state)
    .done( function( response ) {
      $(obj).removeClass('working'); 
      if (state == 1)
      {
        $(obj).addClass("added");
        $(obj).removeClass("reg");
        $('#num_subscribed').html(parseInt(count)+1);
      }
      else
      {
        $(obj).removeClass("added");
        $(obj).addClass("reg");
        $('#num_subscribed').html(parseInt(count)-1);
      }
    });
}

function DeleteComic(id, t, h, respDiv)
{
  if (confirm('Are you sure you want to delete this comic?'))
  {
   // delete the comic
    $.ajax({url: '/delete.php?id='+t, async: false});

   // and load the rest.
    $.ajax('/get.php?id='+id+'&h='+h+'&r='+respDiv)
      .done(function( response ) {
        $('#'+respDiv).html(response);
      });
  }
}

function RefreshComic(id, h, respDiv)
{
  if ($('#refresh_'+id).hasClass('refresh_suspended'))
  {
    alert('Cannot refresh suspended comic');
    return;
  }

  $('#refresh_'+id).addClass('refreshing');

 // refresh the comic
  $.ajax('/refresh.php?id='+id)
    .done(function( response ) {
      var numNew = parseInt(response);

     // if response was greater than zero, there are new comics, so retool the menu (if it's there) and reload the comic pane.
      if (numNew > 0)
      {
        if ($('#comic_li_'+id).length > 0)
        {
          if (!($('#recent_li_'+id).is(':visible')))
          {
            $('#recent_li_'+id).show();
            $('#num_updated').html(parseInt($('#num_updated').html())+1);
          }

          if ($('#comic_li_'+id).hasClass('added'))
          {
            if (!($('#unread_li_'+id).is(':visible')))
            {
              $('#unread_li_'+id).show();
              $('.comic_info_mark_all_read').show();
              numUnread++;
              $('#num_unread').html(numUnread);
            }
          }
        }
        else
        {
          $('.comic_info_mark_all_read').show();
          numUnread++;
        }
        
        $.ajax('/get.php?id='+id+'&h='+h+'&r='+respDiv)
          .done(function( response ) {
            $('#'+respDiv).html(response);
          });
      }
      
     // otherwise revert the refresh animation back to a non-spinning cursor.
      else
        $('#refresh_'+id).removeClass('refreshing');
    });
}


function MarkRead(id, time)
{
  if (id == 'all' && !confirm('This will mark all unread comics as read.  Are you sure?'))
    return;
  
 // mark the comic as read in the DB
  $.ajax('/mark_read.php?id='+id+'&t='+time)
    .done(function(response) {
     // if we marked all as read, then reload the page, otherwise hide some divs
      if (id == 'all')
        window.location.reload();
      else
      {
       // reduce the number of unread comics by one.  if this hits zero, hide the "mark read" link.
        numUnread--;
        if (numUnread == 0)
          $('.comic_info_mark_all_read').hide();

       // if we're in "only unread" read mode, then hide the whole comic div with a scrollup animation.
        if (onlyUnread == 1)
        {
          $('#comic_'+id).slideUp();
          if (numUnread == 0)
            $('#no_unread').show();
        }
        else
        {
         // this one might not be there...
          if ($('#new_icon_'+id).length > 0)
            $('#new_icon_'+id).hide();

          $('#mark_read_'+id).hide();
          $('#numUnread_'+id).hide();

         // nor this one
          if ($('#unread_li_'+id).length > 0)
            $('#unread_li_'+id).hide();

          if ($('#num_unread').length > 0)
            $('#num_unread').html(numUnread);
        }
      }
    });
}

// suspend/unsuspend a comic
function SuspendComic(id, action, h, respDiv)
{
 // mark the comic as read in the DB, and update interface.
  $.ajax('/suspend.php?id='+id+'&action='+action)
    .done(function(response) {
     // based on the action, update icons.  first we do "unsuspend" action.
      if (action == 1)
      {
        if ($('#comic_li_'+id).length > 0)
        {
          $('#suspend_li_'+id).hide();
          if($('#recent_li_'+id).html() == '1')
          {
            $('#recent_li_'+id).show().html("");
            $('#num_updated').html(parseInt($('#num_updated').html())+1);
          }
        }
      }
      else
      {
        if ($('#comic_li_'+id).length > 0)
        {
          if ($('#recent_li_'+id).is(':visible'))
          {
            $('#recent_li_'+id).hide().html(1);
            $('#num_updated').html(parseInt($('#num_updated').html())-1);
          }

          $('#suspend_li_'+id).show();
        }
      }

      $.ajax('/get.php?id='+id+'&h='+h+'&r='+respDiv)
        .done(function( response ) {
          $('#'+respDiv).html(response);
        });
    });
}
