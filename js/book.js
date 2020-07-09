var haircuts = [
  ["T", "30-0-0"],
  ["TP", "60-0-0"],
  ["C P", "30-30-45"],
  ["C TP", "30-30-60"],
  ["P", "45-0-0"],
  ["CS P", "60-30-45"],
  ["CS TP", "60-30-60"],
  ["CS su P", "30-30-45"],
  ["SHT TP", "60-30-60"],
  ["SHT P", "60-30-45"],
  ["RIT P", "15-30-45"],
  ["RIT TP", "15-30-60"],
  ["RELAX P", "45-15-45"],
  ["RELAX TP", "45-15-60"],
  ["PERM. P", "60-15-30"],
  ["PERM. TP", "60-15-45"],
];

function initFunction() {
  setHaircuts();
}

//Initialize haircuts options values in html
function setHaircuts() {
  haircuts.forEach((cut) => {
    var option = document.createElement("option");
    option.setAttribute("value", cut[0]);
    var text = document.createTextNode(cut[0]);
    option.appendChild(text);
    document.getElementById("haircuts").appendChild(option);
  });
}

// on cut selection change --> update other info.
function updateCutInfo() {
  var cut = document.getElementById("haircuts");
  var cutName = cut.options[cut.selectedIndex].value;
  updateSelectedCutTimes(cutName);
}

//update cut times of the selected cut.
function updateSelectedCutTimes(cutName) {
  var cutTime;
  for (var i = 0; i < haircuts.length; i++)
    if (haircuts[i][0] == cutName) {
      cutTime = haircuts[i][1];
      break;
    }
  haircutTimes = cutTime.split("-");

  for (var i = 0; i < haircutTimes.length; i++) {
    var select = document.getElementById("phase" + (i + 1));
    var timeValue = parseInt(haircutTimes[i], 10);
    select.value = timeValue;
  }
}

function getSecondsValue(time) {
  var HM = time.split(":");
  return (seconds = HM[0] * 3600 + HM[1] * 60);
}

function setSplitMiddle(start, end) {
  var interval = 900;
  var select = document.getElementById("splitMiddle");
  while (select.firstChild) select.removeChild(select.firstChild);

  var startSeconds = getSecondsValue(start);
  var endSeconds = getSecondsValue(end);
  endSeconds -= interval;

  if (startSeconds >= endSeconds) {
    var option = document.createElement("option");
    var text = document.createTextNode("Indivisibile");
    option.appendChild(text);
    select.appendChild(option);
    return;
  }

  while (startSeconds < endSeconds) {
    var option = document.createElement("option");
    startSeconds += interval;

    var hours = Math.floor(startSeconds / 3600);
    var minutes = Math.floor((startSeconds - hours * 3600) / 60);
    if (hours < 10) hours = "0" + hours;
    if (minutes < 10) minutes = "0" + minutes;

    var text = document.createTextNode(hours + ":" + minutes);
    option.appendChild(text);
    select.appendChild(option);
  }
}

$(document).ready(function () {
  $(".operations").click(function () {
    var timeslot = $(this).closest("tr").find("td:first-child").text();
    var id = $(this).attr("id").split("-");
    var parrucchiere = id[0];
    var endTime = id[1];
    var idSlot = timeslot + "-" + parrucchiere;
    var client = document.getElementById(idSlot).textContent.split(":");
    var clientName = client[0];
    var cut = client[1];

    $("#infoCliente").val(clientName);
    $("#infoParrucchiere").val(parrucchiere);
    $("#infoTimeslot").val(timeslot);
    $("#infoTaglio").val(cut);
    $("#fineTaglio").val(endTime);
    $("#operationModal").modal();
  });

  $(".book").click(function () {
    var timeslot = $(this).closest("tr").find("td:first-child").text();
    var parrucchiere = $(this).attr("id");
    $("#slot").html(timeslot);
    $("#parrucchiere").val(parrucchiere);
    $("#timeslot").val(timeslot);
    $("#addModal").modal();
  });

  $(".editBook").click(function () {
    var parrucchiere = $("#infoParrucchiere").val();
    $("#actualHairdresser").val(parrucchiere);
    $("#editTimeslot").val($("#infoTimeslot").val());
    $("#editModal").modal();
  });

  $(".splitButton").click(function () {
    var middleTime = $(this).closest("tr").find("td:first-child").text();
    var id = $(this).attr("id").split("-");
    var parrucchiere = id[0];
    var start = id[1];
    var endTime = id[2];

    $("#splitHairdresser").val(parrucchiere);
    $("#splitStart").val(start);
    $("#splitMiddle").val(middleTime);
    $("#splitEnd").val(endTime);
    $("#splitConfirm").click();
  });

  $(".hairdresser-header").click(function () {
    var parrucchiere = $(this)
      .contents()
      .filter(function () {
        return this.nodeType === 3;
      })
      .text();
    var midday = $(this).attr("id");
    window.location.href =
      "./editMiddayForm.php?parrucchiere=" + parrucchiere + "&midday=" + midday;
  });

  $("#checkboxAM").on("change", function () {
    var div = $(".divAM");
    if ($(this).is(":checked")) div.css("visibility", "visible");
    else div.css("visibility", "hidden");
  });

  $("#checkboxPM").on("change", function () {
    var div = $(".divPM");
    if ($(this).is(":checked")) div.css("visibility", "visible");
    else div.css("visibility", "hidden");
  });

  $("#checkboxAllDay").on("change", function () {
    var div = $(".divPM");
    if ($(this).is(":checked")) {
      div.css("visibility", "hidden");
      $("#start-time").text("Inizio");
      $("#end-time").text("Fine");
    } else {
      div.css("visibility", "visible");
      $("#start-time").text("Inizio Mattina");
      $("#end-time").text("Fine Mattina");
    }
  });

  $(".removeSlots").click(function () {
    if (!confirm("Sicuro di voler modificare l'orario?")) return;
    var timeslot = $(this).closest("tr").find("td:first-child").text();
    var hairdresser = $(this).attr("id");
    window.location.href =
      "./php/employeeUtil/editSchedule.php?slot=" +
      timeslot +
      "&parrucchiere=" +
      hairdresser;
  });

  $(".show-hide").click(function () {
    var hairdresser = $(this).attr("id");
    window.location.href =
      "./php/employeeUtil/showHideEmployee.php?parrucchiere=" + hairdresser;
  });
});
