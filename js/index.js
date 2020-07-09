//Load the current day booking page.
function showCurrentDay() {
  var date = new Date();
  var year = date.getFullYear();
  var month = (date.getMonth() + 1).toString().padStart(2, "0");
  var day = date.getDate().toString().padStart(2, "0");
  var href = "book.php?date=" + year + "-" + month + "-" + day;
  window.location.href = href;
}
