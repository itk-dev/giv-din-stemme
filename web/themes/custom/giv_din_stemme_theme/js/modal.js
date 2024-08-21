[...document.getElementsByClassName("btnOpenModal")].forEach((item) => {
  item.addEventListener("click", openModal);
});

[...document.getElementsByClassName("btnCloseModal")].forEach((item) => {
  item.addEventListener("click", closeModal);
});

function openModal() {
  let id = this.getAttribute('modal-id');
  document.getElementById(id).classList.remove("hidden");
}

function closeModal() {
  let id = this.getAttribute('modal-id');
  document.getElementById(id).classList.add("hidden");
}