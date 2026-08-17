(() => {
  const syncTagInput = (box, target) => {
    if (!box || !target) return;

    target.value = Array.from(box.querySelectorAll(".tag-chip"))
      .map((chip) => chip.childNodes[0]?.textContent?.trim() || "")
      .filter(Boolean)
      .join(", ");
  };

  const addTag = (input, box, target) => {
    const value = input.value.trim().replace(/,$/, "");
    if (!value || !box) return;

    const chip = document.createElement("span");
    chip.className = "tag-chip keyword";
    chip.append(document.createTextNode(value));

    const remove = document.createElement("span");
    remove.className = "tag-chip-remove";
    remove.dataset.tagRemove = "true";
    remove.innerHTML =
      '<svg class="icon"><use href="/assets/icons/sprite.svg#icon-change-password-choose-a-strong"></use></svg>';

    chip.append(remove);
    box.insertBefore(chip, input);
    input.value = "";
    syncTagInput(box, target);
  };

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-tag-box]").forEach((box) => {
      const input = box.querySelector("[data-tag-input]");
      const target = document.getElementById(box.dataset.tagTarget || "");

      box.addEventListener("click", (event) => {
        const remove = event.target.closest("[data-tag-remove]");
        if (remove) {
          event.stopPropagation();
          remove.closest(".tag-chip")?.remove();
          syncTagInput(box, target);
          return;
        }

        input?.focus();
      });

      input?.addEventListener("keydown", (event) => {
        if (event.key !== "Enter" && event.key !== ",") return;
        event.preventDefault();
        addTag(input, box, target);
      });

      syncTagInput(box, target);
    });

    document.querySelector("[data-clear-file]")?.addEventListener("click", () => {
      const input = document.getElementById("logoFileInput");
      if (input) input.value = "";
    });
  });
})();
