(() => {
  const script = document.getElementsByTagName("script");
  let api = script[script.length - 1].src.split("?");
  let addJqueryJs = api[0].split("/");
  addJqueryJs.splice(-1, 1, "jquery-1.12.4.min.js");
  api = atob(decodeURIComponent(api[1]));
  if (typeof jQuery === "undefined") {
    document.body.appendChild(document.createElement("script")).src =
      addJqueryJs.join("/");
  }
  window.addEventListener("load", () => {
    (($, url) => {
      function YrActivityLinkGetId(id) {
        if (!id) return;
        window?._hmt?.push?.([
          "_trackEvent",
          "活动链接推广插件",
          "click",
          document.title,
          id,
        ]);
        if ($(".yr-loading").length) {
          $(".yr-loading").show();
        } else {
          $("body").append(
            `<div class="yr-loading" style="display:block"></div>`
          );
        }
        $.ajax({
          type: "post",
          async: true, //部分浏览器下异步请求会失败，所以设置未false
          url,
          data: {
            id,
          },
          dataType: "json",
          beforeSend: function () {},
          complete: function () {
            $(".yr-loading").hide();
          },
          success: function (res) {
            if (res.err && res.err.code === 0) {
              if (
                $("#active_link-wrapper").length ||
                $("#active_link-wrapper").size?.()
              ) {
                $("#active_link-wrapper").remove();
              }
              $("body").append(res.data);
            } else {
              alert((res.err && res.err.msg) || "数据获取异常！");
            }
          },
          error: function (data) {
            alert("数据获取失败！");
          },
        });
      }
      //加个防抖
      let yractivitylink_timer = null;
      $("body").on("click", "[data-yractivitylink-id]", (e) => {
        clearTimeout(yractivitylink_timer);
        //解决有可能存在多个触发事件源的问题，导致获取到的id就不准确了
        let id = $(e.currentTarget).attr("data-yractivitylink-id");
        yractivitylink_timer = setTimeout(() => {
          if (id !== undefined) {
            YrActivityLinkGetId(id);
          }
        }, 500);
      });
    })(jQuery, api);
  });
})();
