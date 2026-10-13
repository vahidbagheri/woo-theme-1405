jQuery(document).ready(function ($) {
  currentLang = document.documentElement.lang.slice(0, 2);
  if (typeof wpmlData !== "undefined" && wpmlData.currentLanguage) {
    var currentLang = wpmlData.currentLanguage;
  }
  // TOKEN
  let guestToken = "";
  acf.add_action("load_field/key=field_625578133b6f6", function () {
    $.ajax({
      url: "https://gateway.citysoft.ir/api/v1.0/guesttoken?OrgId=" + 10,
      type: "GET",
      success: function (response) {
        if (response.Success) guestToken = response.token;
      },
    });
  });
  acf.add_action("show_field/name=cn_hotel-city", function () {
    $.ajax({
      url: "https://gateway.citysoft.ir/api/v1.0/guesttoken?OrgId=" + 10,
      type: "GET",
      success: function (response) {
        if (response.Success) guestToken = response.token;
      },
    });
  });

  var services = [
    "flight",
    "hotel",
    "flighandhotel",
    "tour",
    "package",
    "cip",
    "insurance",
    "visa",
    "train",
  ];
  acf.add_action("load_field/key=field_62f8c09236784", function () {
    $(".acf-row:not(.acf-clone)").each(function (index) {
      $(this)
        .find(
          "td[data-name='cn-select-service'] .acf-input .acf-input-wrap>input",
        )
        .each(function () {
          if (!$(this).val()) {
            $(this).val(services[index]);
            localStorage.setItem("citynet-plugin-tab", "yes");
          }
        });
    });

    if (localStorage.getItem("citynet-plugin-tab") == "yes") {
      $(".acf-row:not(.acf-clone)").each(function (index) {
        $(this)
          .find("td[data-name='active'] .acf-input .acf-true-false>input")
          .each(function () {
            $(this).val(1);
            $(this).addClass("hhhhhhhhhhhh");
          });
        return index !== 2;
      });

      $(".acf-row:not(.acf-clone)").each(function (index) {
        $(this)
          .find("td[data-name='show'] .acf-input .acf-true-false>input")
          .each(function () {
            $(this).val(1);
            $(this).addClass("hhhhhhhhhhhh");
          });
        return index !== 2;
      });
      localStorage.removeItem("citynet-plugin-tab");
    }
  });

  // Cities
  acf.add_action("append_field/name=cn_city", function () {
    var repeaterWrapper = $(
      'div[data-name="cn_hotel-city"], div[data-orig_name="cn_hotel-city"] .ui-sortable',
    );
    var lastRowNum = repeaterWrapper.children(
      ".acf-row:not(.acf-clone)",
    ).length;
    var selectInp = repeaterWrapper
      .find(".acf-row:not(.acf-clone")
      .eq(lastRowNum - 1)
      .find("#cn-custom-select input");
    var dropdown;
    var dropdownHtml = '<ul class="cn-custom-select__dropdown">';
    dropdownHtml += '<input type="search">';
    dropdownHtml += "</ul>";
    selectInp.parent().append(dropdownHtml);
    selectInp
      .parent()
      .prepend('<input type="text" id="cn-fake-dropdow-input" readonly>');
    selectInp.hide();
    var fakeSelectInp = repeaterWrapper
      .find(".acf-row:not(.acf-clone")
      .eq(lastRowNum - 1)
      .find("#cn-custom-select input#cn-fake-dropdow-input");

    fakeSelectInp.on("click", function () {
      var currentSearch;
      dropdown = $(this).parent().find(".cn-custom-select__dropdown");
      dropdown.show();
      dropdown.find('input[type="search"]').focus();
      dropdown.find('input[type="search"]').on("keyup", function () {
        var currentSelect = $(this)
          .parents("div.acf-input-wrap")
          .eq(0)
          .find("#cn-fake-dropdow-input");
        currentSearch = $(this);
        var searchKey = $(this).val();
        if (searchKey.length > 2) {
          dropdown.show();
          $.ajax({
            url:
              "https://gateway.citysoft.ir/api/v1.0/hotels/smartsearch?q=" +
              searchKey,
            type: "GET",
            async: false,
            cache: false,
            headers: { Authorization: "Bearer " + guestToken },

            success: function (response) {
              dropdown.children("li").remove();
              var html = "";
              if (response.Success) {
                response.Items.city.forEach(function (item) {
                  city = JSON.stringify(item).replace(/"/g, "'");
                  html += `<li class='cn-custom-select__option' data-key="${city}"><b>${item.cityNameEn}</b><span>${item.countryNameEn}</span></li>`;
                });
                dropdown.append(html);
              }
            },
          });
          dropdown.find(".cn-custom-select__option").on("click", function () {
            selectInp.val($(this).data("key"));
            currentSelect.val($(this).find("b").html());
            dropdown.hide();
            currentSearch.val("");
            dropdown.children("li").remove();
          });
        } else {
          dropdown.children("li").remove();
        }
      });
      $(document).mouseup(function (e) {
        var container = dropdown;
        if (!container.is(e.target) && container.has(e.target).length === 0) {
          container.hide();
          currentSearch.val();
          dropdown.children("li").remove();
        }
      });
    });
  });
  acf.add_action("load_field/name=cn_hotel-city", function () {
    $(".acf-row:not(.acf-clone)", 'div[data-name="cn_hotel-city"] tbody').each(
      function () {
        var selectInp = $(this).find("#cn-custom-select input");
        var val = JSON.parse( selectInp.val().replace(/'/g, '"') );
        var dropdownHtml = '<ul class="cn-custom-select__dropdown">';
        dropdownHtml += '<input type="search">';
        dropdownHtml += "</ul>";
        selectInp.parent().append(dropdownHtml);
        selectInp
          .parent()
          .prepend('<input type="text" id="cn-fake-dropdow-input" readonly>');
        selectInp.parent().find("#cn-fake-dropdow-input").val(val.cityNameEn);
        selectInp.hide();
      },
    );
    var dropdown;
    var fakeSelectInp = $('div[data-name="cn_hotel-city"] tbody')
      .find(".acf-row:not(.acf-clone")
      .find("#cn-custom-select input#cn-fake-dropdow-input");

    fakeSelectInp.on("click", function () {
      var currentSearch;
      dropdown = $(this).parent().find(".cn-custom-select__dropdown");
      dropdown.show();
      dropdown.find('input[type="search"]').focus();
      dropdown.find('input[type="search"]').on("keyup", function () {
        var currentSelect = $(this)
          .parents("div.acf-input-wrap")
          .eq(0)
          .find("#cn-fake-dropdow-input");
        currentSearch = $(this);
        var searchKey = $(this).val();
        if (searchKey.length > 2) {
          dropdown.show();
          $.ajax({
            url:
              "https://gateway.citysoft.ir/api/v1.0/hotels/smartsearch?q=" +
              searchKey,
            type: "GET",
            async: false,
            cache: false,
            headers: { Authorization: "Bearer " + guestToken },
            success: function (response) {
              dropdown.children("li").remove();
              var html = "";
              if (response.Success) {
                response.Items.city.forEach(function (item) {
                  city = JSON.stringify(item).replace(/"/g, "'");
                  html += `<li class='cn-custom-select__option' data-key="${city}"><b>${item.cityNameEn}</b><span>${item.countryNameEn}</span></li>`;
                });
                dropdown.append(html);
              }
            },
          });
          dropdown.find(".cn-custom-select__option").on("click", function () {
            $(this).parent().prev().val($(this).data("key"));
            currentSelect.val($(this).find("b").html());
            dropdown.hide();
            currentSearch.val("");
            dropdown.children("li").remove();
          });
        } else {
          dropdown.children("li").remove();
        }
      });
      $(document).mouseup(function (e) {
        var container = dropdown;
        if (!container.is(e.target) && container.has(e.target).length === 0) {
          container.hide();
          currentSearch.val();
          dropdown.children("li").remove();
        }
      });
    });
  });

  // Hotels
  acf.add_action("append_field/name=cn_hotelname", function () {
    console.log("cn_hotel-append_field/name=cn_hotelname");
    var repeaterWrapper = $(
      'div[data-name="cn_hotel-page"], div[data-orig_name="cn_hotel-page"] .ui-sortable',
    );
    var lastRowNum = repeaterWrapper.children(
      ".acf-row:not(.acf-clone)",
    ).length;
    var selectInp = repeaterWrapper
      .find(".acf-row:not(.acf-clone")
      .eq(lastRowNum - 1)
      .find("#cn-custom-select input");
    var dropdown;
    var dropdownHtml = '<ul class="cn-custom-select__dropdown">';
    dropdownHtml += '<input type="search">';
    dropdownHtml += "</ul>";
    selectInp.parent().append(dropdownHtml);
    selectInp
      .parent()
      .prepend('<input type="text" id="cn-fake-dropdow-input" readonly>');
    selectInp.hide();
    var fakeSelectInp = repeaterWrapper
      .find(".acf-row:not(.acf-clone")
      .eq(lastRowNum - 1)
      .find("#cn-custom-select input#cn-fake-dropdow-input");

    fakeSelectInp.on("click", function () {
      console.log("ADS");
      var currentSearch;
      dropdown = $(this).parent().find(".cn-custom-select__dropdown");
      dropdown.show();
      dropdown.find('input[type="search"]').focus();
      dropdown.find('input[type="search"]').on("keyup", function () {
        var currentSelect = $(this)
          .parents("div.acf-input-wrap")
          .eq(0)
          .find("#cn-fake-dropdow-input");
        currentSearch = $(this);
        var searchKey = $(this).val();
        if (searchKey.length > 2) {
          dropdown.show();
          $.ajax({
            url:
              "https://gateway.citysoft.ir/api/v1.0/hotels/smartsearch?q=" +
              searchKey,
            type: "GET",
            async: false,
            cache: false,
            headers: { Authorization: "Bearer " + guestToken },
            success: function (response) {
              dropdown.children("li").remove();
              if (response.Success) {
                var html = "";
                response.Items.hotel.forEach(function (res) {
                  let key = { ...res };
                  key.CitynetNameEn = key.CitynetNameEn.replaceAll(
                    "'",
                    "CitynetReplace",
                  );
                  key = JSON.stringify(key).replaceAll('"', "'");
                  html += `<li class="cn-custom-select__option" data-key="${key}">
                  <b>
                   ${
                     res.CitynetNameEn ? res.CitynetNameEn : res.CitynetNameFa
                   }</b>
                  <span>${res.CityEn ? res.CityEn : res.CityFa}</span>
                  </li>`;
                });
                dropdown.append(html);
              }
            },
          });
          dropdown.find(".cn-custom-select__option").on("click", function () {
            $(this).parent().prev().val($(this).data("key"));
            let val = JSON.parse($(this).data("key").replaceAll("'", '"'));
            currentSelect.val(
              `${val.CitynetNameEn.replaceAll("CitynetReplace", "'")} (${
                val.CityEn
              })`,
            );
            dropdown.hide();
            currentSearch.val("");
            dropdown.children("li").remove();
          });
        } else {
          dropdown.children("li").remove();
        }
      });
      $(document).mouseup(function (e) {
        var container = dropdown;
        if (!container.is(e.target) && container.has(e.target).length === 0) {
          container.hide();
          currentSearch.val();
          dropdown.children("li").remove();
        }
      });
    });
  });
  acf.add_action("load_field/name=cn_hotel-page", function () {
    $(".acf-row:not(.acf-clone)", 'div[data-name="cn_hotel-page"] tbody').each(
      function () {
        var selectInp = $(this).find("#cn-custom-select input");
        var val = JSON.parse(selectInp.val().replaceAll("'", '"'));
        val = `${val.CitynetNameEn.replaceAll("CitynetReplace", "'")} (${
          val.CityEn
        })`;
        var dropdownHtml = '<ul class="cn-custom-select__dropdown">';
        dropdownHtml += '<input type="search">';
        dropdownHtml += "</ul>";
        selectInp.parent().append(dropdownHtml);
        selectInp
          .parent()
          .prepend('<input type="text" id="cn-fake-dropdow-input" readonly>');
        selectInp.parent().find("#cn-fake-dropdow-input").val(val);
        selectInp.hide();
      },
    );
    var dropdown;
    var fakeSelectInp = $('div[data-name="cn_hotel-page"] tbody')
      .find(".acf-row:not(.acf-clone")
      .find("#cn-custom-select input#cn-fake-dropdow-input");

    fakeSelectInp.on("click", function () {
      var currentSearch;
      dropdown = $(this).parent().find(".cn-custom-select__dropdown");
      dropdown.show();
      dropdown.find('input[type="search"]').focus();
      dropdown.find('input[type="search"]').on("keyup", function () {
        var currentSelect = $(this)
          .parents("div.acf-input-wrap")
          .eq(0)
          .find("#cn-fake-dropdow-input");
        currentSearch = $(this);
        var searchKey = $(this).val();
        if (searchKey.length > 2) {
          dropdown.show();
          $.ajax({
            url:
              "https://gateway.citysoft.ir/api/v1.0/hotels/smartsearch?q=" +
              searchKey,
            type: "GET",
            async: false,
            cache: false,
            headers: { Authorization: "Bearer " + guestToken },
            success: function (response) {
              dropdown.children("li").remove();
              if (response.Success) {
                var html = "";
                response.Items.hotel.forEach(function (res) {
                  let key = { ...res };
                  key.CitynetNameEn = key.CitynetNameEn.replaceAll(
                    "'",
                    "CitynetReplace",
                  );
                  key = JSON.stringify(key).replaceAll('"', "'");
                  html += `<li class="cn-custom-select__option" data-key="${key}">
                  <b>
                   ${
                     res.CitynetNameEn ? res.CitynetNameEn : res.CitynetNameFa
                   }</b>
                  <span>${res.CityEn ? res.CityEn : res.CityFa}</span>
                  </li>`;
                });
                dropdown.append(html);
              }
            },
          });
          dropdown.find(".cn-custom-select__option").on("click", function () {
            $(this).parent().prev().val($(this).data("key"));
            let val = JSON.parse($(this).data("key").replaceAll("'", '"'));
            currentSelect.val(
              `${val.CitynetNameEn.replaceAll("CitynetReplace", "'")} (${
                val.CityEn
              })`,
            );
            dropdown.hide();
            currentSearch.val("");
            dropdown.children("li").remove();
          });
        } else {
          dropdown.children("li").remove();
        }
      });
      $(document).mouseup(function (e) {
        var container = dropdown;
        if (!container.is(e.target) && container.has(e.target).length === 0) {
          container.hide();
          currentSearch.val();
          dropdown.children("li").remove();
        }
      });
    });
  });

  //popular hotel
  acf.add_action("append_field/name=cn-city", function () {
    var repeaterWrapper = $(
      'div[data-name="cn-most-visited-cities"], div[data-orig_name="cn-most-visited-cities"] .ui-sortable',
    );
    var lastRowNum = repeaterWrapper.children(
      ".acf-row:not(.acf-clone)",
    ).length;
    var selectInp = repeaterWrapper
      .find(".acf-row:not(.acf-clone")
      .eq(lastRowNum - 1)
      .find("#cn-custom-select input");
    console.log("selectInp.val()", selectInp.val());
    var dropdown;
    var dropdownHtml = '<ul class="cn-custom-select__dropdown">';
    dropdownHtml += '<input type="search">';
    dropdownHtml += "</ul>";
    selectInp.parent().append(dropdownHtml);
    selectInp
      .parent()
      .prepend('<input type="text" id="cn-fake-dropdow-input" readonly>');
    selectInp.hide();
    var fakeSelectInp = repeaterWrapper
      .find(".acf-row:not(.acf-clone")
      .eq(lastRowNum - 1)
      .find("#cn-custom-select input#cn-fake-dropdow-input");

    fakeSelectInp.on("click", function () {
      var currentSearch;
      dropdown = $(this).parent().find(".cn-custom-select__dropdown");
      dropdown.show();
      dropdown.find('input[type="search"]').focus();
      dropdown.find('input[type="search"]').on("keyup", function () {
        var currentSelect = $(this)
          .parents("div.acf-input-wrap")
          .eq(0)
          .find("#cn-fake-dropdow-input");
        currentSearch = $(this);
        var searchKey = $(this).val();
        if (searchKey.length > 2) {
          dropdown.show();
          $.ajax({
            url:
              "https://gateway.citysoft.ir/api/v1.0/hotels/smartsearch?q=" +
              searchKey,
            type: "GET",
            async: false,
            cache: false,
            headers: { Authorization: "Bearer " + guestToken },
            success: function (response) {
              dropdown.children("li").remove();
              if (response.Success) {
                var html = "";
                response.Items.city.forEach(function (res) {
                  let key = JSON.stringify(res).replaceAll('"', "'");
                  html += `<li class="cn-custom-select__option" data-key="${key}">
                    <b>
                     ${res.cityNameEn}</b>
                    <span>${res.countryNameEn}</span>
                    </li>`;
                });
                dropdown.append(html);
              }
            },
          });
          dropdown.find(".cn-custom-select__option").on("click", function () {
            $(this).parent().prev().val($(this).data("key"));
            let val = JSON.parse($(this).data("key").replaceAll("'", '"'));
            currentSelect.val(`${val.cityNameEn} (${val.countryNameEn})`);
            dropdown.hide();
            currentSearch.val("");
            dropdown.children("li").remove();
          });
        } else {
          dropdown.children("li").remove();
        }
      });
      $(document).mouseup(function (e) {
        var container = dropdown;
        if (!container.is(e.target) && container.has(e.target).length === 0) {
          container.hide();
          currentSearch.val();
          dropdown.children("li").remove();
        }
      });
    });
  });

  //airports
  acf.add_action("append_field/name=cn-airport", function () {
    var repeaterWrapper = $(
      'div[data-name="cn-most-visited-airports"], div[data-orig_name="cn-most-visited-airports"] .ui-sortable',
    );
    var lastRowNum = repeaterWrapper.children(
      ".acf-row:not(.acf-clone)",
    ).length;
    var selectInp = repeaterWrapper
      .find(".acf-row:not(.acf-clone")
      .eq(lastRowNum - 1)
      .find("#cn-custom-select input");
    var dropdown;
    var dropdownHtml = '<ul class="cn-custom-select__dropdown">';
    dropdownHtml += '<input type="search">';
    dropdownHtml += "</ul>";
    selectInp.parent().append(dropdownHtml);
    selectInp
      .parent()
      .prepend('<input type="text" id="cn-fake-dropdow-input" readonly>');
    selectInp.hide();
    var fakeSelectInp = repeaterWrapper
      .find(".acf-row:not(.acf-clone")
      .eq(lastRowNum - 1)
      .find("#cn-custom-select input#cn-fake-dropdow-input");

    fakeSelectInp.on("click", function () {
      var currentSearch;
      dropdown = $(this).parent().find(".cn-custom-select__dropdown");
      dropdown.show();
      dropdown.find('input[type="search"]').focus();
      dropdown.find('input[type="search"]').on("keyup", function () {
        var currentSelect = $(this)
          .parents("div.acf-input-wrap")
          .eq(0)
          .find("#cn-fake-dropdow-input");
        currentSearch = $(this);
        var searchKey = $(this).val();
        if (searchKey.length > 2) {
          dropdown.show();
          $.ajax({
            url: `https://gateway.citysoft.ir/api/v1.0/airportlist?Lang=${currentLang}&q=${searchKey}`,
            type: "GET",
            async: false,
            cache: false,
            headers: { Authorization: "Bearer " + guestToken },
            success: function (response) {
              dropdown.children("li").remove();
              if (response.Success) {
                var html = "";
                let sortedResults = [...response.Items];
                for (var i = 0; i < sortedResults.length; i++) {
                  sortedResults[i].subCategory = false;
                  if (sortedResults[i].isCity) {
                    for (var j = i + 1; j < sortedResults.length; j++) {
                      if (
                        sortedResults[i].cityCode == sortedResults[j].cityCode
                      ) {
                        sortedResults[j].subCategory = true;
                      } else {
                        sortedResults[j].subCategory = false;
                        break;
                      }
                    }
                    i = j - 1;
                  }
                }
                sortedResults.forEach(function (res) {
                  const resultWithLang = {
                    ...res,
                    airportNameEn: res.airportName,
                    cityNameEn: res.cityName,
                    countryNameEn: res.countryName,
                    originalCityCode: res.cityCode,
                    originalCityName: res.cityName,
                    originalAirportCode: res.airportCode,
                    originalAirportName: res.airportName,
                  };

                  if (res.language && res.language.lang) {
                    resultWithLang[
                      `airportName${capitalizeFirstLetter(res.language.lang)}`
                    ] = res.language.airportName;
                    resultWithLang[
                      `cityName${capitalizeFirstLetter(res.language.lang)}`
                    ] = res.language.cityName;
                    resultWithLang[
                      `countryName${capitalizeFirstLetter(res.language.lang)}`
                    ] = res.language.countryName;
                  }

                  let key = encodeURIComponent(JSON.stringify(resultWithLang));

                  html += `<li class="cn-custom-select__option--airports" data-test3="true" data-key="${key}">
                ${
                  res.subCategory
                    ? '<i class="dashicons dashicons-arrow-right-alt2"></i>'
                    : ""
                }
                <span class="chip ${res.isCity ? "is-city" : ""}">
                <i class="dashicons dashicons-${
                  res.isCity ? "admin-home" : "airplane"
                }"></i>${res.airportCode.replace("*", "")}
                </span>
                <div>
                <b>
                 ${res.airportName}</b>
                <span>${res.isCity ? "" : res.countryName} ${
                  res.cityName
                }</span></div>
                </li>`;
                });
                dropdown.append(html);
              }
            },
          });
          dropdown
            .find(".cn-custom-select__option--airports")
            .on("click", function () {
              const airportData = decodeURIComponent($(this).data("key"));
              $(this).parent().prev().val(airportData);

              const parsedAirportData = JSON.parse(airportData);
              currentSelect.val(
                `${parsedAirportData.cityName} (${parsedAirportData.airportName})`,
              );

              dropdown.hide();
              currentSearch.val("");
              dropdown.children("li").remove();
            });
        } else {
          dropdown.children("li").remove();
        }
      });
      $(document).mouseup(function (e) {
        var container = dropdown;
        if (!container.is(e.target) && container.has(e.target).length === 0) {
          container.hide();
          currentSearch.val();
          dropdown.children("li").remove();
        }
      });
    });
  });

  // Most Visited Airports
  let default_airports = null;
  fetch("../../wp-content/plugins/citynet/assets/other/default_airports.json", {
    headers: {
      "Cache-Control": "no-cache",
    },
  })
    .then((response) => {
      return response.json();
    })
    .then((jsondata) => {
      default_airports = jsondata;
      $(
        ".acf-row:not(.acf-clone)",
        'div[data-name="cn-most-visited-airports"] tbody',
      ).each(function (index) {
        var selectInp = $(this).find("#cn-custom-select input");
        if (!selectInp.val()) {
          selectInp.val(JSON.stringify(default_airports[index]));
        }

        const airportData = JSON.parse(
          decodeURIComponent(selectInp.val().replaceAll("'", '"')),
        );
        const val = `${airportData.cityNameEn} (${airportData.airportNameEn})`;

        var dropdownHtml = '<ul class="cn-custom-select__dropdown">';
        dropdownHtml += '<input type="search">';
        dropdownHtml += "</ul>";
        selectInp.parent().append(dropdownHtml);
        selectInp.hide();
        selectInp
          .parent()
          .prepend('<input type="text" id="cn-fake-dropdow-input" readonly>');
        selectInp.parent().find("#cn-fake-dropdow-input").val(val);
      });
      var dropdown;
      var fakeSelectInp = $('div[data-name="cn-most-visited-airports"] tbody')
        .find(".acf-row:not(.acf-clone")
        .find("#cn-custom-select input#cn-fake-dropdow-input");

      fakeSelectInp.on("click", function () {
        var currentSearch;
        dropdown = $(this).parent().find(".cn-custom-select__dropdown");
        dropdown.show();
        dropdown.find('input[type="search"]').focus();
        dropdown.find('input[type="search"]').on("keyup", function () {
          var currentSelect = $(this)
            .parents("div.acf-input-wrap")
            .eq(0)
            .find("#cn-fake-dropdow-input");
          currentSearch = $(this);
          var searchKey = $(this).val();
          if (searchKey.length > 2) {
            dropdown.show();
            console.log("1");
            $.ajax({
              url: `https://gateway.citysoft.ir/api/v1.0/airportlist?Lang=${currentLang}&q=${searchKey}`,
              type: "GET",
              async: false,
              cache: false,
              headers: { Authorization: "Bearer " + guestToken },
              success: function (response) {
                dropdown.children("li").remove();
                if (response.Success) {
                  var html = "";
                  let sortedResults = [...response.Items];
                  for (var i = 0; i < sortedResults.length; i++) {
                    sortedResults[i].subCategory = false;
                    if (sortedResults[i].isCity) {
                      for (var j = i + 1; j < sortedResults.length; j++) {
                        if (
                          sortedResults[i].cityCode == sortedResults[j].cityCode
                        ) {
                          sortedResults[j].subCategory = true;
                        } else {
                          sortedResults[j].subCategory = false;
                          break;
                        }
                      }
                      i = j - 1;
                    }
                  }
                  sortedResults.forEach(function (res) {
                    const resultWithLang = {
                      ...res,
                      airportNameEn: res.airportName,
                      cityNameEn: res.cityName,
                      countryNameEn: res.countryName,
                      originalCityCode: res.cityCode,
                      originalCityName: res.cityName,
                      originalAirportCode: res.airportCode,
                      originalAirportName: res.airportName,
                    };

                    if (res.language && res.language.lang) {
                      resultWithLang[
                        `airportName${capitalizeFirstLetter(res.language.lang)}`
                      ] = res.language.airportName;
                      resultWithLang[
                        `cityName${capitalizeFirstLetter(res.language.lang)}`
                      ] = res.language.cityName;
                      resultWithLang[
                        `countryName${capitalizeFirstLetter(res.language.lang)}`
                      ] = res.language.countryName;
                    }

                    let key = encodeURIComponent(
                      JSON.stringify(resultWithLang),
                    );
                    console.log("key", key);

                    html += `<li class="cn-custom-select__option--airports" data-test2="true" data-key="${key}">
                    ${
                      res.subCategory
                        ? '<i class="dashicons dashicons-arrow-right-alt2"></i>'
                        : ""
                    }
                    <span class="chip ${res.isCity ? "is-city" : ""}">
                    <i class="dashicons dashicons-${
                      res.isCity ? "admin-home" : "airplane"
                    }"></i>${res.airportCode.replace("*", "")}
                    </span>
                    <div>
                    <b>
                     ${res.airportName}</b>
                    <span>${res.isCity ? "" : res.countryName} ${
                      res.cityName
                    }</span></div>
                    </li>`;
                  });
                  dropdown.append(html);
                }
              },
            });
            dropdown
              .find(".cn-custom-select__option--airports")
              .on("click", function () {
                const airportData = decodeURIComponent(
                  $(this).data("key"),
                ).replaceAll("'", '"');
                $(this).parent().prev().val(airportData);

                const parsedAirportData = JSON.parse(airportData);
                currentSelect.val(
                  `${parsedAirportData.cityName} (${parsedAirportData.airportName})`,
                );

                dropdown.hide();
                currentSearch.val("");
                dropdown.children("li").remove();
              });
          } else {
            dropdown.children("li").remove();
          }
        });
        $(document).mouseup(function (e) {
          var container = dropdown;
          if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.hide();
            currentSearch.val();
            dropdown.children("li").remove();
          }
        });
      });

      // default city acf for flight
      let val = "";
      var selectAirport = $('div[data-name="cn_airport_flight"] input');
      if (!selectAirport.val()) {
        selectAirport.val("");
      }
      if (selectAirport.val()) {
        val = JSON.parse(selectAirport.val().replaceAll("'", '"'));
        val = `${val.cityName} (${val.airportName})`;
      }

      var dropdownHtml = '<ul class="cn-custom-select__dropdown">';
      dropdownHtml += '<input type="search">';
      dropdownHtml += "</ul>";
      selectAirport.parent().append(dropdownHtml);
      selectAirport.hide();
      selectAirport
        .parent()
        .prepend('<input type="text" id="cn-fake-dropdow-input" readonly>');
      selectAirport.parent().find("#cn-fake-dropdow-input").val(val);

      var dropdown;
      var fakeSelectInp = $('div[data-name="cn_airport_flight"]').find(
        "input#cn-fake-dropdow-input",
      );
      fakeSelectInp.on("click", function () {
        var currentSearch;
        dropdown = $(this).parent().find(".cn-custom-select__dropdown");
        dropdown.show();
        dropdown.find('input[type="search"]').focus();
        dropdown.find('input[type="search"]').on("keyup", function () {
          var currentSelect = $(this)
            .parents("div.acf-input-wrap")
            .eq(0)
            .find("#cn-fake-dropdow-input");
          currentSearch = $(this);
          var searchKey = $(this).val();
          if (searchKey.length > 2) {
            dropdown.show();
            $.ajax({
              url:
                "https://gateway.citysoft.ir/api/v1.0/airportlist?Lang=en&q=" +
                searchKey,
              type: "GET",
              async: false,
              cache: false,
              headers: { Authorization: "Bearer " + guestToken },
              success: function (response) {
                dropdown.children("li").remove();
                if (response.Success) {
                  var html = "";
                  let sortedResults = [...response.Items];
                  for (var i = 0; i < sortedResults.length; i++) {
                    sortedResults[i].subCategory = false;
                    if (sortedResults[i].isCity) {
                      for (var j = i + 1; j < sortedResults.length; j++) {
                        if (
                          sortedResults[i].cityCode == sortedResults[j].cityCode
                        ) {
                          sortedResults[j].subCategory = true;
                        } else {
                          sortedResults[j].subCategory = false;
                          break;
                        }
                      }
                      i = j - 1;
                    }
                  }
                  sortedResults.forEach(function (res) {
                    let key = JSON.stringify(res).replaceAll('"', "'");
                    html += `<li class="cn-custom-select__option--airports" data-key="${key}">
                  ${
                    res.subCategory
                      ? '<i class="dashicons dashicons-arrow-right-alt2"></i>'
                      : ""
                  }
                  <span class="chip ${res.isCity ? "is-city" : ""}">
                  <i class="dashicons dashicons-${
                    res.isCity ? "admin-home" : "airplane"
                  }"></i>${res.airportCode.replace("*", "")}
                  </span>
                  <div>
                  <b>
                    ${res.airportName}</b>
                  <span>${res.isCity ? "" : res.countryName} ${
                    res.cityName
                  }</span></div>
                  </li>`;
                  });
                  dropdown.append(html);
                }
              },
            });
            dropdown
              .find(".cn-custom-select__option--airports")
              .on("click", function () {
                $(this).parent().prev().val($(this).data("key"));
                let val = JSON.parse($(this).data("key").replaceAll("'", '"'));
                currentSelect.val(`${val.cityName} (${val.airportName})`);
                dropdown.hide();
                currentSearch.val("");
                dropdown.children("li").remove();
              });
          } else {
            dropdown.children("li").remove();
          }
        });
        $(document).mouseup(function (e) {
          var container = dropdown;
          if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.hide();
            currentSearch.val();
            dropdown.children("li").remove();
          }
        });
      });
      //delete BTN
      var deleteBtn = '<i class="dashicons-before dashicons-remove"></i>';
      selectAirport
        .parent()
        .parent()
        .css({ display: "flex", justify: "space-between" });
      selectAirport.parent().css({ width: "100%" });
      selectAirport.parent().parent().append(deleteBtn);
      var deleteBtnWrapper = $('div[data-name="cn_airport_flight"]').find(
        ".acf-input .dashicons-remove",
      );
      deleteBtnWrapper.css({ margin: "5px 5px 0" });
      deleteBtnWrapper.on("click", function () {
        $(this).prev().find("input:not(#cn-fake-dropdow-input)").val("");
        $(this).prev().find("input#cn-fake-dropdow-input").val("");
      });
    });

  // Most Visited Cities
  let default_cities = null;
  fetch("../../wp-content/plugins/citynet/assets/other/default_cities.json", {
    headers: {
      "Cache-Control": "no-cache",
    },
  })
    .then((response) => {
      return response.json();
    })
    .then((jsondata) => {
      default_cities = jsondata;
      $(
        ".acf-row:not(.acf-clone)",
        'div[data-name="cn-most-visited-cities"] tbody',
      ).each(function (index) {
        var selectInp = $(this).find("#cn-custom-select input");
        let val = "";
        if (!selectInp.val()) {
          selectInp.val(JSON.stringify(default_cities[index]));
        }
        val = JSON.parse(selectInp.val().replaceAll("'", '"'));
        val = `${val.cityNameEn} (${val.countryNameEn})`;
        var dropdownHtml = '<ul class="cn-custom-select__dropdown">';
        dropdownHtml += '<input type="search">';
        dropdownHtml += "</ul>";
        selectInp.parent().append(dropdownHtml);
        selectInp.hide();
        selectInp
          .parent()
          .prepend('<input type="text" id="cn-fake-dropdow-input" readonly>');
        selectInp.parent().find("#cn-fake-dropdow-input").val(val);
      });
      var dropdown;
      var fakeSelectInp = $('div[data-name="cn-most-visited-cities"] tbody')
        .find(".acf-row:not(.acf-clone")
        .find("#cn-custom-select input#cn-fake-dropdow-input");

      fakeSelectInp.on("click", function () {
        var currentSearch;
        dropdown = $(this).parent().find(".cn-custom-select__dropdown");
        dropdown.show();
        dropdown.find('input[type="search"]').focus();
        dropdown.find('input[type="search"]').on("keyup", function () {
          var currentSelect = $(this)
            .parents("div.acf-input-wrap")
            .eq(0)
            .find("#cn-fake-dropdow-input");
          currentSearch = $(this);
          var searchKey = $(this).val();
          if (searchKey.length > 2) {
            dropdown.show();
            $.ajax({
              url:
                "https://gateway.citysoft.ir/api/v1.0/hotels/smartsearch?q=" +
                searchKey,
              type: "GET",
              async: false,
              cache: false,
              headers: { Authorization: "Bearer " + guestToken },
              success: function (response) {
                dropdown.children("li").remove();
                if (response.Success) {
                  var html = "";
                  response.Items.city.forEach(function (res) {
                    let key = JSON.stringify(res).replaceAll('"', "'");
                    html += `<li class="cn-custom-select__option" data-key="${key}">
                    <b>
                     ${res.cityNameEn}</b>
                    <span>${res.countryNameEn}</span>
                    </li>`;
                  });
                  dropdown.append(html);
                }
              },
            });
            dropdown.find(".cn-custom-select__option").on("click", function () {
              $(this).parent().prev().val($(this).data("key"));
              let val = JSON.parse($(this).data("key").replaceAll("'", '"'));
              currentSelect.val(`${val.cityNameEn} (${val.countryNameEn})`);
              dropdown.hide();
              currentSearch.val("");
              dropdown.children("li").remove();
            });
          } else {
            dropdown.children("li").remove();
          }
        });
        $(document).mouseup(function (e) {
          var container = dropdown;
          if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.hide();
            currentSearch.val();
            dropdown.children("li").remove();
          }
        });
      });

      // default city acf for hotel

      let val = "";
      var selectHotel = $('div[data-name="cn_default_hotel"] input');
      if (!selectHotel.val()) {
        selectHotel.val();
      }
      if (selectHotel.val()) {
        val = JSON.parse(selectHotel.val().replaceAll("'", '"'));
        val = `${val.cityNameEn} (${val.countryNameEn})`;
      }

      var dropdownHtml = '<ul class="cn-custom-select__dropdown">';
      dropdownHtml += '<input type="search">';
      dropdownHtml += "</ul>";
      selectHotel.parent().append(dropdownHtml);
      selectHotel.hide();
      selectHotel
        .parent()
        .prepend('<input type="text" id="cn-fake-dropdow-input" readonly>');
      selectHotel.parent().find("#cn-fake-dropdow-input").val(val);

      var dropdown;
      var fakeSelectInp = $('div[data-name="cn_default_hotel"]').find(
        "input#cn-fake-dropdow-input",
      );
      fakeSelectInp.on("click", function () {
        var currentSearch;
        dropdown = $(this).parent().find(".cn-custom-select__dropdown");
        dropdown.show();
        dropdown.find('input[type="search"]').focus();
        dropdown.find('input[type="search"]').on("keyup", function () {
          var currentSelect = $(this)
            .parents("div.acf-input-wrap")
            .eq(0)
            .find("#cn-fake-dropdow-input");
          currentSearch = $(this);
          var searchKey = $(this).val();
          if (searchKey.length > 2) {
            dropdown.show();
            $.ajax({
              url:
                "https://gateway.citysoft.ir/api/v1.0/hotels/smartsearch?q=" +
                searchKey,
              type: "GET",
              async: false,
              cache: false,
              headers: { Authorization: "Bearer " + guestToken },
              success: function (response) {
                dropdown.children("li").remove();
                if (response.Success) {
                  var html = "";
                  response.Items.city.forEach(function (res) {
                    let key = JSON.stringify(res).replaceAll('"', "'");
                    html += `<li class="cn-custom-select__option" data-key="${key}">
                    <b>
                    ${res.cityNameEn}</b>
                    <span>${res.countryNameEn}</span>
                    </li>`;
                  });
                  dropdown.append(html);
                }
              },
            });
            dropdown.find(".cn-custom-select__option").on("click", function () {
              $(this).parent().prev().val($(this).data("key"));
              let val = JSON.parse($(this).data("key").replaceAll("'", '"'));
              currentSelect.val(`${val.cityNameEn} (${val.countryNameEn})`);
              dropdown.hide();
              currentSearch.val("");
              dropdown.children("li").remove();
            });
          } else {
            dropdown.children("li").remove();
          }
        });
        $(document).mouseup(function (e) {
          var container = dropdown;
          if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.hide();
            currentSearch.val();
            dropdown.children("li").remove();
          }
        });
      });

      //delete BTN
      var deleteBtn = '<i class="dashicons-before dashicons-remove"></i>';
      selectHotel
        .parent()
        .parent()
        .css({ display: "flex", justify: "space-between" });
      selectHotel.parent().css({ width: "100%" });
      selectHotel.parent().parent().append(deleteBtn);
      var deleteBtnWrapper = $('div[data-name="cn_default_hotel"]').find(
        ".acf-input .dashicons-remove",
      );
      deleteBtnWrapper.css({ margin: "5px 5px 0" });
      deleteBtnWrapper.on("click", function () {
        $(this).prev().find("input:not(#cn-fake-dropdow-input)").val("");
        $(this).prev().find("input#cn-fake-dropdow-input").val("");
      });
    });
});

function setCookie(name, value, days) {
  var expires = "";
  if (days) {
    var date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
  var name = name + "=";
  var ca = document.cookie.split(";");
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i].trim();
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

function deleteCookie(name) {
  value = "No value";
  document.cookie =
    name + "=" + value + "; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
}

function capitalizeFirstLetter(str) {
  return str[0].toUpperCase() + str.slice(1);
}
