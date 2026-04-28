document.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(window.location.search);
  const popup = document.getElementById("errorPopup");

  if (params.get("error") === "1" && popup) {
    popup.classList.add("show");

    setTimeout(() => {
      popup.classList.remove("show");
      // limpia la URL
      window.history.replaceState(
        {},
        document.title,
        window.location.pathname
      );
    }, 3000);
  }
});