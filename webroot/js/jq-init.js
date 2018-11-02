$(document).ready(function() {
    initElems();
    setTriggers();
});

silentAjax = false;

function initElems()
{
  $(document).on({
    ajaxStart: function() {
      if ( !silentAjax ) {
        showAjaxSpinner();
      } },
    ajaxStop: function() {
      showAjaxSpinner(false)
    },
    beforeSend : function(xhr) { xhr.overrideMimeType('text/plain; charset=utf-8');}
  });

  $('.lang-link').click(function(e) {
    e.preventDefault();
    var url = $(this).attr('href');
    if ( $('[name=classi_code]').val().length ) {
      url += "&code=" + $('[name=classi_code]').val();
    }
    window.location.href = url;
  })

  function tog(v){return v?'addClass':'removeClass';} 
  var clearableCN = "clearable", clearBtnShowingCN = "x", mouseOverClearBtnCN = "onX";
  $(document)
    .on('input', '.' + clearableCN, function() {
      $(this)[tog(this.value)](clearBtnShowingCN);
    }).on('mousemove', '.' + clearableCN + '.' + clearBtnShowingCN, function(e) {
      $(this)[tog(this.offsetWidth-18 < e.clientX-this.getBoundingClientRect().left)](mouseOverClearBtnCN);
    }).on('click', '.' + clearableCN + '.' + mouseOverClearBtnCN, function(ev) {
      ev.preventDefault();
      $(this).removeClass(clearBtnShowingCN + ' ' + mouseOverClearBtnCN);
      resetSelection();
    }
  );
  // end initElems()  
  
  if ( $('body').hasClass('extern') ) {
    adjustTbodyHeight($('table.tbl-exposure-data'), true);
  }
  
  initMultiselect($('[name="choice_agents[]"]'));
}

function setTriggers()
{
  $('#classi-code').select().focus().keyup();
  
  $('.draw-tree').click(drawTree);
}