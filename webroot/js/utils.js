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

var currentReq = null;
var refineReqs = {};
function showClassificationItems(evt)
{
  /* Reject invalid characters */
  /*if ( $(evt.target).is('input') ) {
    var extraChar =  String.fromCharCode(evt.keyCode);
    if ( evt.keyCode >= 65 && evt.keyCode <= 90 ) {
      // upper-case letter
    } else
    if (evt.keyCode >= 97 && evt.keyCode <= 122) {
      // lower-case letter
    } else
    if ( evt.keyCode >= 48 && evt.keyCode <= 57 ) {
      // digit
    } else
    if ( $.inArray(evt.keyCode, [32,45,46]) >= 0 ) {
      // ' ', '-', '.'
    } else
    if ( evt.keyCode == 8 ) {
      extraChar = '';
    }
    else {
      evt.preventDefault();
      return;
    }
  }
  */
  $sel = $('[name=classi_standard]');
  $selOpt = $sel.find('option:selected');
  var selVal = $selOpt.val();
  $code = $('#classi-code');
  var usingSafari = window.navigator.userAgent.indexOf("Safari") >= 0;
  var codeHint = (!usingSafari && evt instanceof ClipboardEvent) ? evt.clipboardData.getData('text') : $code.val();
  /*if ( extraChar.length > 0 ) {
    codeHint += extraChar;
  } else {
    codeHint = codeHint.slice(0,-1);
  }*/
  if ( codeHint.length >= 4 ) {
    var selGroup = $selOpt.closest('optgroup').attr('label').substr(0,5).toLowerCase();
    var yearIn = $('[name=period_start]').val();
    var yearOut = $('[name=period_end]').val();
    var url = [$code.data('urlBase'), selGroup, selVal, yearIn, yearOut, codeHint].join('/');
    
    if(currentReq && currentReq.readyState != 4){
      currentReq.abort();
    }
    currentReq = $.ajax({
      url: url,
      success: function(tableHtml) {
        $('.classi-items')[0].innerHTML = $(tableHtml).html();
      }
    });
  } else {
    resetSelection(codeHint);
  }
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
  
  function refineExpoProfile(evt)
  {
    evt.preventDefault();
    $targ = $(evt.target);
    $expoDiv = $targ.closest('div.expo-profile');
    if ( $targ.is('select') ) {
      $yearSlider = $expoDiv.find('.year-in');
    } else {
      $yearSlider = $targ;
    }
    
    $otherYearSlider = $expoDiv.find('.year-slider').not($yearSlider);
    $yearInSlider = $expoDiv.find('.year-in');
    $yearOutSlider = $expoDiv.find('.year-out');
    var cde = $expoDiv.attr('id');
    var classi = $('[name=classi_standard]').val();
    
    var doRefine = false;
    var yearIn = "-", yearOut = "-";
    if ( $yearSlider.val().length == 4 ) {
      if ( parseInt($yearInSlider.val()) > parseInt($yearOutSlider.val()) ) {
        $yearSlider.val($otherYearSlider.val());
      }
      yearIn = $yearInSlider.val();
      yearOut = $yearOutSlider.val();
      doRefine = true;
    } else
    if ( $yearSlider.val().length == 0 ) {
      if ( $otherYearSlider.hasClass('year-in') ) {
        yearIn = $yearInSlider.val();
      } else {
        yearOut = $yearOutSlider.val();
      }
      doRefine = true;
    }
    
    if ( doRefine ) {
      var url = [$expoDiv.data('url'), yearIn, yearOut].join('/');
      $expoDiv.attr('data-last-url', url);
      if(refineReqs[cde] && refineReqs[cde].readyState != 4) {
        refineReqs[cde].abort();
      }
    
      refineReqs[cde] = $.ajax({
          url: url,
          data: $expoDiv.find('[name="refine_agents[]"]').serialize(),
          success: function(expoHtml) {
            updateCounter($expoDiv.find('span.n-jobs-male'), $(expoHtml).data('nJobsMale'));
            updateCounter($expoDiv.find('span.n-jobs-female'), $(expoHtml).data('nJobsFemale'));
            updateCounter($expoDiv.find('span.n-agents'), $(expoHtml).data('nAgents'));
            $expoDiv.find('tbody').html($(expoHtml).find('tbody'));
            adjustTbodyHeight($expoDiv.find('table'));
          },
          error: function(jqXHR, textStatus, errorThrown) {
            $icon = $("svg[data-code='" + dacode + "']");
            $icon.toggleClass('show');
          }
        });
    }
  }
  
  function showExpoData(code)
  {
    $icon = $("svg[data-code='" + code + "']");
    $icon.toggleClass('show');
    dacode = $icon.data('code');
    var periodStart = $('[name=period_start]').val();
    var periodEnd = $('[name=period_end]').val();
    var stnd = $('#classi-standard').val();
    if ( $icon.hasClass('show') ) {
      var url = [$('.classi-items').data('urlBase'), stnd, code, periodStart, periodEnd].join('/');

      if ( $icon.closest('tr').find('div.expo-profile').length ) {
        revealExposureTable($icon, code);
      } else {
        $.ajax({
          type: 'GET',
          url: url,
          success: function(expoHtml) {
            $td = $(".classi-items tr[data-code='" + code + "'] td.title");
            if ( $td.length ) {
              $td.append($(expoHtml));
              initMultiselect($td);
            }
            revealExposureTable($icon, code);
          },
          error: function(jqXHR, textStatus, errorThrown) {
            $icon = $("svg[data-code='" + dacode + "']");
            $icon.toggleClass('show');
          }
        });
      }
    } else {
      hideExposureTable($icon, code);
    }
  }
  
  function revealExposureTable($i, cde)
  {
    $('#' + $i.prop('id')).toggleClass('fa-plus-square');
    $('#' + $i.prop('id')).toggleClass('fa-minus-square');
    $div = $('#' + cde);
    $div.slideDown(500, function() {
      /* Hack to ensure that first 5 rows of exposure profile are shown */
      $tbl = $(this).find('table');
      adjustTbodyHeight($tbl);
    });
    $i.attr('title', getTranslation('hide_data'));
    
    refineReqs[cde] = null;
  }
  
  function hideExposureTable($i, cde) {
    $('#' + $i.prop('id')).toggleClass('fa-minus-square');
    $('#' + $i.prop('id')).toggleClass('fa-plus-square');
    $('#' + cde).slideUp(250);
    $i.attr('title', getTranslation('show_data'));
  }
    
  function jumpToPage(e)
  {
    e.preventDefault();
    $a = $(e.target);
    if ( !$a.is('a') ) {
      $a = $a.closest('a');
    }
    var href = $a.data('href');
    $.ajax({
      url: href,
      success: function(html) {
        $newDiv = $(html);
        var code = $newDiv.prop('id');
        $('#' + code).html($newDiv.html());
      }
    })
    return false;
  }
  
  function adjustTbodyHeight($tbl, isExternTable)
  {
    if ( undef(isExternTable) ) {
      isExternTable = false;
    }
    if ( $tbl.length && !$tbl.hasClass('no-data') ) {
      $sixthTr = $tbl.find('.tr-data-m:eq(5)');
      if ( $sixthTr.length ) {
        var tbodyHeight = $sixthTr.position().top - $tbl.find('tbody').position().top+(isExternTable ? 6 : 1);
        $tbl.find('tbody').css('max-height', tbodyHeight);
      }
    }
  }
  
  function updateCounter($counter, newVal)
  {
    var currVal = $counter.text();
    $counter.text(newVal);
    if ( newVal != currVal ) {
      $counter.effect('highlight', {duration: 1500, color: 'lightblue'})
    }
  }
  
  function updateLinkPeriods(evt)
  {
    var pStart = $('.period-year.start').val();
    var pEnd = $('.period-year.end').val();
    var nullYear = '-';
    
    var validPeriod = true;
    
    if ( (pStart.length != 4 && pStart.length != 0) ||
         (pEnd.length != 4 && pEnd.length != 0) ) {
      validPeriod = false;
    } else {
      if ( pStart.length == 0 ) { pStart = nullYear; }
      if ( pEnd.length == 0 ) { pEnd = nullYear; }
    }
    
    if ( validPeriod ) {
      $('.show-expo-profile-elsewhere').each(function() {
        var href = $(this).attr('href');
        var newHref = href.replace(/\/([^\/]{1,4})\/.{1,4}$/, "/" + pStart + "/" + pEnd);
        $(this).attr('href', newHref);
      });
    }
  }
  
  function initMultiselect($rootElem)
  {
    var initSel = function($sel) {
      $sel.chosen({
        no_results_text: $sel.data('noResultsText'),
        search_contains: true
      }).on('change', agentSubsetChanged);
    }
    
    if ( $rootElem.is('select') ) {
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
    if ( selectedAgents.length == 2 && $cc.find('.' + className).length == 0 ) {
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
          var treeConfigFilePath = res.treeConfigFilename;
          $.getScript(treeConfigFilePath, function( data, textStatus, jqxhr ) {
            new Treant(chart_config);
          });
        }
      }
    });
  }