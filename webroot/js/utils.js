function fadeOutNotification($notif, effect, delay, duration)
{
  if ( undef(effect) ) {
      effect = 'blind';
  }
  
  if ( undef(delay) ) {
      delay = 10000;
  }
  
  if ( undef(duration) ) {
      duration = 5000;
  }
  $notif.click(function() { $notif.fadeOut(); });
  setTimeout(function() {
    $notif.hide(effect, {duration: duration, complete: function() { $notif.remove() }});
  }, delay);
}

function undef(x)
{
    return typeof(x) === 'undefined';
}

function inArray(x, y)
{
    return $.inArray(x, y) >= 0;
}

/*
function notify(typeNotif, text, timeout)
{
    noty({
    text: undef(text) ? 'Avertissement !' : text,
    type : undef(typeNotif) ? 'warning' : typeNotif,
    animation: {
        open: {height: 'toggle'},
        close: {height: 'toggle'},
        easing: 'swing',
        speed: 500 // opening & closing animation speed
    },
    timeout : undef(timeout) ? 5000 : timeout
})
}
*/

function getTranslation(msgId)
{
  var url = [$('.lang-link').data('transUrlBase'), msgId].join('/');
  trans = $('.lang-link').data('translations');
  var text = trans[$('body').data('lang') + ":" + msgId];
  if ( undef(text) ) {
    silentAjax = true;
    $.ajax(
      {url: url,
       success: function(res) {
         res = JSON.parse(res);
         text = res.text;
         trans[$('body').data('lang') + ":" + res.msgid] = text;
         $('.lang-link').data('translations', trans);
         silentAjax = false;
       }
      }
    );
  }
    
  return text;
}

function showAjaxSpinner(show)
  {
    if ( undef(show) ) {
      show = true;
    }
    
    if ( show ) {
      $('#loading-ajax').show();
    } else {
      $('#loading-ajax').hide();
    }
  }
  
  function setAjaxSpinnerText(text)
  {
    if ( undef(text) || text === false ) {
      text = "";
    }
    $("#loading-ajax .loading-info").text(text);
  }
  
  function resetSelection(code)
  {
    $('.classi-items tr.classi-item').remove();
    if ( undef(code) ) {
      $('[name=classi_code]').removeClass('x').val("").focus();
    }
    if(currentReq && currentReq.readyState != 4){
      currentReq.abort();
    }
  }
  
  function initMultiselect($rootElem)
  {
    var initSel = function($sel) {
      $sel.on('chosen:ready', function(evt, params) {
        $('div.my-chosen').show();
      });
      $sel.chosen({
        no_results_text: $sel.data('noResultsText'),
        search_contains: true
      }).on('change', agentSubsetChanged);
    }
    
    if ( $rootElem.is('select') ) {
      if ( $rootElem.data('initSelected') ) {
        var initAgents = $rootElem.data('initSelected').split(' ');
        $rootElem.data('selected', initAgents);
        $rootElem.find('option').filter(function() { return $.inArray($(this).val(), initAgents) >= 0; }).prop('selected', true);
        if ( initAgents.length > 0 ) {
          $drawButton = $('[name=draw-button]');
          $drawButton.prop('disabled', false);
          setTimeout(function() { $drawButton.click(); }, 50);
        }
      }
      initSel($rootElem);
    } else {
      $rootElem.find('.chosen-select').each(initSel);
    }
  }

  function agentSubsetChanged(evt, params)
  {
    $sel = $(evt.target);
    var selectedAgents = $sel.find('option:selected').map(function() { return $(this).val(); }).get();
    $('[name=draw-button]').prop('disabled', selectedAgents.length == 0)
    $sel.data('selected', selectedAgents);
    $cc = $sel.next().find('.chosen-choices')
    var className = "chosen-deselect-all";
    if ( selectedAgents.length >= 2 && $cc.find('.' + className).length == 0 ) {
      $cc.append("<li class='" + className + "'><a href='#' onclick='unselectAllChosen(event)'>" + $sel.data("unselectAllText") + "</a></li>");
    } else
    if ( selectedAgents.length < 2 ) {
      $cc.find('.' + className).remove();
    }
  }
  
  function unselectAllChosen(event)
  {
    event.preventDefault();
    $sel = $(event.target).closest('div.chosen-container').prev();
    $sel.find('option:selected').prop('selected', false);
    $sel.trigger('chosen:updated');
    setTimeout(function() {
      $sel.trigger('chosen:close');
      $sel.change();
    }, 80);
  }
  
  function drawTree(e) {
    e.preventDefault();
    $sel = $('[name="choice_agents[]"]');
    var agents = $sel.data('selected').toString().split(',');
    $.ajax({
      method: 'GET',
      url: $sel.data('urlBuildTreeConfig'),
      data: $.map(agents, function(agentId) { return "agents[]="+agentId; }).join('&'),
      success: function(data) {
        var res = JSON.parse(data);
        if ( res.ok ) {
          $elem = $(res.rootElem);
          $elem.html("");
          $treeLegElem = $('#tree-legend');
          var treeConfigFilePath = res.treeConfigFilename; //
          $.getScript(treeConfigFilePath, function( data, textStatus, jqxhr ) {
            new Treant(chart_config);
            if ( $treeLegElem.html().length == 0 ) {
              $.ajax({
                url: $sel.data('urlGetTreeLegend'),
                dataType: 'html',
                success: function(legendHtml) {
                  $treeLegElem.html(legendHtml);
                }
              })
            }
          });
          for ( var idc in res.chains ) {
            $.ajax({
                url: $sel.data('urlGetJobCounts'),
                data: res.chains[idc].map(function(x) { return "chain[]=" + x}).join("&"),
                dataType: 'json',
                success: function(countsJson) {
                  console.log(countsJson)
                }
              })
          }
        }
      }
    });
  }