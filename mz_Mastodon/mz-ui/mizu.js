$(function () {
  // tabs
  function fnToggleTab() {
    if (location.hash !== "") {
      $(".tab-content").hide();
      $(".content-box-tabs a").removeClass("current");
      $(location.hash).show();
      $('a[href="' + location.hash + '"]').addClass("current");
    }
  }
  fnToggleTab();
  $(".content-box-tabs a").click(function () {
    // alert(this.href);
    window.history.pushState(null, null, this.href.replace(/#tab1/, ""));
  });
  // readOnly
  const fnReCover = ($el) => {
    if ($el.data("val")) {
      $el.val($el.data("val"));
    } else {
      $el.data("val", $el.val());
    }
    $el.focus();
    // $el.select();
  };
  // 因为是在 zblog 里选项器用的相邻
  $(".focus + *").mouseenter(function (i) {
    $(this).focus();
  });
  $(".placeholder").each(function (i) {
    const str = $(this).data("placeholder");
    if (str) {
      const $el = $(this).next("*");
      $el.attr("placeholder", str);
    }
  });
  $(".auto-select").each(function (i) {
    const $next = $(this).next("*");
    $next.addClass("auto-select");
  });
  $(".readonly + *")
    .click(function () {
      const _this = this;
      fnReCover($(this));
      setTimeout(function () {
        if ($(_this).hasClass("auto-select")) {
          $(_this).select();
        }
      }, 150);
    })
    .dblclick(function () {
      $(this).select();
    })
    .mouseenter(function () {
      fnReCover($(this));
    })
    .keyup(function () {
      fnReCover($(this));
    })
    .change(function () {
      fnReCover($(this));
    })
    // .attr("readonly", 1);
});
