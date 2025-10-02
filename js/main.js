function toastSetup(msg, type) {
    var toastContainer = document.querySelector(".toast-container");
    var toastElement = document.createElement("div");
    toastElement.classList.add("toast", "position-fixed", "top-0", "end-0");
  
    var toastHeader = document.createElement("div");
    toastHeader.classList.add("toast-header", "d-flex", "gap-2");
  
    var strongElement = document.createElement("strong");
    strongElement.classList.add("me-auto");
    var headerText = "";
    switch (type) {
      case "error":
        toastElement.classList.add("bg-danger", "text-white");
        headerText = "Error";
        break;
      case "notice":
        toastElement.classList.add("bg-info", "text-white");
        headerText = "Notice";
        break;
      case "warning":
        toastElement.classList.add("bg-warning", "text-dark");
        headerText = "Warning";
        break;
    }
    strongElement.textContent = headerText;
    toastHeader.appendChild(strongElement);
  
    var closeButton = document.createElement("button");
    closeButton.setAttribute("type", "button");
    closeButton.classList.add("btn-close");
    closeButton.setAttribute("data-bs-dismiss", "toast");
    closeButton.setAttribute("aria-label", "Close");
    toastHeader.appendChild(closeButton);
  
    toastElement.appendChild(toastHeader);
  
    var toastBody = document.createElement("div");
    toastBody.classList.add("toast-body");
    var bodySpan = document.createElement("span");
    bodySpan.textContent = msg;
    toastBody.appendChild(bodySpan);
    toastElement.appendChild(toastBody);
  
    toastContainer.appendChild(toastElement);
  
    var toast = new bootstrap.Toast(toastElement);
    toast.show();
  
    function formatAMPM(date) {
      var hours = date.getHours();
      var minutes = date.getMinutes();
      var ampm = hours >= 12 ? "P.M" : "A.M";
      hours = hours % 12;
      hours = hours ? hours : 12;
      minutes = minutes < 10 ? "0" + minutes : minutes;
      var strTime = hours + "." + minutes + " " + ampm;
      return strTime;
    }
  
    setInterval(function () {
      var now = new Date();
      document.getElementById("toastTime").textContent = formatAMPM(now);
    }, 1000);
  }
  